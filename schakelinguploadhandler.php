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
session_start();

include_once('logincheck.inc.php');
//redirect if not logged in
if (!logincheck() || !($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
	http_response_code(401); //unauthorized
    exit;
}
//check if files
if (empty($_FILES['files']['name'])) {
	http_response_code(400); //bad request
    exit;
}

//include database gegevens
include('dbconnect.inc.php');

//controleer of er een schakeling is met het gegeven id
if (is_numeric($_GET['id'])) {
	$qry = "SELECT `id` FROM `".$db['prefix']."schakelingen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
	}
}
else {
	http_response_code(400); //bad request
    exit;
}

$files = array('files' => array());

header('Content-type: application/json');
//handle uploads
foreach ($_FILES["files"]["error"] as $key => $error) {
    $name = $_FILES["files"]["name"][$key];
	$tmp_name = $_FILES["files"]["tmp_name"][$key];
	$md5 = md5_file($tmp_name);
	$size = $_FILES["files"]["size"][$key];
	$filetype = strtolower(substr($name, strrpos($name, '.')));
	if ($error == UPLOAD_ERR_OK) {
        //check filesize
		if ($size >= 100*1024) {
			$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Bestand is te groot, maximumgrootte 100 kB');
		}
		//check filetype
		elseif (!in_array($filetype, array('.png'))) {
			$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Bestandstype niet toegestaan, enkel PNG-afbeeldingen');
		}
		else {
			//insert in database
			$qry = "INSERT INTO `".$db['prefix']."schakelingdrips` SET
			`schakeling_id` = '".$data['id']."',
			`afbeelding` = '".$md5.$filetype."'";
			//kijk of er een DRIP is met dezelfde naam als de gegeven bestandsnaam
			$qry2 = "SELECT `id` FROM `".$db['prefix']."drips`
			WHERE `name` LIKE '".mysqli_real_escape_string($db['link'], substr($name, 0, strrpos($name, '.')))."'
			ORDER BY `status`
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry2);
			$overwrite = FALSE;
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				//kijk of deze drip al in de schakeling zit
				$qry2 = "SELECT `id`, `afbeelding` FROM `".$db['prefix']."schakelingdrips`
				WHERE `schakeling_id`  = '".$data['id']."'
				AND `drip_id` = '".$row[0]."'
				LIMIT 1";
				$res2 = mysqli_query($db['link'], $qry2);
				//nog geen DRIP met deze naam, toevoegen
				if (mysqli_num_rows($res2) == 0) {
					$qry .= ", `drip_id` = '".$row[0]."'";
				}
				//wel al een drip met deze naam, en overschrijven staat aan
				elseif ($_GET['ow'] == 'true') {
					$row2 = mysqli_fetch_row($res2);
					//nieuwe query om drip te overschrijven
					$qry = "UPDATE `".$db['prefix']."schakelingdrips` SET
					`afbeelding` = '".$md5.$filetype."'
					WHERE `id` = '".$row2[0]."'";
					$overwrite = TRUE;
				}
			}
			mysqli_query($db['link'], $qry);
			$target_file = 'store/'.strtoupper(substr($md5, 0, 1)).'/'.$md5.$filetype;
			if (!file_exists($target_file)) {
				if (!empty($temp_file)) {
					//move temp file
					rename($temp_file, $target_file);
					unset($temp_file);
				}
				else {
					//move original file
					move_uploaded_file($tmp_name, $target_file);
				}
			}
			//wanneer overschreven, ruim oude afbeelding op
			if ($overwrite === TRUE) {
				//verwijder in onbruik geraakte afbeeldingen
				include_once('afbeeldingverwijderen.fct.php');
				afbeelding_verwijderen($row2[1]);
			}
			$files['files'][] = array('name' => $name, 'size' => $size, 'url' => $target_file);
		}
    }
	else {
		$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Kan bestand niet opslaan');
	}
}

echo json_encode($files, JSON_FORCE_OBJECT);
?>
