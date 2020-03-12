<?php 
/*
 	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
    Copyright (C) 2016-2020 Gemeente Den Haag, Netherlands
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
/*
if (!logincheck()) {
	header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
    exit;
}
*/
//include database gegevens
include('dbconnect.inc.php');

include('scenariotype.cfg.php');

//controleer of gegeven id bestaat
$qry = "SELECT `id`, `naam`, `type`, `map_id`, `ernst`, `archief`, `organisatie_id` FROM `".$db['prefix']."scenarios`
WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
	$scenario_data = mysqli_fetch_array($res);
}

if (($_GET['do'] == 'edit') && (permissioncheck('scenario_bewerken', $scenario_data['organisatie_id']) || (empty($_GET['id']) && permissioncheck('scenario_bewerken')))) {
	$wegvakken = array();
	//load data
	if (is_numeric($_GET['id'])) {
		$qry = "SELECT * FROM `".$db['prefix']."scenarios` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data = mysqli_fetch_assoc($res);
			//get wegvakken
			$qry = "SELECT `wegvak_id` FROM `".$db['prefix']."wegvakmapping`
			WHERE `scenario_id` = '".$data['id']."'";
			$res = mysqli_query($db['link'], $qry);
			while ($row = mysqli_fetch_row($res)) {
				$wegvakken[] = $row[0];
			}
			//get versie
			$qry = "SELECT `versie`, `wijzigingen`, `omschrijving`, `concept` FROM `".$db['prefix']."scenarioversies`
			WHERE `scenario_id` = '".$data['id']."'
			ORDER BY `versie` DESC
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				if ($row[3] == 1) {
					$data['versie'] = $row[0];
				}
				else {
					$data['versie'] = $row[0] + 1;
				}
				$data['wijzigingen'] = $row[1];
				$data['omschrijving'] = $row[2];
				$data['concept'] = $row[3];
			}
			else {
				$data['versie'] = 1;
			}
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
			exit;
		}
	}
	//nieuwe toevoegen
	else {
		//bepaal standaardmap en controleer of deze bestaat
		if (is_numeric($_GET['folder'])) {
			$folder_id = $_GET['folder'];
		}
		else {
			$folder_id = $_SESSION['folder_id'];
		}
		$qry = "SELECT `id` FROM `".$db['prefix']."scenariomappen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $folder_id)."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data['map_id'] = mysqli_fetch_row($res);
			$data['map_id'] = $data['map_id'][0];
		}
		else {
			$data['map_id'] = 0;
		}
		//zet eerste versie
		$data['versie'] = 1;
		//als wegvak gegeven, controleer of deze bestaat
		$qry = "SELECT `id` FROM `".$db['prefix']."wegvakken`
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['wegvak_id'])."'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		while ($row = mysqli_fetch_row($res)) {
			$wegvakken[] = $row[0];
		}
	}
	
	//check post data
	if (!empty($_POST)) {
		$fieldcheck = TRUE;
		//check fields
		if (empty($_POST['naam'])) $fieldcheck = FALSE;
		if (!array_key_exists($_POST['type'], $scenario_types)) $fieldcheck = FALSE;
		if (!array_key_exists($_POST['ernst'], $scenario_ernsten)) $fieldcheck = FALSE;
		//map id
		if ($_POST['map_id'] == 0) {
			$data['map_id'] = 0;
		}
			else {
			$qry = "SELECT `id` FROM `".$db['prefix']."scenariomappen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_POST['map_id'])."'";
			$res = mysqli_query($db['link'], $qry);
			if (mysqli_num_rows($res)) {
				$data['map_id'] = mysqli_fetch_row($res);
				$data['map_id'] = $data['map_id'][0];
			}
			else {
				$fieldcheck = FALSE;
			}
		}
		//check segments
		$wegvakken = array();
		if (!empty($_POST['wegvakken'])) {
			$temp_wegvakken = explode(',', $_POST['wegvakken']);
			foreach ($temp_wegvakken as $wegvak_this) {
				if (is_numeric($wegvak_this)) {
					$wegvakken[] = $wegvak_this;
				}
				else {
					$fieldcheck = FALSE;
				}
			}
		}
		//save data
		if ($fieldcheck == TRUE) {
			//update existing
			if (is_numeric($data['id'])) {
				//update entry
				$qry = "UPDATE `".$db['prefix']."scenarios` SET
				`map_id` = '".$data['map_id']."',
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`type` = '".mysqli_real_escape_string($db['link'], $_POST['type'])."',
				`ernst` = '".mysqli_real_escape_string($db['link'], $_POST['ernst'])."'
				WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
				mysqli_query($db['link'], $qry);
			}
			//insert new
			else {
				//bepaal eigen organisatie id
				$qry = "SELECT `organisation` FROM `".$db['prefix']."users` WHERE
				`id` = '".$_SESSION['id']."'";
				$res = mysqli_query($db['link'], $qry);
				$row = mysqli_fetch_row($res);
				//sla op
				$qry = "INSERT IGNORE INTO `".$db['prefix']."scenarios` SET
				`map_id` = '".$data['map_id']."',
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`type` = '".mysqli_real_escape_string($db['link'], $_POST['type'])."',
				`ernst` = '".mysqli_real_escape_string($db['link'], $_POST['ernst'])."',
				`organisatie_id` = '".mysqli_real_escape_string($db['link'], $row[0])."'";
				mysqli_query($db['link'], $qry);
				$data['id'] = mysqli_insert_id($db['link']);
				//scenario version table
				$qry = "INSERT INTO `".$db['prefix']."scenarioversies` SET
				`scenario_id` = '".$data['id']."',
				`versie` =  '".$data['versie']."',
				`datum` = CURDATE(),
				`user_id` = '".$_SESSION['id']."'";
				mysqli_query($db['link'], $qry);
			}
			
			//set segment mapping
			//remove
			if (empty($wegvakken)) {
				$qry = "DELETE FROM `".$db['prefix']."wegvakmapping` 
				WHERE `scenario_id` = '".$data['id']."'";
			}
			else {
				$qry = "DELETE FROM `".$db['prefix']."wegvakmapping` 
				WHERE `scenario_id` = '".$data['id']."'
				AND `wegvak_id` NOT IN (".join(',', $wegvakken).")";
			}
			mysqli_query($db['link'], $qry);
			//add
			foreach($wegvakken as $wegvak_id) {
				$qry = "INSERT IGNORE INTO `".$db['prefix']."wegvakmapping` SET
				`scenario_id` = '".$data['id']."',
				`wegvak_id` = '".$wegvak_id."'";
				mysqli_query($db['link'], $qry);
				echo mysqli_error($db['link']);
			}
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['id']);
			exit;
		}
		else {
			//overload post data
			foreach ($_POST as $k => $v) {
				if (($k != 'id') && ($k != 'naam')) {
					$data[$k] = $v;
				}
			}
		}
	}
}

