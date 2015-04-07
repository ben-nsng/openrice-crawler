<?php

/* * * * * * * *
 *
 *   Helper Function
 *
 * * * * * * * */

function startsWith($haystack, $needle) {
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
	// search forward starting from end minus needle length characters
	return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

function curl_create($url) {
	
	$url = str_replace('&amp;', '&', $url);
	$url = trim($url);
	echo $url . "\n";

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36');
	curl_setopt($curl, CURLOPT_HEADER, 1);
	// curl_setopt($curl, CURLOPT_VERBOSE, 1);

	$header=array();            
	$header[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
	$header[]="Accept-Encoding: gzip, deflate";
	$header[]="Accept-Language: en-US,en;q=0.5";
	$header[]="Connection: keep-alive";
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

	return $curl;
}

function curl_set_cookies($curl, $cookies) {
	$params = http_build_query($cookies, NULL, '; ');
	curl_setopt($curl, CURLOPT_COOKIE, $params);
}

function curl_post($curl, $params = array()) {
	$params = http_build_query($params, NULL, '&');
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
}

function curl_cookies($response) {
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $m);
	$cookies = array_filter($m[1], function($str) {
		if(preg_match('/deleted/mi', $str, $c) > 0)
			return false;
		return true;
	});
	parse_str(implode('&', $cookies), $cookies);
	return $cookies;
}

function curl_execute($curl) {
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_ENCODING , "gzip");
	//response
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

/* * * * * * * *
 *
 *   Library & Helper
 *
 * * * * * * * */

include('ganon.php');
define('BASE_DIR', __DIR__);

$hostname = {{ mask }};
$database = {{ mask }};
$username = {{ mask }};
$password = {{ mask }};
$lang = 'zh';
$keywords = array(
	'zh' =>
		array(
			'wish_to_go' => '想去',
			'been_here' => '去過',
			'bookmark' => '收藏'
		),
	'en' =>
		array(
			'wish_to_go' => 'Wish to Go',
			'been_here' => 'Been Here',
			'bookmark' => 'Bookmark'
		)
	);

$dbh = new PDO('mysql:host=' . $hostname . ';dbname=' . $database . ';', $username, $password);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbh->query('SET NAMES UTF8');

function start_crawl_restaurant_detail() {
	global $dbh, $lang, $keywords;

	$sql = 'SELECT id, url FROM restaurant WHERE lat=""';
	$stmt = $dbh->query($sql);

	$cat = false;
	$try = 0;
	while($cat || $row = $stmt->fetch(PDO::FETCH_ASSOC)) {

		if($cat) $try++;
		if($try > 1) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$try = 0;
		}
		$cat = !crawl_restaurant_detail($row['id'], $row['url']);

	}
}

function start_crawl_restaurant_review() {
	global $dbh, $lang, $keywords;

	$sql = 'SELECT id, url FROM restaurant WHERE visited=0 OR visited IS NULL ORDER BY id ASC';
	$stmt = $dbh->query($sql);

	$cat = false;
	$try = 0;
	while($cat || $row = $stmt->fetch(PDO::FETCH_ASSOC)) {

		if($cat) $try++;
		if($try > 1) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$try = 0;
		}
		$cat = !crawl_restaurant_review($row['id'], $row['url']);

	}
}

function start_crawl_user_detail() {
	global $dbh, $lang, $keywords;

	$sql = 'SELECT id FROM user WHERE visited=0 ORDER BY id ASC';
	$stmt = $dbh->query($sql);

	$cat = false;
	$try = 0;
	while($cat || $row = $stmt->fetch(PDO::FETCH_ASSOC)) {

		if($cat) $try++;
		if($try > 1) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$try = 0;
		}
		$cat = !crawl_user_detail($row['id']);

		sleep(2);

	}

}

