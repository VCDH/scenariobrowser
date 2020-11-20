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

exit;

include('dbconnect.inc.php');

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password'], $db['database']);

$vannaar = array(
	719 => 628,
	718 => 627,
	715 => 624,
	714 => 623,
	713 => 622,
	426 => 427,
	397 => 399,
	394 => 396,
	371 => 373,
	370 => 372,
	369 => 371,
	368 => 366,
	367 => 370,
	311 => 313,
	309 => 311,
	308 => 310,
	284 => 286,
	201 => 202,
	198 => 199,
	188 => 189,
	187 => 188,
	186 => 187
);

/*
drips.id
schakelingdrips.drip_id
*/

foreach ($vannaar as $van => $naar) {
	$qry = "DELETE FROM `".$db['prefix']."drips`
	WHERE `id` = '".$naar."'";
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);

	$qry = "UPDATE `".$db['prefix']."drips`
	SET `id` = '".$naar."'
	WHERE `id` = '".$van."'";
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);
	
	$qry = "UPDATE `".$db['prefix']."schakelingdrips`
	SET `drip_id` = '".$naar."'
	WHERE `drip_id` = '".$van."'";
	mysqli_query($db['link'], $qry);
	echo mysqli_error($db['link']);
}
echo 'klaar';
?>