elseif (($_GET['do'] == 'description') && permissioncheck('scenario_bewerken')) {
	//load data
	$qry = "SELECT * FROM `".$db['prefix']."scenarios` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		//get versie
		$qry = "SELECT `versie`, `omschrijving` FROM `".$db['prefix']."scenarioversies`
		WHERE `scenario_id` = '".$data['id']."'
		AND `concept` = 1
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$data['versie'] = $row[0];
			$data['omschrijving'] = $row[1];
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
			exit;
		}
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
		exit;
	}
	
	//save data
	if (!empty($_POST)) {
		
		//scenario version table
		$qry = "UPDATE `".$db['prefix']."scenarioversies` SET
		`datum` = CURDATE(),
		`user_id` = '".$_SESSION['id']."',
		`omschrijving` = '".mysqli_real_escape_string($db['link'], $_POST['omschrijving'])."'
		WHERE `scenario_id` = '".$data['id']."'
		AND `versie` =  '".$data['versie']."'";
		mysqli_query($db['link'], $qry);
		
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['id']);
		exit;
	}
}

elseif (($_GET['do'] == 'newconcept') && permissioncheck('scenario_bewerken')) {
	//load data
	$qry = "SELECT * FROM `".$db['prefix']."scenarios` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		//controleer of er niet al een concept is
		$qry = "SELECT `versie` FROM `".$db['prefix']."scenarioversies`
		WHERE `scenario_id` = '".$data['id']."'
		AND `concept` = 1
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['id']);
			exit;
		}
		else {
			//get versie
			$qry = "SELECT `versie`, `omschrijving` FROM `".$db['prefix']."scenarioversies`
			WHERE `scenario_id` = '".$data['id']."'
			ORDER BY `versie` DESC
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$data['versie'] = $row[0] + 1;
				$data['omschrijving'] = $row[1];
			}
			else {
				$data['versie'] = 1;
			}
		}
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
		exit;
	}
	
	//check post data
	if (!empty($_POST)) {
		//save data
		//scenario version table
		$qry = "INSERT INTO `".$db['prefix']."scenarioversies` SET
		`scenario_id` = '".$data['id']."',
		`versie` =  '".$data['versie']."',
		`datum` = CURDATE(),
		`user_id` = '".$_SESSION['id']."',
		`omschrijving` = '".mysqli_real_escape_string($db['link'], $data['omschrijving'])."',
		`concept` = 1";
		mysqli_query($db['link'], $qry);
		//kopieer alle schakelingen, omleidingmapping en schakelingdrips, als deze er zijn
		//vraag schakelingen op
		$qry = "SELECT `id`, `scenario_id`, `scenarioversie`, `naam`, `omschrijving`
		FROM `".$db['prefix']."schakelingen`
		WHERE `scenario_id` = '".$data['id']."'
		AND `scenarioversie` = '".($data['versie'] - 1)."'";
		$res = mysqli_query($db['link'], $qry);
		while ($row = mysqli_fetch_row($res)) {
			//maak kopie van schakeling
			$qry = "INSERT INTO `".$db['prefix']."schakelingen` SET
			`scenario_id` = '".$row[1]."', 
			`scenarioversie` = '".$data['versie']."', 
			`naam` = '".mysqli_real_escape_string($db['link'], $row[3])."', 
			`omschrijving` = '".mysqli_real_escape_string($db['link'], $row[4])."'";
			mysqli_query($db['link'], $qry);
			$schakeling_id = mysqli_insert_id($db['link']);
			//kopier omleidingmapping
			$qry = "INSERT INTO `".$db['prefix']."omleidingmapping` 
			(
			`wegvak_id`,
			`schakeling_id`
			)
			SELECT
			`wegvak_id`,
			'".$schakeling_id."'
			FROM `".$db['prefix']."omleidingmapping` 
			WHERE `schakeling_id` = '".$row[0]."'";
			mysqli_query($db['link'], $qry);
			//maak kopie van DRIPs
			$qry = "INSERT INTO `".$db['prefix']."schakelingdrips` 
			(
			`schakeling_id`,
			`drip_id`,
			`afbeelding`
			)
			SELECT
			'".$schakeling_id."',
			`drip_id`,
			`afbeelding`
			FROM `".$db['prefix']."schakelingdrips` 
			WHERE `schakeling_id` = '".$row[0]."'";
			mysqli_query($db['link'], $qry);
		}
		
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['id']);
		exit;
	}
}

elseif (($_GET['do'] == 'transferownership') && permissioncheck('scenario_overdragen', $scenario_data['organisatie_id'])) {
	//load data
	$qry = "SELECT `id`, `naam`, `organisatie_id` FROM `".$db['prefix']."scenarios` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		$qry = "UPDATE `".$db['prefix']."scenarios` SET
		`organisatie_id` = '".mysqli_real_escape_string($db['link'], $_POST['organisatie'])."'
		WHERE `id` = '".$_GET['id']."'";
		mysqli_query($db['link'], $qry);
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['id']);
		exit;
	}
}

elseif (($_GET['do'] == 'changelog') && permissioncheck('scenario_bewerken')) {
	//load data
	$qry = "SELECT `scenario_id` FROM `".$db['prefix']."scenarioversies` 
	WHERE `scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		$qry = "UPDATE `".$db['prefix']."scenarioversies` SET
		`user_id` = '".$_SESSION['id']."',
		`wijzigingen` = '".mysqli_real_escape_string($db['link'], $_POST['wijzigingen'])."'
		WHERE `scenario_id` = '".$data['scenario_id']."'
		AND `concept` = 1";
		mysqli_query($db['link'], $qry);
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['scenario_id']);
		exit;
	}
}

elseif (($_GET['do'] == 'finalize') && permissioncheck('scenario_finalizeren', $scenario_data['organisatie_id'])) {
	//load data
	$qry = "SELECT `scenario_id` FROM `".$db['prefix']."scenarioversies` 
	WHERE `scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		$qry = "UPDATE `".$db['prefix']."scenarioversies` SET
		`user_id` = '".$_SESSION['id']."',
		`concept` = 0,
		`wijzigingen` = '".mysqli_real_escape_string($db['link'], $_POST['wijzigingen'])."'
		WHERE `scenario_id` = '".$data['scenario_id']."'
		AND `concept` = 1";
		mysqli_query($db['link'], $qry);
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['scenario_id']);
		exit;
	}
}