function crawl_restaurant_detail($id, $url) {
	global $dbh, $lang, $keywords;

	// $url = 'http://www.openrice.com' . $url;
	$url = 'http://www.openrice.com/' . $lang . '/restaurant/sr2.htm?&shopid=' . $id . '&tc=MYOR';

	$curl = curl_create($url);
	$response = curl_execute($curl);
	if(!$response) return false;
	$dbh->beginTransaction();

	$ipos = stripos($response, 'OR_GoogleMap.initialize("sr2_em_map", {');
	$snap = substr($response, $ipos, 1000);

	preg_match('/centerAddress: "(.+)"/mi', $snap, $m);
	$address = $m[1];

	preg_match('/centerLat: (.+),/mi', $snap, $m);
	$lat = $m[1];

	preg_match('/centerLng: (.+),/mi', $snap, $m);
	$lng = $m[1];

	$html = str_get_dom($response);

	$col = $html('div.info_basic_first');

	if(count($col) > 0) {
		$col = $col[0];

		$labels = $col('a.hiddenlink');
		$label_names = array();

		if(count($labels) > 0) {
			foreach($labels as $label) {
				$name = $label->getPlainText();

				$label_names[] = $name;
				$stmt = $dbh->prepare('INSERT IGNORE INTO label(name) VALUES(?)');
				$stmt->execute(array($name));
			}

			$stmt = $dbh->prepare('INSERT IGNORE INTO label_restaurant(restaurant_id, label_id) SELECT ?, id FROM label WHERE name IN(' . substr(str_repeat('?,', count($label_names)), 0, -1) . ')');
			$stmt->execute(array_merge(array($id), $label_names));
		}

	}

	$score_l = $html('div.sr2_score_l');
	$score_m = $html('div.sr2_score_m');

	if(count($score_l) + count($score_m) != 3) {
		$dbh->rollBack();
		return false;
	}

	if(count($score_l) > 0) {
		$happy = $html('div.sr2_score_l', 0)->getPlainText();
		$ok = $html('div.sr2_score_m', 0)->getPlainText();
		$not_ok = $html('div.sr2_score_m', 1)->getPlainText();
	}
	else {
		$happy = $html('div.sr2_score_m', 0)->getPlainText();
		$ok = $html('div.sr2_score_m', 1)->getPlainText();
		$not_ok = $html('div.sr2_score_m', 2)->getPlainText();
	}

	$stmt = $dbh->prepare('UPDATE restaurant SET lat=?, lng=?, address=?, num_smile=?, num_ok=?, num_not_ok=? WHERE id=?');
	$stmt->execute(array($lat, $lng, $address, $happy, $ok, $not_ok, $id));

	$dbh->commit();

	return true;
}

