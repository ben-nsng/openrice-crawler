SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `bi_restaurant` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `bi_user` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `label` (
`id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=128443 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `label_restaurant` (
  `restaurant_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rating_explicit` (
`id` int(11) NOT NULL,
  `face` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tas` int(11) DEFAULT NULL,
  `dec` int(11) DEFAULT NULL,
  `ser` int(11) DEFAULT NULL,
  `hyg` int(11) DEFAULT NULL,
  `val` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=777290 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rating_implicit` (
  `user_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant` (
`id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(1023) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lat` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `lng` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(1023) COLLATE utf8_unicode_ci NOT NULL,
  `num_wish` int(11) NOT NULL,
  `num_been` int(11) NOT NULL,
  `num_bookmarked` int(11) NOT NULL,
  `num_smile` int(11) NOT NULL,
  `num_ok` int(11) NOT NULL,
  `num_not_ok` int(11) NOT NULL,
  `visited` tinyint(4) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=444879 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `review` (
`id` int(11) NOT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `date_posted` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=807054 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `visited` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_follow` (
  `followee_id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_love` (
  `user_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_review` (
  `user_id` int(11) DEFAULT NULL,
  `review_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `rating_explicit_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `label`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name_UNIQUE` (`name`);

ALTER TABLE `label_restaurant`
 ADD PRIMARY KEY (`restaurant_id`,`label_id`), ADD KEY `fk_label_restaurant_label1_idx` (`label_id`);

ALTER TABLE `rating_explicit`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `rating_implicit`
 ADD UNIQUE KEY `index3` (`user_id`,`restaurant_id`,`type`), ADD KEY `fk_ratings_implicit_user1_idx` (`user_id`), ADD KEY `fk_ratings_implicit_restaurant1_idx` (`restaurant_id`);

ALTER TABLE `restaurant`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `review`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `user`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `user_follow`
 ADD PRIMARY KEY (`followee_id`,`follower_id`), ADD KEY `fk_user_follow_user2_idx` (`follower_id`);

ALTER TABLE `user_love`
 ADD PRIMARY KEY (`user_id`,`label_id`), ADD KEY `fk_user_love_label1_idx` (`label_id`);

ALTER TABLE `user_review`
 ADD PRIMARY KEY (`review_id`,`restaurant_id`), ADD KEY `fk_user_review_review1_idx` (`review_id`), ADD KEY `fk_user_review_restaurant1_idx` (`restaurant_id`), ADD KEY `fk_user_review_rating_explicit1_idx` (`rating_explicit_id`), ADD KEY `fk_user_review_user1_idx` (`user_id`);


ALTER TABLE `label`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=128443;
ALTER TABLE `rating_explicit`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=777290;
ALTER TABLE `restaurant`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=444879;
ALTER TABLE `review`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=807054;

ALTER TABLE `label_restaurant`
ADD CONSTRAINT `fk_label_restaurant_label1` FOREIGN KEY (`label_id`) REFERENCES `label` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_label_restaurant_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `rating_implicit`
ADD CONSTRAINT `fk_ratings_implicit_restaurant1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_ratings_implicit_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `user_follow`
ADD CONSTRAINT `fk_user_follow_user1` FOREIGN KEY (`followee_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_follow_user2` FOREIGN KEY (`follower_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `user_love`
ADD CONSTRAINT `fk_user_love_label1` FOREIGN KEY (`label_id`) REFERENCES `label` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_love_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `user_review`
ADD CONSTRAINT `fk_user_review_rating_explicit1a` FOREIGN KEY (`rating_explicit_id`) REFERENCES `rating_explicit` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_restaurant1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_restaurant1a` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_review1` FOREIGN KEY (`review_id`) REFERENCES `review` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_review1a` FOREIGN KEY (`review_id`) REFERENCES `review` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_review_user1a` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;