elseif (($_GET['do'] == 'copy') && permissioncheck('scenario_bewerken')) {
	//load data
	$qry = "SELECT `".$db['prefix']."scenarios`.`id` AS `id`, `".$db['prefix']."scenarios`.`map_id` AS `map_id`, `".$db['prefix']."scenarios`.`naam` AS `naam`, `".$db['prefix']."scenarioversies`.`omschrijving` AS `omschrijving`, `".$db['prefix']."scenarios`.`type` AS `type`, `".$db['prefix']."scenarios`.`ernst` AS `ernst`, `".$db['prefix']."scenarioversies`.`versie` as `versie` 
	FROM `".$db['prefix']."scenarioversies` 
	LEFT JOIN `".$db['prefix']."scenarios`
	ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
	WHERE `".$db['prefix']."scenarioversies`.`scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	AND `".$db['prefix']."scenarioversies`.`versie` = '".mysqli_real_escape_string($db['link'], $_GET['versie'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		//kopieer scenario en optioneel omschrijving
		$qry = "INSERT INTO `".$db['prefix']."scenarios` SET
		`map_id`= '".$data['map_id']."', 
		`naam` = '".mysqli_real_escape_string($db['link'], $data['naam'])." - kopie',
		`type`= '".$data['type']."', 
		`ernst`= '".$data['ernst']."'";
		mysqli_query($db['link'], $qry);
		$new_id = mysqli_insert_id($db['link']);
		//maak scenarioversie
		$qry = "INSERT INTO `".$db['prefix']."scenarioversies` SET
		`scenario_id` = '".$new_id."',
		`versie` = 1,
		`datum` = CURDATE(),
		`user_id` = '".$_SESSION['id']."',
		`wijzigingen` = '".mysqli_real_escape_string($db['link'], $data['naam'])." gedupliceerd',
		`concept` = 1";
		if ($_POST['omschrijving'] == 'true') {
			$qry .= ", `omschrijving` = '".mysqli_real_escape_string($db['link'], $data['omschrijving'])."'";
		}
		mysqli_query($db['link'], $qry);
		//kopieer scenariobijlagen
		if ($_POST['bijlagen'] == 'true') {
			$qry = "INSERT INTO `".$db['prefix']."scenariobijlagen` 
			(
			`scenario_id`,
			`datum`,
			`user_id`,
			`bestandsnaam`,
			`grootte`,
			`bestand`,
			`toegang`,
			`archief`
			)
			SELECT
			'".$new_id."',
			`datum`,
			`user_id`,
			`bestandsnaam`,
			`grootte`,
			`bestand`,
			`toegang`,
			`archief`
			FROM `".$db['prefix']."scenariobijlagen` 
			WHERE `scenario_id` = '".$data['id']."'";
			mysqli_query($db['link'], $qry);
		}
		//kopieer wegvakken
		if ($_POST['wegvakken'] == 'true') {
			$qry = "INSERT INTO `".$db['prefix']."wegvakmapping` 
			(
			`wegvak_id`,
			`scenario_id`
			)
			SELECT
			`wegvak_id`,
			'".$new_id."'
			FROM `".$db['prefix']."wegvakmapping` 
			WHERE `scenario_id` = '".$data['id']."'";
			mysqli_query($db['link'], $qry);
		}
		//kopieer schakelingen
		if ($_POST['schakelingen'] == 'true') {
			$qry = "SELECT `id`, `naam`, `omschrijving` FROM `".$db['prefix']."schakelingen`
			WHERE `scenario_id` = '".$data['id']."'
			AND `scenarioversie` = '".$data['versie']."'";
			$res = mysqli_query($db['link'], $qry);
			while ($row = mysqli_fetch_row($res)) {
				//maak schakeling
				$qry = "INSERT INTO `".$db['prefix']."schakelingen`
				SET `scenario_id` = '".$new_id."',
				`scenarioversie` = 1,
				`naam` = '".mysqli_real_escape_string($db['link'], $row[1])."'";
				if ($_POST['schakelingomschrijving'] == 'true') {
					$qry .= ", `omschrijving`= '".mysqli_real_escape_string($db['link'], $row[2])."'";
				}
				mysqli_query($db['link'], $qry);
				$schakeling_new_id = mysqli_insert_id($db['link']);
				//kopier omleidingmapping
				if ($_POST['omleidingen'] == 'true') {
					$qry = "INSERT INTO `".$db['prefix']."omleidingmapping` 
					(
					`wegvak_id`,
					`schakeling_id`
					)
					SELECT
					`wegvak_id`,
					'".$schakeling_new_id."'
					FROM `".$db['prefix']."omleidingmapping` 
					WHERE `schakeling_id` = '".$row[0]."'";
					mysqli_query($db['link'], $qry);
				}
				if ($_POST['drips'] == 'true') {
				//kopieer DRIPs
					$qry = "INSERT INTO `".$db['prefix']."schakelingdrips` 
					(
					`schakeling_id`,
					`drip_id`,
					`afbeelding`
					)
					SELECT
					'".$schakeling_new_id."',
					`drip_id`,
					`afbeelding`
					FROM `".$db['prefix']."schakelingdrips` 
					WHERE `schakeling_id` = '".$row[0]."'";
					mysqli_query($db['link'], $qry);
				}
			}
		}
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$new_id.'&do=edit');
		exit;
	}
}

elseif (($_GET['do'] == 'archive') && permissioncheck('scenario_finalizeren', $scenario_data['organisatie_id'])) {
	//load data
	$qry = "SELECT `id`, `archief` FROM `".$db['prefix']."scenarios` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		$qry = "UPDATE `".$db['prefix']."scenarios` 
		SET `archief` = '".(($row[1] == 1) ? '0' : '1')."'
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
		mysqli_query($db['link'], $qry);	
		$scenario_data['archief'] = (($row[1] == 1) ? '0' : '1');
		$scenario_data[5] = (($row[1] == 1) ? '0' : '1');
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
}

elseif (($_GET['do'] == 'annihilate') && permissioncheck('scenario_verwijderen', $scenario_data['organisatie_id'])) {
	//load data
	$qry = "SELECT `id`, `map_id`, `naam` FROM `".$db['prefix']."scenarios` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		//te verwijderen:
		//schakelingdrips
		//ongebruikte dripafbeeldingen
		//schakelingen
		//scenarioversies
		//wegvakmapping
		//omleidingmapping
		//scenario
		//scenariobijlagen TBA
		//bijlage bestanden TBA
		
		//verwijder DRIPs bij schakeling
		$qry = "SELECT `id`, `afbeelding` FROM `".$db['prefix']."schakelingdrips` 
		WHERE `schakeling_id` IN (
			SELECT `id` FROM `".$db['prefix']."schakelingen` 
			WHERE `scenario_id` = '".$data['id']."'
		)";
		$res = mysqli_query($db['link'], $qry);
		while ($row = mysqli_fetch_row($res)) {
			//verwijder rij
			$qry = "DELETE FROM `".$db['prefix']."schakelingdrips` 
			WHERE `id` = '".$row[0]."'";
			mysqli_query($db['link'], $qry);
			//verwijder in onbruik geraakte afbeeldingen
			include_once('afbeeldingverwijderen.fct.php');
			afbeelding_verwijderen($row[1]);
		}
		//verwijder wegvakmapping
		$qry = "DELETE FROM `".$db['prefix']."wegvakmapping` 
		WHERE `scenario_id` = '".$data['id']."'";
		mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		//verwijder omleidingmapping
		$qry = "DELETE FROM `".$db['prefix']."omleidingmapping` 
		WHERE `schakeling_id` IN (
			SELECT `id` FROM `".$db['prefix']."schakelingen` 
			WHERE `scenario_id` = '".$data['id']."'
		)";
		mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		//verwijder schakelingen
		$qry = "DELETE FROM `".$db['prefix']."schakelingen` 
		WHERE `scenario_id` = '".$data['id']."'";
		mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		//verwijder scenarioversies
		$qry = "DELETE FROM `".$db['prefix']."scenarioversies` 
		WHERE `scenario_id` = '".$data['id']."'";
		mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		//verwijder scenario
		$qry = "DELETE FROM `".$db['prefix']."scenarios` 
		WHERE `id` = '".$data['id']."'";
		mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php?folder='.$data['map_id']);
		exit;
	}
}

elseif ($_GET['do'] == 'download') {
	//function
	function strtovalidfilename($str) {
		return preg_replace('/([^A-Z0-9_\-.,; ])/Ui', '', $str);
	}
	//bepaal welke versie gedownload moet worden
	//wanneer niet ingelogd, toon enkel meest recente niet-concept
	if (!logincheck()) {
		$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarioversies`.`concept` FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		WHERE `".$db['prefix']."scenarioversies`.`scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
		AND `".$db['prefix']."scenarioversies`.`concept` = 0
		ORDER BY `".$db['prefix']."scenarioversies`.`versie` DESC
		LIMIT 1";
	}
	//wanneer ingelogd en versie gegeven, toon gevraagde versie
	elseif (is_numeric($_GET['versie'])) {
		$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarioversies`.`concept` FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		WHERE `".$db['prefix']."scenarioversies`.`scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
		AND `".$db['prefix']."scenarioversies`.`versie` = '".mysqli_real_escape_string($db['link'], $_GET['versie'])."'
		ORDER BY `".$db['prefix']."scenarioversies`.`versie` DESC
		LIMIT 1";
	}
	//wanneer ingelogd en geen versie gegeven, toon meest recente versie
	else {
		$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarioversies`.`concept` FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		WHERE `".$db['prefix']."scenarioversies`.`scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
		ORDER BY `".$db['prefix']."scenarioversies`.`versie` DESC
		LIMIT 1";
	}
	$res = mysqli_query($db['link'], $qry);
	$row = mysqli_fetch_row($res);
	$versie = $row[0];
	$naam = strtovalidfilename($row[1]).'_v'.$versie.(($row[2] == 1) ? '-concept' : '').'.zip';
	//wanneer geen versie, stop
	if (!($versie >= 1)) {
		echo '<p>Mag scenario niet downloaden</p>';
		exit;
	}
	//schakelingen toevoegen
	$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."schakelingen`
	WHERE `scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	AND `scenarioversie` = '".$versie."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		//create zip archive
		$zip = new ZipArchive();
		$filename = tempnam(sys_get_temp_dir(), 'scn');
		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}
		while ($row = mysqli_fetch_row($res)) {
			$schakeling = strtovalidfilename($row[1]);
			if (empty($schakeling)) $schakeling = $row[0];
			//voeg DRIPs voor schakeling toe
			$qry2 = "SELECT `".$db['prefix']."schakelingdrips`.`id`, `".$db['prefix']."drips`.`name`, `".$db['prefix']."schakelingdrips`.`afbeelding` FROM `".$db['prefix']."schakelingdrips`
			LEFT JOIN `".$db['prefix']."drips`
			ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
			WHERE `".$db['prefix']."schakelingdrips`.`schakeling_id` = '".$row[0]."'";
			$res2 = mysqli_query($db['link'], $qry2);
			while ($row2 = mysqli_fetch_row($res2)) {
				if (empty($row2[1])) {
					$drip_filename = $row2[2];
				}
				else {
					$drip_filename = strtovalidfilename($row2[1]).'.png';
				}
				//echo 'store/'.strtoupper(substr($row2[2], 0, 1)).'/'.$row2[2].'<br>';
				//echo $schakeling.'/'.$drip_filename.'<br>';
				$zip->addFile('store/'.strtoupper(substr($row2[2], 0, 1)).'/'.$row2[2], $schakeling.'/'.$drip_filename);
			}
		}
		$zip->close();
	}
	else {
		echo '<p>Geen schakelingen in scenario!</p>';
		exit;
	}
	//offer file for download
	header('Content-Description: File Transfer');
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename='.sprintf('"%s"', addcslashes(basename($naam), '"\\'))); 
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($filename));
	ob_clean();
	readfile($filename);
	//unlink temp file
	unlink($filename);
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Scenario</title>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=api-key&amp;sensor=false"></script>
<script type="text/javascript" src="markerwithlabel_packed.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="navigation" class="noprint">
    <ul class="toolbartab">
    	<li><span class="searchbox"><input type="text" id="searchbox" placeholder="Zoeken"></span></li>
        <li><a href="index.php">Kaart</a></li>
        <li><a href="scenariolijst.php">Scenario's</a></li>
        <?php if (logincheck() && ($_SESSION['accesslevel'] >= $auth['beheer'])) { ?> <li><a href="beheer.php">Beheer</a></li> <?php } ?>
		<?php if (!logincheck()) { ?><li><a href="login.php">Aanmelden</a></li><?php } ?>
        <?php if (logincheck()) { ?><li><a href="account.php">Account</a></li>
        <li><a href="logout.php">Afmelden</a></li><?php } ?>
        <li><a href="help.php" rel="scenario" id="help">Help</a></li>
    </ul>
</div>

<div id="content">
	
    
<?php 
if ($_GET['do'] == 'edit') {
	if (is_numeric($data['id'])) {
		?>
    	<h1>Scenario &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; bewerken</h1>
        <?php
	}
	else {
		?>
    	<h1>Nieuw scenario toevoegen</h1>
        <?php
	}
	
    if ($fieldcheck === FALSE) {
    	echo '<p class="error">De volgende velden zijn verplicht: naam, type, ernst, wijzigingen</p>';
		
    }
    ?>
    <script type="text/javascript" src="scenariobewerken.js"></script>
    <form method="post">
    
    <fieldset>
    <legend>Algemeen</legend>
    <table>
    <tr><td>map:</td><td><select name="map_id">
    <?php
	echo '<option value="0"';
	if ($data['map_id'] == 0) echo ' selected="selected"';
	echo '>(hoofdmap)</option>';
	function getfolders($parent_id, $depth) {
		global $db, $data;
		$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."scenariomappen`
		WHERE `parent_id` = '".$parent_id."'";
		$res = mysqli_query($db['link'], $qry);
		$n = mysqli_num_rows($res);
		$c = 0;
		while ($row = mysqli_fetch_row($res)) {
			$c++;
			echo '<option value="'.$row[0].'"';
			if ($data['map_id'] == $row[0]) echo ' selected="selected"';
			echo '>';
			for ($i = 0; $i <= $depth; $i++) {
				if ($i == $depth) {
					if ($c == $n) echo '&nbsp;&boxur; ';
					else echo '&nbsp;&boxvr; ';
				}
				else echo '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			echo htmlspecialchars($row[1]).'</option>';
			getfolders($row[0], $depth + 1);
		}
	}
	getfolders(0, 0);
	?>
    </select></td></tr>
    <tr><td>naam:</td><td><input type="text" name="naam" value="<?php echo htmlspecialchars($data['naam']); ?>"></td></tr>
    <tr><td>type:</td><td><select name="type">
    <?php
	foreach ($scenario_types as $k => $v) {
		echo '<option value="'.$k.'"';
		if ($k == $data['type']) echo ' selected="selected"';
		echo '>'.$v.'</option>';
	}
	?>
    </select></td></tr>
    <tr><td>ernst:</td><td><select name="ernst">
    <?php
	foreach ($scenario_ernsten as $k => $v) {
		echo '<option value="'.$k.'"';
		if ($k == $data['ernst']) echo ' selected="selected"';
		echo '>'.$v.'</option>';
	}
	?>
    </select></td></tr>
    </table>
    </fieldset>
    
    <fieldset>
    <legend id="legend_wvk"></legend>
    <div class="map-canvas" id="map-canvas" style="width: 880px; height: 600px;"></div>
    <ul class="scenariolijst">
    <li><h3>Routedelen</h3>
    <ul id="wegvakkenlijst">
    </ul>
    </li>
    </ul>
    </fieldset>
    
    <input type="hidden" name="coords" value="<?php echo htmlspecialchars($data['coords']); ?>">
    <input type="hidden" name="wegvakken" value="<?php echo join(',', $wegvakken); ?>">
    <input type="submit" id="submitbutton" value="Opslaan"> <a href="<?php echo (is_numeric($data['id']) ? 'scenario.php?id='.$data['id'] : 'scenariolijst.php?folder='.$data['map_id']); ?>">Annuleren</a>
    </form> 
    <?php
}