function crawl_restaurant_review($id, $url) {
	global $dbh, $lang, $keywords;

	$orig_url = $url;

	// reviews
	if(endsWith($url, 'tc=MYOR') === true) {
		$curl = curl_create('http://www.openrice.com' . $url);
		$response = curl_execute($curl);

		if(preg_match('/^Location: (.+)$/mi', $response, $m)) {
			$url = $m[1];
			$stmt = $dbh->prepare('UPDATE restaurant SET url=? WHERE id=?');
			$stmt->execute(array($url, $id));
		}
	}

	$url = 'http://www.openrice.com' . substr($url, 0, strrpos($url, '/') + 1) . 'reviews/';

	$page = 1;
	while(true) {
		$curl = curl_create($url . $id . '?page=' . $page++);
		$response = curl_execute($curl);

		$html = str_get_dom($response);

		$review_blocks = $html('div.sr2_review_block');
		if(count($review_blocks) == 0) break;

		for($i = 0; $i < count($review_blocks); $i+=2) {

			$review_block = $review_blocks[$i];

			$review = $review_block('div.sr2_review_full', 0)->getPlainText();

			$day = $review_block('div.date_upper', 0)->getPlainText();

			$month = $review_block('div.date_lower', 0)->getPlainText();

			$a = $review_block('a.reviewer_name', 0);

			$dbh->beginTransaction();

			if($a) {

				$reviewer_name = $a->getPlainText();

				preg_match('/userid=([0-9]+)/', $a->href, $m);

				$reviewer_id = $m[1];

				$stmt = $dbh->prepare('INSERT IGNORE INTO user(id, name) VALUES(?, ?)');
				$stmt->execute(array($reviewer_id, $reviewer_name));

			}
			else {

				$reviewer_name = null;
				$reviewer_id = null;

			}

			$divs = $review_block('div[class="PL10 PR10"]');
			$scores = array(
				'states' => array( false,  false,  false,  false,  false),
				'scores' => array( '5',  '5',  '5',  '5',  '5')
			);

			foreach($divs as $div) {
				//hidden vis_hidden
				$div_vols = array(
					 $div('div.score_vol5')
					, $div('div.score_vol4')
					, $div('div.score_vol3')
					, $div('div.score_vol2')
					, $div('div.score_vol1')
				);

				for($j = 0; $j < count($div_vols); $j++) {

					for($k = 0; $k < count($div_vols[$j]); $k++) {

						if(stripos($div_vols[$j][$k]->class, 'vis_hidden') === false && !$scores['states'][$k]) {

							$scores['states'][$k] = true;
							$scores['scores'][$k] = 5 - $j;

						}
					}

				}

			}

			$faces = $review_block('h1.rel_pos');
			$face = '';
			foreach($faces as $face) {
				if(count($face('span.sprite-sr2-face-ok-review')) > 0)
					$face = 'ok';
				else if(count($face('span.sprite-sr2-face-smile-review')) > 0)
					$face = 'smile';
				else if(count($face('span.sprite-sr2-face-cry-review')) > 0)
					$face = 'cry';
				else
					$face = '';
			}


			$stmt = $dbh->prepare('INSERT INTO review(comment, date_posted) VALUES(?, ?)');
			$stmt->execute(array($review, date_create_from_format('j M y', $day . ' ' . $month)->format('Y-m-d') . ' 00:00:00'));

			$last_review_id = $dbh->lastInsertId();

			if($face != '') {
				$stmt = $dbh->prepare('INSERT INTO rating_explicit(`face`, `tas`, `dec`, `ser`, `hyg`, `val`) VALUES(?, ?, ?, ?, ?, ?)');
				$stmt->execute(array_merge(array($face), $scores['scores']));

				$last_erating_id = $dbh->lastInsertId();
			}

			if($reviewer_id !== null) {
				if($face != '')
					$dbh->query('INSERT INTO user_review(user_id, review_id, restaurant_id, rating_explicit_id) VALUES(' . $reviewer_id . ', ' . $last_review_id . ',' . $id . ' , ' . $last_erating_id . ')');
				else
					$dbh->query('INSERT INTO user_review(user_id, review_id, restaurant_id) VALUES(' . $reviewer_id . ', ' . $last_review_id . ',' . $id . ')');
			}
			else {
				if($face != '')
					$dbh->query('INSERT INTO user_review(review_id, restaurant_id, rating_explicit_id) VALUES(' . $last_review_id . ',' . $id . ' , ' . $last_erating_id . ')');
				else
					$dbh->query('INSERT INTO user_review(review_id, restaurant_id) VALUES(' . $last_review_id . ',' . $id . ')');
			}

			$dbh->commit();
		}

	}

	$dbh->query('UPDATE restaurant SET visited=1 WHERE id=' . $id);

	return true;
}

