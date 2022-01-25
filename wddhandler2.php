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


//header('Access-Control-Allow-Origin: http://wdd.s200.nl');

session_start();

include_once('logincheck.inc.php');
//redirect if not logged in
if (!logincheck()) {
	echo '<p>Niet aangemeld.</p>';
    exit;
}
//check user rights
elseif (!($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
	echo '<p>Onvoldoende rechten om handeling uit te voeren.</p>';
    exit;
}

//check if files
if (!preg_match('/([0-9A-Z]){32}/i', $_GET['image'])) {
	echo '<p>Ongeldige aanvraag</p>';
    exit;
}
$md5 = $_GET['image'];

//haal afbeelding op
$source_file = 'http://wdd.s200.nl/store/'.substr($md5, 0, 1).'/'.$md5.'.png';
if (!$image = file_get_contents($source_file)) {
	var_dump($image);
	echo '<p>Geen afbeelding beschikbaar: ';
	echo $source_file.'</p>';
	exit;
}

//include database gegevens
include('dbconnect.inc.php');

if ($_GET['do'] == 'new') {
	//controleer of er een schakeling is met het gegeven id
	$qry = "SELECT `id` FROM `".$db['prefix']."schakelingen` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_row($res);
		$schakeling_id = $data[0];
	}
	else {
		echo '<p>Kan aanvraag niet verwerken: geen schakeling met opgegeven ID.</p>';
		exit;
	}
	//controleer of er een drip is met het gegeven id
	$qry = "SELECT `id` FROM `".$db['prefix']."drips` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['drip_id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_row($res);
		$drip_id = $data[0];
	}
	else {
		echo '<p>Kan aanvraag niet verwerken: geen DRIP met opgegeven ID.</p>';
		exit;
	}
	
	//sla afbeelding op
	$target_file = 'store/'.strtoupper(substr($md5, 0, 1)).'/'.$md5.'.png';
	if (!file_exists($target_file)) {
		file_put_contents($target_file, $image);
	}
	//sla op in database
	$overwrite = TRUE;
	if ($_GET['ow'] == 'true') {
		//overschrijven actief
		$qry = "SELECT `id`, `afbeelding` FROM `".$db['prefix']."schakelingdrips` 
		WHERE `schakeling_id` = '".$schakeling_id."'
		AND `drip_id` = '".$drip_id."'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			//overschrijf
			$qry = "UPDATE `".$db['prefix']."schakelingdrips` 
			SET 
			`afbeelding` = '".$md5.'.png'."'
			WHERE `id` = '".$row[0]."'";
			mysqli_query($db['link'], $qry);
			//verwijder in onbruik geraakte afbeeldingen
			include_once('afbeeldingverwijderen.fct.php');
			afbeelding_verwijderen($row[1]);
		}
		else {
			$overwrite = FALSE;
		}
	}
	else {
		$overwrite = FALSE;
	}
	//insert new
	if ($overwrite == FALSE) {
		$qry = "INSERT INTO `".$db['prefix']."schakelingdrips` 
			SET 
			`schakeling_id` = '".$schakeling_id."',
			`drip_id` = '".$drip_id."',
			`afbeelding` = '".$md5.'.png'."'";
			mysqli_query($db['link'], $qry);
	}
	echo 'OK';
	exit;
}
elseif ($_GET['do'] == 'edit') {
	//controleer of er een schakelingdrip is met het gegeven id
	$qry = "SELECT `id`, `afbeelding` FROM `".$db['prefix']."schakelingdrips` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		$id = $row[0];
	}
	else {
		echo '<p>Kan aanvraag niet verwerken: geen DRIP met opgegeven ID.</p>';
		exit;
	}
	
	//sla afbeelding op
	$target_file = 'store/'.strtoupper(substr($md5, 0, 1)).'/'.$md5.'.png';
	if (!file_exists($target_file)) {
		file_put_contents($target_file, $image);
	}
	//sla op in database
	$qry = "UPDATE `".$db['prefix']."schakelingdrips` 
	SET 
	`afbeelding` = '".$md5.'.png'."'
	WHERE `id` = '".$id."'";
	mysqli_query($db['link'], $qry);
	//verwijder in onbruik geraakte afbeeldingen
	include_once('afbeeldingverwijderen.fct.php');
	afbeelding_verwijderen($row[1]);
	echo 'OK';
	exit;
}
?>