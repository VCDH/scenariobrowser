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

$qry = "DROP TABLE IF EXISTS `".$db['prefix']."hectopunten`";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."hectopunten` (
`lat` DOUBLE NOT NULL,
`lon` DOUBLE NOT NULL,
`hecto` INT NOT NULL,
`wegnummer` VARCHAR(5) NOT NULL,
`letter` VARCHAR(1) NULL,
`rpe` VARCHAR(1) NULL,
`bst` VARCHAR(3) NULL,
`pos_wol` VARCHAR(1) NOT NULL,
PRIMARY KEY (`hecto`, `wegnummer`, `letter`, `pos_wol`)
)
CHARACTER SET 'latin1' 
COLLATE 'latin1_general_ci' 
ENGINE=MyISAM";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "LOAD DATA LOCAL INFILE 'hectopunten.csv'
INTO TABLE `".$db['prefix']."hectopunten` 
FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\\\'
IGNORE 1 LINES
(
`lon`,
`lat`,
`hecto`,
`wegnummer`,
`letter`,
`rpe`,
`bst`,
`pos_wol`
)";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "UPDATE `".$db['prefix']."hectopunten` 
SET `wegnummer` = CONCAT('A', `wegnummer`) WHERE SUBSTR(`wegnummer`, 1, 1) NOT IN ('A', 'N')";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "UPDATE `".$db['prefix']."hectopunten` 
SET `letter` = NULL WHERE `letter` = '#'";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

$qry = "UPDATE `".$db['prefix']."hectopunten` 
SET `rpe` = NULL WHERE `rpe` = '#'";
mysqli_query($db['link'], $qry);
echo mysqli_error($db['link']);

//converteer A > N
//volledige wegen
$convert = array('3', '11', '14', '33', '36', '46', '48', '57', '61', '99');

foreach ($convert as $road) {
	$qry = "UPDATE `".$db['prefix']."hectopunten` 
	SET `wegnummer` = 'N".$road."' 
	WHERE `wegnummer` = 'A".$road."'";
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);
}

//gedeelten van wegen
$convert = array(
'31' => array(0, 169),
'31' => array(423, 770),
'44' => array(217, 280),
'7' => array(1180, 1230),
'7' => array(1955, 2049),
'9' => array(749, 1140),
'15' => array(161, 256),
'18' => array(0, 160),
'18' => array(2140, 2434),
'32' => array(696, 720),
'35' => array(409, 424),
'35' => array(713, 765),
'50' => array(2390, 2636),
'59' => array(95, 590),
'65' => array(49, 174),
'200' => array(0, 69)
);

foreach ($convert as $road => $bounds) {
	$qry = "UPDATE `".$db['prefix']."hectopunten` 
	SET `wegnummer` = 'N".$road."' 
	WHERE `wegnummer` = 'A".$road."'
	AND
	`hecto` BETWEEN ".$bounds[0]." AND ".$bounds[1];
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);
}

//planvervangende wegen
$convert = array('708', '769', '772', '773', '783', '834', '835', '838', '915');

foreach ($convert as $road) {
	$qry = "UPDATE `".$db['prefix']."hectopunten` 
	SET `wegnummer` = 'RW".$road."' 
	WHERE `wegnummer` = 'A".$road."'";
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);
}

?>