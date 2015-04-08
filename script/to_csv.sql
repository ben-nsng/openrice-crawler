-- (Kowloon) -- lat BETWEEN 22.311962 AND 22.324885 AND lng BETWEEN 114.166200 AND 114.173131
-- (Hong Kong Island) -- lat BETWEEN 22.269781 AND 22.292178 AND lng BETWEEN 114.105757 AND 114.256648

USE `or`;
SET NAMES utf8;

-- Clear Data
DELETE FROM bi_user;
DELETE FROM bi_restaurant;

-- INSERT require ids
INSERT INTO bi_restaurant(id)
SELECT id FROM restaurant
WHERE lat BETWEEN 22.269781 AND 22.292178 AND lng BETWEEN 114.105757 AND 114.256648;

INSERT INTO bi_user(id)
SELECT DISTINCT user_id FROM (
	SELECT DISTINCT user_id FROM rating_implicit
	WHERE EXISTS (
		SELECT 1 FROM bi_restaurant
		WHERE restaurant_id=bi_restaurant.id
	)
	UNION
	SELECT DISTINCT user_id FROM user_review
	WHERE EXISTS (
		SELECT 1 FROM bi_restaurant
		WHERE restaurant_id=bi_restaurant.id
	)
) T;

-- LABEL INFO
SELECT id+100000000, name FROM label
INTO OUTFILE '/tmp/label.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- RESTAURANT INFO
SELECT restaurant.id+200000000, `name`, `url`, `lat`, `lng`, `address`, `num_smile`, `num_ok`, `num_not_ok` FROM restaurant
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_restaurant
-- 	WHERE bi_restaurant.id=restaurant.id
-- )
INNER JOIN bi_restaurant ON bi_restaurant.id=restaurant.id
INTO OUTFILE '/tmp/restaurant.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- USER INFO
SELECT user.id+300000000,name FROM user
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_user
-- 	WHERE bi_user.id=user.id
-- )
INNER JOIN bi_user ON bi_user.id=user.id
INTO OUTFILE '/tmp/user.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';


-- RESTAURANT - LABEL
SELECT
	restaurant_id+200000000, label_id+100000000, restaurant.name, label.name
FROM label_restaurant
INNER JOIN restaurant ON restaurant_id=restaurant.id
INNER JOIN label ON label_id=label.id
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_restaurant
-- 	WHERE bi_restaurant.id=restaurant_id
-- )
INNER JOIN bi_restaurant ON bi_restaurant.id=restaurant.id
INTO OUTFILE '/tmp/label_restaurant.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- USER - RESTAURANT
SELECT user_id+300000000,restaurant_id+200000000,type FROM rating_implicit
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_restaurant
-- 	WHERE bi_restaurant.id=restaurant_id
-- )
INNER JOIN bi_restaurant ON bi_restaurant.id=restaurant_id
INTO OUTFILE '/tmp/rating_bookmark.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- USER - USER
SELECT
	followee_id+300000000,follower_id+300000000, u1.name, u2.name
FROM user_follow
INNER JOIN user AS u1 ON u1.id=followee_id
INNER JOIN user AS u2 ON u2.id=follower_id
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_user
-- 	WHERE bi_user.id=u1.id
-- )
-- OR EXISTS (
-- 	SELECT 1 FROM bi_user
-- 	WHERE bi_user.id=u2.id
-- )
INNER JOIN bi_user ON bi_user.id=followee_id OR bi_user.id=follower_id
INTO OUTFILE '/tmp/user_follow.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- USER - LABEL
SELECT
	user_id+300000000,label_id+100000000, user.name, label.name
FROM user_love
INNER JOIN user ON user_id=user.id
INNER JOIN label ON label_id=label.id
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_user
-- 	WHERE bi_user.id=user_id
-- )
INNER JOIN bi_user ON bi_user.id=user_id
INTO OUTFILE '/tmp/user_love.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n';

-- USER - RESTAURANT
SELECT 
	COALESCE(user_id+300000000, ''), `review_id`, restaurant_id+200000000, COALESCE(`rating_explicit_id`, ''),
	COALESCE(user.name, ''),
	REPLACE(review.comment, CHAR(0 USING ASCII), ''),review.date_posted,
	restaurant.`name`, `url`, `lat`, `lng`, `address`, `num_smile`, `num_ok`, `num_not_ok`,
	COALESCE(`face`, ''), COALESCE(`tas`, ''), COALESCE(`dec`, ''), COALESCE(`ser`, ''), COALESCE(`hyg`, ''), COALESCE(`val`, '')
FROM user_review
LEFT JOIN user ON user_review.user_id=user.id
LEFT JOIN review ON user_review.review_id=review.id
LEFT JOIN restaurant ON user_review.restaurant_id=restaurant.id
LEFT JOIN rating_explicit ON user_review.rating_explicit_id=rating_explicit.id
-- WHERE EXISTS (
-- 	SELECT 1 FROM bi_restaurant
-- 	WHERE bi_restaurant.id=restaurant_id
-- )
INNER JOIN bi_restaurant ON bi_restaurant.id=restaurant_id
INTO OUTFILE '/tmp/user_review_full.csv' FIELDS ESCAPED BY '"' TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\n';