function crawl_user_detail($id) {
	if(!$id) return;

	global $dbh, $lang, $keywords;

	//get the users' love label

	$curl = curl_create('http://www.openrice.com/' . $lang . '/restaurant/userinfo.htm?userid=' . $id);
	$response = curl_execute($curl);
	if(!$response) return false;
	$dbh->beginTransaction();

	$html = str_get_dom($response);

	$ml25 = $html('span.sprite-myor_icon_cuisine + div.ML25');
	if(count($ml25) > 0) {
		$ml25 = $ml25[0];
		$as = $ml25('a');

		$labels = array();
		foreach($as as $a) {
			$label = $a->getPlainText();
			$labels[] = $label;

			$stmt = $dbh->prepare('INSERT IGNORE INTO label(name) VALUES(?)');
			$stmt->execute(array($label));
		}

		if(count($labels) > 0) {

			$stmt = $dbh->prepare('INSERT IGNORE INTO user_love(user_id, label_id) SELECT ?, id FROM label WHERE name IN(' . substr(str_repeat('?,', count($labels)), 0, -1) . ')');
			$stmt->execute(array_merge(array($id), $labels));
		}
	}

	$dbh->commit();

	//get the implicit ratings

	$cnt = 1;
	while(true) {
		$curl = curl_create('http://www.openrice.com/' . $lang . '/gourmet/bookmarkrestaurant.htm?userid=' . $id . '&city=hongkong&page=' . $cnt++);
		$response = curl_execute($curl);

		$html = str_get_dom($response);

		$lists = $html('div#poiListing');

		if(count($lists) == 0) break;
		$lists = $lists[0];

		$rs = $lists('div[class="poi col"]');

		if(count($rs) > 0) {

			foreach($rs as $r) {

				// restaurant info

				$rtag = $r('div.title > a', 0);
				preg_match('/shopid=([0-9]+)/', $rtag->href, $m);
				$rid = $m[1];

				$rname = $rtag->getPlainText();

				$dbh->beginTransaction();
				$stmt = $dbh->prepare('INSERT IGNORE INTO restaurant(id, name, url) VALUES(?, ?, ?)');
				$stmt->execute(array($rid, $rname, $rtag->href));

				// user -> restaurant label
				$labels = $r('span.bpTags a');
				foreach($labels as $label) {
					$txt = $label->getPlainText();

					if(stripos($txt, $keywords[$lang]['been_here']) !== false) {
						$dbh->query('INSERT IGNORE INTO rating_implicit(user_id, restaurant_id, type) VALUES(' . $id . ', ' . $rid . ', "been_here")');
					}
					else if(stripos($txt, $keywords[$lang]['bookmark']) !== false) {
						$dbh->query('INSERT IGNORE INTO rating_implicit(user_id, restaurant_id, type) VALUES(' . $id . ', ' . $rid . ', "bookmarked")');
					}
					else if(stripos($txt, $keywords[$lang]['wish_to_go']) !== false) {
						$dbh->query('INSERT IGNORE INTO rating_implicit(user_id, restaurant_id, type) VALUES(' . $id . ', ' . $rid . ', "wish_to_go")');
					}

				}
				$dbh->commit();

			}

		}
		else break;
	}

	//get directed social network

	//bookmarked user

	$cnt = 1;
	while(true) {

		$curl = curl_create('http://www.openrice.com/' . $lang . '/gourmet/bookmarkuser.htm?userid=' . $id . '&city=hongkong&page=' . $cnt++);
		$response = curl_execute($curl);

		$html = str_get_dom($response);

		$lists = $html('div#myor_main');

		if(count($lists) == 0) break;
		$lists = $lists[0];

		$us = $lists('div.myor_following_fans > div.MT10');

		if(count($us) > 0) {

			foreach($us as $u) {

				$ub = $u('div.user_block', 0);
				$a = $ub('a.avatar', 0);
				preg_match('/userid=([0-9]+)/', $a->href, $m);

				$uid = $m[1];

				$uimg = $ub('img', 0);
				$uname = $uimg->alt;

				$dbh->beginTransaction();
				$stmt = $dbh->prepare('INSERT IGNORE INTO user(id, name) VALUES(?, ?)');
				$stmt->execute(array($uid, $uname));

				$dbh->query('INSERT IGNORE INTO user_follow(followee_id, follower_id) VALUES(' . $uid . ', ' . $id . ')');
				$dbh->commit();
			}
		}
		else break;
	}

	//fans

	$cnt = 1;
	while(true) {

		$curl = curl_create('http://www.openrice.com/' . $lang . '/gourmet/bookmarkuserFans.htm?userid=' . $id . '&city=hongkong&page=' . $cnt++);
		$response = curl_execute($curl);

		$html = str_get_dom($response);

		$lists = $html('div#myor_main');

		if(count($lists) == 0) break;
		$lists = $lists[0];

		$us = $lists('div.myor_following_fans > div.MT10');

		if(count($us) > 0) {

			foreach($us as $u) {

				$ub = $u('div.user_block', 0);
				$a = $ub('a.avatar', 0);
				preg_match('/userid=([0-9]+)/', $a->href, $m);

				$uid = $m[1];

				$uimg = $ub('img', 0);
				$uname = $uimg->alt;

				$dbh->beginTransaction();
				$stmt = $dbh->prepare('INSERT IGNORE INTO user(id, name) VALUES(?, ?)');
				$stmt->execute(array($uid, $uname));

				$dbh->query('INSERT IGNORE INTO user_follow(followee_id, follower_id) VALUES(' . $id . ', ' . $uid . ')');
				$dbh->commit();

			}
		}
		else break;
	}

	//mark user as visited
	$dbh->query('UPDATE user SET visited=1 WHERE id=' . $id);

	

	return true;
}

start_crawl_restaurant_detail();
start_crawl_restaurant_review();
start_crawl_user_detail();