elseif ($_GET['do'] == 'description') {
?>
    <h1>Omschrijving bewerken voor scenario <em><?php echo htmlspecialchars($data['naam']); ?></em></h1>
    <form method="post">
    
    <script type="text/javascript" src="//cdn.tinymce.com/4/tinymce.min.js"></script>
	<script type="text/javascript" src="tinymce_config.js"></script>
    <fieldset>
    <legend>Omschrijving</legend>
    <textarea name="omschrijving" id="omschrijving"><?php echo htmlspecialchars($data['omschrijving']); ?></textarea>
    </fieldset>
    
    <input type="submit" id="submitbutton" value="Opslaan"> <a href="<?php echo (is_numeric($data['id']) ? 'scenario.php?id='.$data['id'] : 'scenariolijst.php?folder='.$data['map_id']); ?>">Annuleren</a>
    </form> 
    <?php
}
elseif ($_GET['do'] == 'newconcept') {
?>
    <h1>Nieuw concept maken voor scenario <em><?php echo htmlspecialchars($data['naam']); ?></em></h1>
    <p>Wil je een nieuw concept maken voor dit scenario?</p>
    <form method="post">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
    <input type="submit" id="submitbutton" value="Concept maken"> <a href="<?php echo (is_numeric($data['id']) ? 'scenario.php?id='.$data['id'] : 'scenariolijst.php?folder='.$data['map_id']); ?>">Annuleren</a>
    </form> 
    <?php
}

