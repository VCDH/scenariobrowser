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
if (!logincheck()) {
	header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
    exit;
}

//include database gegevens
include('dbconnect.inc.php');

if (($_GET['do'] == 'edit') && ($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
	$wegvakken = array();
	//load data
	if (is_numeric($_POST['id'])) {
		$data['id'] = $_POST['id'];
	}
	else {
		$data['id'] = $_GET['id'];
	}
	if (is_numeric($data['id'])) {
		$qry = "SELECT * FROM `".$db['prefix']."schakelingen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data = mysqli_fetch_assoc($res);
			//get wegvakken
			$qry = "SELECT `wegvak_id` FROM `".$db['prefix']."omleidingmapping`
			WHERE `schakeling_id` = '".$data['id']."'";
			$res = mysqli_query($db['link'], $qry);
			while ($row = mysqli_fetch_row($res)) {
				$wegvakken[] = $row[0];
			}
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
			exit;
		}
	}
	//nieuwe toevoegen
	else {
		$qry = "SELECT * FROM `".$db['prefix']."scenarioversies` 
		WHERE `scenario_id` = '".mysqli_real_escape_string($db['link'], $_GET['scenario'])."'
		AND `concept` = 1
		ORDER BY `versie` DESC
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_assoc($res);
			$data['scenario_id'] = $row['scenario_id'];
			$data['scenarioversie'] = $row['versie'];
			//maak nieuwe schakeling voor scenario
			$qry = "INSERT IGNORE INTO `".$db['prefix']."schakelingen` SET
			`scenario_id` = '".$data['scenario_id']."',
			`scenarioversie` = '".$data['scenarioversie']."',
			`naam` = 'Nieuwe schakeling'";
			mysqli_query($db['link'], $qry);
			$data['id'] = mysqli_insert_id($db['link']);
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
			exit;
		}
	}
	//haal scenario naam op
	$qry = "SELECT `naam` FROM `".$db['prefix']."scenarios` 
	WHERE `id` = '".$data['scenario_id']."'";
	$res = mysqli_query($db['link'], $qry);
	if ($row = mysqli_fetch_row($res)) {
		$data['scenario_naam'] = $row[0];
	}
	
	$stremmingsvakken = array();
	//get stremmingsvakken
	$qry = "SELECT `wegvak_id` FROM `".$db['prefix']."wegvakmapping`
	WHERE `scenario_id` = '".$data['scenario_id']."'";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		$stremmingsvakken[] = $row[0];
	}
	
	//check post data
	if (!empty($_POST)) {
		$fieldcheck = TRUE;
		//check fields
		//if (empty($_POST['naam'])) $fieldcheck = FALSE;
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
				$qry = "UPDATE `".$db['prefix']."schakelingen` SET
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`omschrijving` = '".mysqli_real_escape_string($db['link'], $_POST['omschrijving'])."'
				WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
				mysqli_query($db['link'], $qry);
				//set segment mapping
				//remove
				if (empty($wegvakken)) {
					$qry = "DELETE FROM `".$db['prefix']."omleidingmapping` 
					WHERE `schakeling_id` = '".$data['id']."'";
				}
				else {
					$qry = "DELETE FROM `".$db['prefix']."omleidingmapping` 
					WHERE `schakeling_id` = '".$data['id']."'
					AND `wegvak_id` NOT IN (".join(',', $wegvakken).")";
				}
				mysqli_query($db['link'], $qry);
				//add
				foreach($wegvakken as $wegvak_id) {
					$qry = "INSERT IGNORE INTO `".$db['prefix']."omleidingmapping` SET
					`schakeling_id` = '".$data['id']."',
					`wegvak_id` = '".$wegvak_id."'";
					mysqli_query($db['link'], $qry);
					echo mysqli_error($db['link']);
				}
			}
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['scenario_id'].'#'.$data['id']);
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
elseif (($_GET['do'] == 'delete') && ($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
	//get scenario id en controleer of concept
	$qry = "SELECT `id`, `scenario_id`, `scenarioversie` FROM `".$db['prefix']."schakelingen` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		//controleer of concept
		$qry = "SELECT `concept` FROM `".$db['prefix']."scenarioversies` 
		WHERE `scenario_id` = '".$data['scenario_id']."'
		AND `versie` = '".$data['scenarioversie']."'
		AND `concept` = 1";
		$res = mysqli_query($db['link'], $qry);
		if (!mysqli_num_rows($res)) {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['scenario_id']);
			exit;
		}
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	
	if (!empty($_POST)) {
		//verwijder DRIPs bij schakeling
		$qry = "SELECT `id`, `afbeelding` FROM `".$db['prefix']."schakelingdrips` 
		WHERE `schakeling_id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
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
		//verwijder schakeling
		$qry = "DELETE FROM `".$db['prefix']."schakelingen` 
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
		mysqli_query($db['link'], $qry);
		//verwijder omleidingen
		$qry = "DELETE FROM `".$db['prefix']."omleidingmapping` 
		WHERE `schakeling_id` = '".$data['id']."'";
		mysqli_query($db['link'], $qry);
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenario.php?id='.$data['scenario_id']);
		exit;
	}
}
elseif (($_GET['do'] == 'copy') && ($_SESSION['accesslevel'] >= $auth['scenario_bewerken'])) {
	//get scenario id en controleer of concept
	$qry = "SELECT `id`, `scenario_id`, `scenarioversie`, `naam`, `omschrijving` FROM `".$db['prefix']."schakelingen` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		//controleer of concept
		$qry = "SELECT `concept` FROM `".$db['prefix']."scenarioversies` 
		WHERE `scenario_id` = '".$row[1]."'
		AND `versie` = '".$row[2]."'
		AND `concept` = 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			//kopieer schakeling
			$qry = "INSERT INTO `".$db['prefix']."schakelingen` SET
			`scenario_id` = '".$row[1]."', 
			`scenarioversie` = '".$row[2]."', 
			`naam` = '".mysqli_real_escape_string($db['link'], $row[3])." - kopie', 
			`omschrijving` = '".mysqli_real_escape_string($db['link'], $row[4])."'";
			mysqli_query($db['link'], $qry);
			$schakeling_id = mysqli_insert_id($db['link']);
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
			//maak kopie van omleidingen
			$qry = "INSERT INTO `".$db['prefix']."omleidingmapping` 
			(
			`schakeling_id`,
			`wegvak_id`
			)
			SELECT
			'".$schakeling_id."',
			`wegvak_id`
			FROM `".$db['prefix']."omleidingmapping` 
			WHERE `schakeling_id` = '".$row[0]."'";
			mysqli_query($db['link'], $qry);
		}
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/schakeling.php?do=edit&id='.$schakeling_id);
		exit;
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Schakeling</title>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=api-key&amp;sensor=false"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="print.css" media="print">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="navigation">
    <ul class="toolbartab">
        <li><a href="help.php" rel="schakeling" id="help">Help</a></li>
    </ul>
</div>

<div id="content">   
<?php 
if ($_GET['do'] == 'edit') {
	if (!is_numeric($_GET['scenario'])) {
		?>
    	<h1>Schakeling <em><?php echo (!empty($data['naam']) ? htmlspecialchars($data['naam']) : '(naamloze schakeling)'); ?></em> bewerken</h1>
        <?php
	}
	else {
		?>
    	<h1>Nieuwe schakeling toevoegen</h1>
        <?php
	}
	
    if ($fieldcheck === FALSE) {
    	echo '<p class="error">De volgende velden zijn verplicht: naam</p>';
		
    }
    ?>
    
    <script src="jQuery-File-Upload-9.12.3/js/jquery.iframe-transport.js"></script>
    <script src="jQuery-File-Upload-9.12.3/js/jquery.fileupload.js"></script>
    <script type="text/javascript" src="//cdn.tinymce.com/4/tinymce.min.js"></script>
	<script type="text/javascript" src="tinymce_config.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.2.0/list.min.js"></script>
    <script type="text/javascript" src="schakeling.js"></script>
    
    <form method="post">
    
    <fieldset>
    <legend>Algemeen</legend>
    <table>
    <tr><td>naam:</td><td><input type="text" name="naam" value="<?php echo htmlspecialchars($data['naam']); ?>"></td></tr>
    <tr><td>scenario:</td><td><?php echo htmlspecialchars($data['scenario_naam']); ?></td></tr>
    </table>
    </fieldset>
    
    <fieldset>
    <legend>DRIPs <span id="button_refresh_drips" class="ui-icon ui-icon-refresh" title="Vernieuwen"></span></legend>
    <ul id="drips">
    </ul>
    <div id="uploadarea" style="clear:both;">
    <span class="fileinput-button" id="wdd_button">Nieuw via WebDRIP Designer...</span>
    <!-- The fileinput-button span is used to style the file input field as button -->
    <span class="fileinput-button">
        <span>Nieuw via Upload...</span>
        <!-- The file input field used as target for the file upload widget -->
        <input id="fileupload" type="file" name="files[]" multiple>
    </span> <input type="checkbox" name="overwrite" id="overwrite" value="true" checked="checked"><label for="overwrite" title="Selecteer deze optie om bestaande beeldstanden te overschrijven wanneer nieuwe afbeeldingen voor DRIPs met dezelfde naam worden ge&uuml;pload. Wanneer deze optie worden nieuwe afbeeldingen als ongedefini&euml;erde DRIP toegevoegd en blijven bestaande afbeeldingen behouden.">Overschrijven</label>
    <!-- The container for the uploaded files -->
    <div id="files" class="files"></div>
    </div>
    </fieldset>
   
    <fieldset>
    <legend>Omschrijving</legend>
    <textarea name="omschrijving" id="omschrijving"><?php echo htmlspecialchars($data['omschrijving']); ?></textarea>
    </fieldset>
    
    <fieldset>
    <legend id="legend_wvk"></legend>
    <div class="map-canvas" id="map-canvas" style="width: 900px; height: 600px;"></div>
    <ul class="scenariolijst">
    <li><h3>Omleidingsroutedelen</h3>
    <ul id="wegvakkenlijst">
    </ul>
    </li>
    </ul>
    </fieldset>
    
    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
    <input type="hidden" name="wegvakken" value="<?php echo join(',', $wegvakken); ?>">
    <input type="hidden" name="stremmingsvakken" value="<?php echo join(',', $stremmingsvakken); ?>">
    <input type="submit" id="submitbutton" value="Opslaan">
    </form> 
    <?php
}
elseif ($_GET['do'] == 'delete') {
	?>
    <h1>Schakeling verwijderen</h1>
    <form method="post">
    <p>Het verwijderen van een schakeling kan niet ongedaan gemaakt worden.</p>
    <input type="hidden" name="delete" value="true">
	<input type="submit" id="submitbutton" value="Schakeling verwijderen"> <a onclick="history.go(-1)">Annuleren</a>
    </form> 
    <?php
}
?>
</div>
</body>
</html>
