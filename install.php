<?php
/*
 	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
    Copyright (C) 2016-2019 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
include('dbconnect.inc.php');

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password']);

$qry = "CREATE DATABASE IF NOT EXISTS `".$db['database']."`
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci'";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password'], $db['database']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."wegvakken` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`naam` TINYTEXT NOT NULL,
`weg` VARCHAR(64) NULL,
`ri` VARCHAR(64) NULL,
`km_van` FLOAT UNSIGNED NULL,
`km_tot` FLOAT UNSIGNED NULL,
`coords` TEXT NOT NULL,
`lat_min` TINYTEXT NOT NULL,
`lat_max` TINYTEXT NOT NULL,
`lon_min` TINYTEXT NOT NULL,
`lon_max` TINYTEXT NOT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."scenariomappen` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
`naam` TINYTEXT NOT NULL,
`omschrijving` TINYTEXT NOT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."scenarios` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`map_id` INT UNSIGNED NOT NULL,
`organisatie_id` INT UNSIGNED NOT NULL DEFAULT '1',
`naam` TINYTEXT NOT NULL,
`type` VARCHAR(1) NOT NULL,
`ernst` VARCHAR(1) NOT NULL,
`archief` BOOLEAN NOT NULL DEFAULT TRUE,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."wegvakmapping` (
`wegvak_id` INT UNSIGNED NOT NULL,
`scenario_id` INT UNSIGNED NOT NULL,
PRIMARY KEY (`wegvak_id`, `scenario_id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."omleidingmapping` (
`wegvak_id` INT UNSIGNED NOT NULL,
`schakeling_id` INT UNSIGNED NOT NULL,
PRIMARY KEY (`wegvak_id`, `schakeling_id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."scenarioversies` (
`scenario_id` INT UNSIGNED NOT NULL,
`versie` INT UNSIGNED NOT NULL,
`datum` DATE NOT NULL,
`user_id` INT UNSIGNED NOT NULL,
`wijzigingen` TINYTEXT NULL,
`omschrijving` TEXT NULL,
`concept` BOOLEAN NOT NULL DEFAULT TRUE,
PRIMARY KEY (`scenario_id`, `versie`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."schakelingen` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`scenario_id` INT UNSIGNED NOT NULL,
`scenarioversie` INT UNSIGNED NOT NULL,
`naam` TINYTEXT NOT NULL,
`omschrijving` TEXT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."schakelingdrips` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`schakeling_id` INT UNSIGNED NOT NULL,
`drip_id` INT UNSIGNED NULL,
`afbeelding` TINYTEXT NOT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."scenariobijlagen` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`scenario_id` INT UNSIGNED NOT NULL,
`datum` DATETIME NOT NULL,
`user_id` INT UNSIGNED NOT NULL,
`bestandsnaam` TINYTEXT NOT NULL,
`grootte` INT NOT NULL,
`bestand` VARCHAR(40) NOT NULL,
`toegang` INT(1) NOT NULL DEFAULT 0,
`archief` BOOLEAN NOT NULL DEFAULT FALSE,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."drips` (
`id` INT UNSIGNED NOT NULL,
`latitude` DOUBLE NOT NULL DEFAULT 52,
`longitude` DOUBLE NOT NULL DEFAULT 4,
`rotation` INT(3) UNSIGNED NOT NULL DEFAULT 0,
`name` TINYTEXT NOT NULL,
`comment` TEXT NULL,
`road` TINYTEXT NULL,
`direction` VARCHAR(1) NULL,
`position` TINYTEXT NULL,
`code` TINYTEXT NULL,
`type` TINYTEXT NULL,
`status` TINYTEXT NOT NULL,
`owner` TINYTEXT NOT NULL,
`defaulttext` TEXT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."dripstemp` (
`id` INT UNSIGNED NOT NULL,
`latitude` DOUBLE NOT NULL DEFAULT 52,
`longitude` DOUBLE NOT NULL DEFAULT 4,
`rotation` INT(3) UNSIGNED NOT NULL DEFAULT 0,
`name` TINYTEXT NOT NULL,
`comment` TEXT NULL,
`road` TINYTEXT NULL,
`direction` VARCHAR(1) NULL,
`position` TINYTEXT NULL,
`code` TINYTEXT NULL,
`type` TINYTEXT NULL,
`status` TINYTEXT NOT NULL,
`owner` TINYTEXT NOT NULL,
`defaulttext` TEXT NULL,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."users` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE KEY,
`email` VARCHAR(255) NOT NULL,
`password` VARCHAR(64) NOT NULL,
`token` MEDIUMTEXT,
`name` TINYTEXT,
`phone` TINYTEXT NULL DEFAULT NULL,
`organisation` INT UNSIGNED NOT NULL,
`accesslevel` TINYINT UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`email`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."organisations` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`email` VARCHAR(255) NOT NULL,
`name` VARCHAR(64) NOT NULL,
`afdeling` TINYTEXT NULL,
`emailadres` VARCHAR(255) NULL,
`telefoon` TINYTEXT NULL,
`allowsignup` BOOLEAN NOT NULL DEFAULT TRUE,
PRIMARY KEY (`id`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

//create store
if (!is_dir('store')) {
	mkdir('store');
	$subdirs = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	foreach ($subdirs as $subdir) {
		mkdir('store/'.$subdir);
	}
	echo 'created store directories'.PHP_EOL;
}

//create store
if (!is_dir('attachments')) {
	mkdir('attachments');
	$subdirs = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	foreach ($subdirs as $subdir) {
		mkdir('attachments/'.$subdir);
	}
	echo 'created attachments directories'.PHP_EOL;
}
file_put_contents('attachments/.htaccess', 'deny from all');

?>