elseif ($_GET['do'] == 'transferownership') {
?>
    <h1>Beheer overdragen voor scenario <em><?php echo htmlspecialchars($data['naam']); ?></em></h1>
    <p>Middels deze optie kun je het beheer van dit scenario overdragen aan een andere organisatie. Dit betekent dat je zelf niet meer de naam, type, ernst, opslaglocatie (map) en gekoppelde routedelen van dit scenario kunt wijzigen. Ook kun je dit scenario niet meer definitief maken of archiveren/beschikbaar maken. Als je dat allemaal heel zeker weet, kun je hieronder de organisatie kiezen waaraan het beheer wordt overgedragen. Het is over het algemeen een goed idee om dit vooraf even te overleggen.</p>
    <form method="post">
    <table>
    <tr><td>Nieuw beheer door:</td><td><select name="organisatie">
    <?php
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."organisations`
	ORDER BY `name`";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		echo '<option value="'.$row[0].'"';
		if ($data['organisatie_id'] == $row[0]) echo ' selected="selected"';
		echo '>';
		echo htmlspecialchars($row[1]).'</option>';
	}
	?>
    </select></td></tr>
    </table>
    <input type="submit" id="submitbutton" value="Beheer overdragen"> <a href="<?php echo (is_numeric($data['id']) ? 'scenario.php?id='.$data['id'] : 'scenariolijst.php?folder='.$data['map_id']); ?>">Annuleren</a>
    </form> 
    <?php
}
/*
* scenario definitief maken en changlog bewerken
*/
elseif (($_GET['do'] == 'finalize') || ($_GET['do'] == 'changelog')) {
	if ($_GET['do'] == 'finalize') {
	?>
    <h1>Scenario definitief maken</h1>
    <p class="info">Het definitief maken van een scenario kan niet ongedaan gemaakt worden. Het scenario kan daarna wel bewerkt worden in een nieuwe versie.</p>
    <?php
	}
	else {
	?>
    <h1>Versiewijzigingen bewerken</h1>
    <?php
	}
	?>
    <form method="post">
    <fieldset>
    <legend>Versiebeheer</legend>
    <table>
    <tr><th>Versie</th><th>Datum</th><th>Auteur</th><th>Wijzigingen</th></tr>
    <?php
	//get version history
	$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie`, `".$db['prefix']."scenarioversies`.`datum`, `".$db['prefix']."users`.`name`, `".$db['prefix']."scenarioversies`.`wijzigingen`, `".$db['prefix']."scenarioversies`.`concept` FROM `".$db['prefix']."scenarioversies`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenarioversies`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `scenario_id` = '".$data['scenario_id']."'
	ORDER BY `versie`";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		echo '<tr><td>'.(($row[4] == 1) ? 'concept' : $row[0]).'</td><td>'.date('d-m-Y', strtotime($row[1])).'</td><td>'.htmlspecialchars($row[2]).'</td><td>'.htmlspecialchars($row[3]).'</td></tr>';
		$wijzigingen = $row[3];
	}
	?>
    <tr><td></td><td><?php echo date('d-m-Y'); ?></td><td><?php echo htmlspecialchars($_SESSION['name']); ?></td><td><input type="text" name="wijzigingen" value="<?php echo htmlspecialchars($wijzigingen); ?>"></td></tr>
    </table>
    </fieldset>
	<input type="submit" id="submitbutton" value="<?php echo (($_GET['do'] == 'finalize') ? 'Definitief maken' : 'Opslaan'); ?>"> <a href="scenario.php?id=<?php echo $data['scenario_id']; ?>">Annuleren</a>
    </form> 
    <?php
}
/*
* scenario dupliceren
*/
elseif ($_GET['do'] == 'copy') {
	?>
    <script type="text/javascript">
	$(function() {
		$('#schakelingen').click( function() {
			if ($('#schakelingen:checked').val() == 'true') {
				$('#schakelingsubopties input[type=checkbox]').removeAttr('disabled');
				$('#schakelingsubopties label').removeClass('disabled');
			}
			else {
				$('#schakelingsubopties input[type=checkbox]').attr('disabled', 'disabled');
				$('#schakelingsubopties label').addClass('disabled');
			}
		});
	});
	</script>
    <h1>Kopie van scenario &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; maken</h1>
    <form method="post">
    <fieldset>
    <legend>Selecteer te kopi&euml;ren onderdelen</legend>
    <dl>
    	<dt><input type="checkbox" checked disabled><label>Scenario</label></dt>
        	<dd><input type="checkbox" name="omschrijving" id="omschrijving" value="true" checked><label for="omschrijving">Omschrijving</label><br>
            <input type="checkbox" name="wegvakken" id="wegvakken" value="true" checked><label for="wegvakken">Gekoppelde routedelen</label><br>
            <input type="checkbox" name="bijlagen" id="bijlagen" value="true" checked><label for="bijlagen">Scenariobijlagen</label></dd>
        <dt><input type="checkbox" name="schakelingen" id="schakelingen" value="true" checked><label for="schakelingen">Schakelingen</label></dt>
        	<dd id="schakelingsubopties"><input type="checkbox" name="drips" id="drips" value="true" checked><label for="drips">DRIPs</label><br>
            <input type="checkbox" name="schakelingomschrijving" id="schakelingomschrijving" value="true" checked><label for="schakelingomschrijving">Omschrijving</label><br>
            <input type="checkbox" name="omleidingen" id="omleidingen" value="true" checked><label for="omleidingen">Gekoppelde omleidingsroutedelen</label></dd>
    </dl>
    
    </fieldset>
	<input type="submit" id="submitbutton" value="Kopie maken"> <a href="scenario.php?id=<?php echo $data['id']; ?>">Annuleren</a>
    </form> 
    <?php
}
/*
* scenario verwijderen
*/
elseif ($_GET['do'] == 'annihilate') {
	?>
    <script type="text/javascript">
	var countdown = 3;
	var interval;
	$(function() {
		$('#submitbutton').click( function() {
			if ($('input[name=annihilate]').val() != 'true') {
				$('#submitbutton').attr('value', 'Wacht ' + countdown + ' seconden'); 
				$('#submitbutton').attr('disabled', 'disabled');
				interval = setInterval(count_down, 1000);
			}
			else {
				$('#form').submit();
			}
		});
	});
	function count_down() {
		countdown = countdown - 1;
		$('#submitbutton').attr('value', 'Wacht ' + countdown + ' seconden'); 
		console.log(countdown);
		if (countdown == 0) {
			$('input[name=annihilate]').val('true');
			$('#submitbutton').removeAttr('disabled');
			$('#submitbutton').attr('value', 'Scenario vernietigen');
			clearInterval(interval);
		}
	}
	</script>
    <h1>Scenario &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; verwijderen</h1>
    <form method="post" id="form">
    <p class="error">Het onherstelbaar vernietigen van een scenario kan niet ongedaan gemaakt worden. Alle bij het scenario horende schakelingen, koppelingen met routedelen en DRIP-afbeeldingen worden eveneens verwijderd.</p>
    <input type="hidden" name="annihilate" value="false">
	<input type="button" id="submitbutton" value="Knop activeren"> <a href="scenario.php?id=<?php echo $data['id']; ?>">Annuleren</a>
    </form> 
    <?php
}
/*
* toon scenario
*/
else {
	//controleer of gegeven id bestaat
	if ($scenario_data['id'] > 0) {
		//$row: `id`, `naam`, `type`, `map_id`, `ernst`, `archief`, `organisatie_id`
		$row = $scenario_data;
		
		if (!logincheck() && ($row[5] == 1)) {
			//als niet ingelogd en scenario is archief, dan niet tonen
			echo '<p class="error">Aanmelden is vereist om gearchiveerde scenario\'s te kunnen bekijken.</p>';
		}
		else {
			//bepaal welke versie getoond moet worden
			//bepaal conceptversie
			$qry = "SELECT `versie` FROM `".$db['prefix']."scenarioversies`
			WHERE `scenario_id` = '".$row[0]."'
			ORDER BY `versie` DESC
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			$versie_con = mysqli_fetch_row($res);
			$versie_con = $versie_con[0];
			//bepaal meest recente niet-concept
			$qry = "SELECT `versie` FROM `".$db['prefix']."scenarioversies`
			WHERE `scenario_id` = '".$row[0]."'
			AND `concept` = 0
			ORDER BY `versie` DESC
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			if (mysqli_num_rows($res)) {
				$versie_def = mysqli_fetch_row($res);
				$versie_def = $versie_def[0];
			}
			else {
				$versie_def = 0;
			}
			//bepaal te tonen versie
			//wanneer niet ingelogd, sta alleen toe concept en meest recente niet-concept
			if (!logincheck()) {
				if (is_numeric($_GET['versie']) && in_array($_GET['versie'], array($versie_con, $versie_def))) {
					$versie = $_GET['versie'];
				}
				elseif ($versie_def > 0) {
					$versie = $versie_def;
				}
				else {
					$versie = $versie_con;
				}
			}
			//wanneer ingelogd, kan alle versies tonen
			else {
				if (is_numeric($_GET['versie']) && ($_GET['versie'] <= $versie_con)) {
					$versie = $_GET['versie'];
				}
				else {
					$versie = $versie_con;
				}
			}
			//waarschuw wanneer concept bekeken wordt
			if ($versie > $versie_def) {
				echo '<p class="warning noprint">Je bekijkt nu een conceptversie van dit scenario. Deze versie is waarschijnlijk nog niet ge&iuml;mplementeerd in de netwerkmanagementsystemen!';
				if ($versie_def > 0) echo ' <a href="?id='.$row[0].'&amp;versie='.$versie_def.'">Bekijk de actuele definitieve versie.</a>';
				echo '</p>';
			}
			
			echo '<div style="float:right; text-align:right;" class="noprint">';
			echo '<a href="scenariolijst.php?folder='.$row[3].'"><span class="ui-icon ui-icon-arrowreturnthick-1-w"></span> terug naar lijst</a>';
			if ($versie >= 1) {
				echo ' | <a href="?do=download&amp;id='.$row[0].'&amp;versie='.$versie.'"><span class="ui-icon 	ui-icon-arrowthickstop-1-s"></span> download beeldstanden</a>';
			}
			if (permissioncheck('scenario_verwijderen', $scenario_data['organisatie_id'])) {
				echo '<br><a href="?id='.$row[0].'&amp;do=annihilate"><span class="ui-icon ui-icon-trash"></span> verwijderen</a>';
			}
			if (permissioncheck('scenario_bewerken')) {
				echo ' | <a href="?id='.$row[0].'&amp;versie='.$versie.'&amp;do=copy"><span class="ui-icon ui-icon-copy"></span> dupliceren</a>';
			}
			if (permissioncheck('scenario_bewerken', $scenario_data['organisatie_id'])) {
				echo ' | <a href="?id='.$row[0].'&amp;do=edit"><span class="ui-icon ui-icon-pencil"></span> bewerken</a>';
			}
			echo '</div>';
			
			echo '<h1>'.htmlspecialchars($row[1]).(($row[5] == 1) ? ' (archief)' : '').'</h1>';
			echo '<p><img src="images/'.$scenario_types_afbeeldingen[$row[2]].'" class="te" width="16" height="16" alt="'.$scenario_types[$row[2]].'" title="'.$scenario_types[$row[2]].'">'.$scenario_types[$row[2]].'<br>';
			echo '<img src="images/'.$scenario_ernsten_afbeeldingen[$row[4]].'" class="te" width="16" height="16" alt="'.$scenario_ernsten[$row[4]].'" title="'.$scenario_ernsten[$row[4]].'">'.$scenario_ernsten[$row[4]].'<br>';
            echo 'Scenario ID: <a href="scenario.php?id='.$row[0].'">'.$row[0].'</a></p>';
			
			//beheer
			if (logincheck()) {
				
				if (permissioncheck('scenario_finalizeren', $scenario_data['organisatie_id'])) {
					if ($row[5] != 1) {
						echo '<p class="noprint" style="float:right;"><a href="?id='.$row[0].'&amp;do=archive"><span class="ui-icon ui-icon-suitcase"></span> scenario archiveren</a></p>';
					}
					else {
						echo '<p class="noprint" style="float:right;"><a href="?id='.$row[0].'&amp;do=archive"><span class="ui-icon ui-icon-document"></span> scenario beschikbaar maken</a></p>';
					}
				}
				
				echo '<h2>Beheer</h2>';
				
				if (permissioncheck('scenario_overdragen', $scenario_data['organisatie_id'])) {
					echo '<p class="noprint" style="clear: right; float:right;"><a href="?id='.$row[0].'&amp;do=transferownership"><span class="ui-icon ui-icon-key"></span> beheer overdragen</a></p>';
				}
				
				$qry = "SELECT `name`, `afdeling`, `telefoon`, `emailadres` FROM `".$db['prefix']."organisations`
				WHERE `id` = '".$row[6]."'
				LIMIT 1";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res)) {
					$data = mysqli_fetch_assoc($res);
					echo '<table class="noborder">';
					echo '<tr><td>Organisatie:</td><td>'.htmlspecialchars($data['name']).'</td></tr>';
					echo '<tr><td>Afdeling:</td><td>'.htmlspecialchars($data['afdeling']).'</td></tr>';
					echo '<tr><td>Telefoon:</td><td>'.htmlspecialchars($data['telefoon']).'</td></tr>';
					echo '<tr><td>E-mail:</td><td>'.htmlspecialchars($data['emailadres']).'</td></tr>';
					echo '</table>';
				}
			}
			//script handler
			echo '<script type="text/javascript" src="scenariobekijken.js"></script>';
			
			//toon scenario als versie niet nul
			if ($versie >= 1) {
				//versiehistorie
				$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie`, `".$db['prefix']."scenarioversies`.`datum`, `".$db['prefix']."users`.`name`, `".$db['prefix']."scenarioversies`.`wijzigingen`, `".$db['prefix']."scenarioversies`.`concept`, `".$db['prefix']."scenarioversies`.`omschrijving` FROM `".$db['prefix']."scenarioversies`
				LEFT JOIN `".$db['prefix']."users`
				ON `".$db['prefix']."scenarioversies`.`user_id` = `".$db['prefix']."users`.`id`
				WHERE `scenario_id` = '".$row[0]."'
				ORDER BY `versie` ASC";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res)) {
					
					$table = '<table style="float:left;">
					<tr><th>Versie</th><th>Datum</th><th>Auteur</th><th>Wijzigingen</th></tr>';
					while ($row2 = mysqli_fetch_row($res)) {
						$table .= '<tr'.(($versie == $row2[0]) ? ' class="bold"' : '' ).'><td>';
						if (logincheck() || ($row2[0] >= $versie_def)) $table .= '<a href="?id='.$row[0].'&amp;versie='.$row2[0].'">';
						$table .= (($row2[4] == 1) ? 'concept' : $row2[0]);
						if (logincheck() || ($row2[0] >= $versie_def)) $table .= '</a>';
						$table .= '</td><td>'.date('d-m-Y', strtotime($row2[1])).'</td><td>'.htmlspecialchars($row2[2]).'</td><td>'.htmlspecialchars($row2[3]).'</td></tr>';
						//toon juiste informatie bij versie
						if ($versie == $row2[0]) {
							$omschrijving = $row2[5];
							$concept = $row2[4];
						}
					}
					$table .= '</table>';
					
					if (($concept == 1) && (permissioncheck('scenario_bewerken'))) {
						echo '<p class="noprint" style="float:right;"><a href="?id='.$row[0].'&amp;do=changelog"><span class="ui-icon ui-icon-tag"></span> versiehistorie bewerken</a></p>';
					}
					echo '<h2>Versiehistorie</h2>';
					
					if (($concept == 1) && permissioncheck('scenario_finalizeren', $scenario_data['organisatie_id'])) {
						echo '<p class="noprint" style="clear:right; float:right;"><a href="?id='.$row[0].'&amp;do=finalize"><span class="ui-icon ui-icon-disk"></span> scenario definitief maken</a></p>';
					}
					elseif (($concept == 0) && ($versie_con == $versie_def) && ($versie == $versie_con) && (permissioncheck('scenario_bewerken'))) {
						echo '<p class="noprint" style="clear:right; float:right;"><a href="?id='.$row[0].'&amp;do=newconcept"><span class="ui-icon ui-icon-pencil"></span> nieuw concept maken</a></p>';
					}
					
					echo $table;
					echo '<div style="clear:both;"></div>';
				}
				
				if (($concept == 1) && (permissioncheck('scenario_bewerken'))) {
					echo '<p class="noprint" style="float:right;"><a href="?id='.$row[0].'&amp;do=description"><span class="ui-icon ui-icon-comment"></span> '.(!empty($omschrijving) ? 'omschrijving bewerken' : 'omschrijving toevoegen').'</a></p>';
				}
				if (!empty($omschrijving)) {
					echo '<h2>Omschrijving</h2>';
					echo '<span style="clear:both;"></span>';
					echo $omschrijving;
				}
				
				//lijst van gekoppelde routedelen
				$qry = "SELECT `".$db['prefix']."wegvakken`.`id`, `".$db['prefix']."wegvakken`.`naam`, `".$db['prefix']."wegvakken`.`weg`, `".$db['prefix']."wegvakken`.`ri`, `".$db['prefix']."wegvakken`.`km_van`, `".$db['prefix']."wegvakken`.`km_tot` FROM `".$db['prefix']."wegvakmapping`
				LEFT JOIN `".$db['prefix']."wegvakken`
				ON `".$db['prefix']."wegvakken`.`id` = `".$db['prefix']."wegvakmapping`.`wegvak_id`
				WHERE `".$db['prefix']."wegvakmapping`.`scenario_id` = '".$row[0]."'";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res)) {
					echo '<h2 style="clear:both;">Gekoppelde routedelen</h2>';
					echo '<ul class="wegvakkenlijst">';
					while ($row2 = mysqli_fetch_row($res)) {
						echo '<li><a href="index.php?lookat='.$row2[0].'"><span class="ui-icon ui-icon-arrowthick-1-e"></span> '.htmlspecialchars($row2[1]).'</a> ';
						if (is_numeric(substr($row2[2], 1))) {
							echo '<span class="wegnummer_'.strtoupper(substr($row2[2], 0, 1)).'">';
						}
						else {
							echo '<span>';
						}
						echo htmlspecialchars($row2[2]).'</span> '.htmlspecialchars($row2[3]);
						if (!empty($row2[4]) && !empty($row2[5])) {
							echo ' '.number_format($row2[4], 1, ',', '.').'&raquo;'.number_format($row2[5], 1, ',', '.');
						}
						echo '</li>';
					}
					echo '</ul>';	
				}
				
				//scenariobijlagen
				//selecteer groep-id van gebruiker
				if (logincheck()) {
					$qry = "SELECT `organisation` FROM `".$db['prefix']."users`
					WHERE `".$db['prefix']."users`.`id` = '".$_SESSION['id']."'";
					$res = mysqli_query($db['link'], $qry);
					$row2 = mysqli_fetch_row($res);
					$org = $row2[0];
				}
				else {
					$org = 0;
				}
				//selecteer bijlagen
				$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`id`, `".$db['prefix']."scenariobijlagen`.`bestandsnaam`, `".$db['prefix']."scenariobijlagen`.`grootte`, `".$db['prefix']."scenariobijlagen`.`toegang`
				FROM `".$db['prefix']."scenariobijlagen`
				LEFT JOIN `".$db['prefix']."users`
				ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
				WHERE `".$db['prefix']."scenariobijlagen`.`scenario_id` = '".$row[0]."'
				AND `".$db['prefix']."scenariobijlagen`.`archief` = 0
				AND (`".$db['prefix']."scenariobijlagen`.`toegang` >= ".(logincheck() ? '1' : '2')."
				OR `".$db['prefix']."users`.`organisation` = '".$org."')
				ORDER BY `".$db['prefix']."scenariobijlagen`.`bestandsnaam`";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res)) {
					echo '<h2 style="clear:both;">Bijlagen</h2>';
					echo '<ul class="wegvakkenlijst" style="float:left;">';
					include_once('convertfilesize.fct.php');
					while ($row2 = mysqli_fetch_row($res)) {
						echo '<li><a href="scenariobijlage.php?do=getfile&amp;id='.$row2[0].'"><span class="ui-icon ui-icon-attachment"></span> '.htmlspecialchars($row2[1]).'</a> ('.convertfilesize($row2[2]).')';
						if ($row2[3] == 0) echo ' <span class="ui-icon ui-icon-locked" title="Bijlage alleen toegankelijk voor eigen organisatie"></span>';
						elseif ($row2[3] <= 1) echo ' <span class="ui-icon ui-icon-contact" title="Bijlage alleen toegankelijk na inloggen"></span>';
						echo '</li>';
					}
					echo '</ul>';
				}
				//bijlage beheren link
				if (permissioncheck('scenario_bewerken')) {
					echo '<p style="float:right" class="noprint"><a href="scenariobijlage.php?id='.$row[0].'"><span class="ui-icon ui-icon-attachment"></span> scenariobijlagen beheren</a></p>';
					echo '<div style="clear:both;"></div>';
				}
				//schakeling toevoegen link
				if (($concept == 1) && permissioncheck('scenario_bewerken')) {
					echo '<div style="clear:both;"></div>';
					echo '<p style="float:right" class="noprint"><a href="schakeling.php?scenario='.$row[0].'&amp;do=edit"><span class="ui-icon ui-icon-plus"></span> schakeling toevoegen</a></p>';
					echo '<div style="clear:both;"></div>';
				}
				//inhoudsopgave, als meer dan 1 schakeling
				$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."schakelingen`
				WHERE `scenario_id` = '".$row[0]."'
				AND `scenarioversie` = '".$versie."'
				ORDER BY `naam`";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res) > 1) {
					echo '<div class="newpage"></div>';
					echo '<h1>Inhoudsopgave</h1>';
					echo '<ul>';
					while ($row2 = mysqli_fetch_row($res)) {
						echo '<li><a href="#'.$row2[0].'">'.(empty($row2[1]) ? htmlspecialchars($row[1]) : htmlspecialchars($row2[1])).'</a></li>';
					}
					echo '</ul>';
				}
				//loop door schakelingen
				$qry = "SELECT `id`, `naam`, `omschrijving` FROM `".$db['prefix']."schakelingen`
				WHERE `scenario_id` = '".$row[0]."'
				AND `scenarioversie` = '".$versie."'
				ORDER BY `naam`";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res)) {
					while ($row2 = mysqli_fetch_row($res)) {
						echo '<div class="newpage"></div>';
						if (($concept == 1) && ($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
							echo '<div style="float:right" class="noprint">';
							echo '<a href="schakeling.php?id='.$row2[0].'&amp;do=edit"><span class="ui-icon ui-icon-pencil"></span> schakeling bewerken</a>';
							echo ' | <a href="schakeling.php?id='.$row2[0].'&amp;do=copy"><span class="ui-icon ui-icon-copy"></span> schakeling kopi&euml;ren</a>';
							echo ' | <a href="schakeling.php?id='.$row2[0].'&amp;do=delete"><span class="ui-icon ui-icon-trash"></span> schakeling verwijderen</a>';
							echo '</div>';
						}
						echo '<a name="'.$row2[0].'"></a><h1>'.(empty($row2[1]) ? htmlspecialchars($row[1]) : htmlspecialchars($row2[1])).'</h1>';
						//kaart
						echo '<div id="map_'.$row[0].'_'.$row2[0].'" class="map-canvas" style="width: 900px; height: 500px;"></div>';
						//drips
						//get drips by schakeling
						$qry3 = "SELECT `".$db['prefix']."schakelingdrips`.`id`, `".$db['prefix']."drips`.`name`, `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."drips`.`owner` FROM `".$db['prefix']."schakelingdrips`
						LEFT JOIN `".$db['prefix']."drips`
						ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
						WHERE `schakeling_id`  = '".mysqli_real_escape_string($db['link'], $row2[0])."'
						ORDER BY `".$db['prefix']."drips`.`owner`, `".$db['prefix']."drips`.`name`";
						$res3 = mysqli_query($db['link'], $qry3);
						if (mysqli_num_rows($res3)) {
							echo '<ul class="drips">';
							while ($row3 = mysqli_fetch_row($res3)) {
								switch ($row3[3]) {
									case 'gemeente Den Haag':
										$driplabelstyle = 'GDH'; break;
									case 'provincie Zuid-Holland':
										$driplabelstyle = 'PZH'; break;
									case 'gemeente Rotterdam':
										$driplabelstyle = 'GRD'; break;
									case 'Rijkswaterstaat ZWN':
										$driplabelstyle = 'RWS'; break;
									default: 
										$driplabelstyle = 'other'; break;
								}
								echo '<li><div class="drip_name org org-'.$driplabelstyle.'">'.(empty($row3[1]) ? '(geen)' : htmlspecialchars($row3[1])).'</div><div class="drip_image"><img src="store/'.strtoupper(substr($row3[2], 0, 1)).'/'.$row3[2].'" height="96" alt="'.(empty($row3[1]) ? '(geen)' : htmlspecialchars($row3[1])).'"></div></li>';
							}
							echo '</ul>';
						}
						echo '<div style="clear:both;"></div>';
						//omschrijving
						if (!empty($row2[2])) {
							echo $row2[2];
						}
					}
					//schakeling toevoegen link
					if (($concept == 1) && permissioncheck('scenario_bewerken')) {
						echo '<div style="clear:both;"></div>';
						echo '<div class="newpage"></div>';
						echo '<p style="float:right" class="noprint"><a href="schakeling.php?scenario='.$row[0].'&amp;do=edit"><span class="ui-icon ui-icon-plus"></span> schakeling toevoegen</a></p>';
					}
				}
			}
			else {
				echo '<p>Opgegeven versie bestaat niet.</p>';
			}
		}
	}
	else {
		echo '<p>Geen scenario met opgegeven ID.</p>';
	}
}
?>
</div>
</body>
</html>
