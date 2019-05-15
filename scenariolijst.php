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
/*
if (!logincheck()) {
	header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
    exit;
}
*/
//include database gegevens
include('dbconnect.inc.php');

//controleer of gegeven id bestaat
if (is_numeric($_GET['folder'])) {
	$folder_id = $_GET['folder'];
}
else {
	$folder_id = $_SESSION['folder_id'];
}
$qry = "SELECT `id`, `parent_id` FROM `".$db['prefix']."scenariomappen`
WHERE `id` = '".mysqli_real_escape_string($db['link'], $folder_id)."'";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
	$row = mysqli_fetch_row($res);
	$folder_id = $row[0];
	$parent_id = $row[1];
}
else {
	$folder_id = 0;
}
$_SESSION['folder_id'] = $folder_id;

//bewerk map
if (($_GET['do'] == 'edit') && permissioncheck('scenariomappen_bewerken')) {
	//load data
	if (is_numeric($_GET['id'])) {
		$qry = "SELECT * FROM `".$db['prefix']."scenariomappen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data = mysqli_fetch_assoc($res);
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
			exit;
		}
	}
	//nieuwe toevoegen
	else {
		$qry = "SELECT `id` FROM `".$db['prefix']."scenariomappen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['parent_id'])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data['parent_id'] = mysqli_fetch_row($res);
			$data['parent_id'] = $data['parent_id'][0];
			
		}
		else {
			$data['parent_id'] = 0;
		}
	}
	
	//check post data
	if (!empty($_POST)) {
		$fieldcheck = TRUE;
		//check fields
		if (empty($_POST['naam'])) $fieldcheck = FALSE;
		//save data
		if ($fieldcheck == TRUE) {
			//update existing
			if (is_numeric($data['id'])) {
				//update entry
				$qry = "UPDATE `".$db['prefix']."scenariomappen` SET
				`parent_id` = '".$data['parent_id']."',
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`omschrijving` = '".mysqli_real_escape_string($db['link'], $_POST['omschrijving'])."'
				WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
				if (mysqli_query($db['link'], $qry)) {
					header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php?folder='.$data['parent_id']);
					exit;
				}
			}
			//insert new
			else {
				$qry = "INSERT IGNORE INTO `".$db['prefix']."scenariomappen` SET
				`parent_id` = '".$data['parent_id']."',
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`omschrijving` = '".mysqli_real_escape_string($db['link'], $_POST['omschrijving'])."'";
				if (mysqli_query($db['link'], $qry)) {
					$id = mysqli_insert_id($db['link']);
					header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php?folder='.$data['parent_id']);
					exit;
				}
			}
		}
		else {
			$name = $data['naam'];
			$id = $data['id'];
			$data = $_POST;	
			$data['id'] = $id;
			$data['naam'] = $name;
		}
	}
}
elseif (($_GET['do'] == 'delete') && permissioncheck('scenariomappen_bewerken')) {
	$qry = "SELECT `id`, `parent_id`, `naam` FROM `".$db['prefix']."scenariomappen` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php');
		exit;
	}
	//check post data
	if (!empty($_POST)) {
		//check if removable
		$qry2 = "SELECT `id` FROM `".$db['prefix']."scenarios`
		WHERE `map_id` = '".$data['id']."'
		LIMIT 1";
		$res2 = mysqli_query($db['link'], $qry2);
		if (mysqli_num_rows($res2) == 0) {
			$qry2 = "SELECT `id` FROM `".$db['prefix']."scenariomappen`
			WHERE `parent_id` = '".$data['id']."'
			LIMIT 1";
			$res2 = mysqli_query($db['link'], $qry2);
			if (mysqli_num_rows($res2) == 0) {
				//delete folder
				$qry2 = "DELETE FROM `".$db['prefix']."scenariomappen`
				WHERE `id` = '".$data['id']."'
				LIMIT 1";
				mysqli_query($db['link'], $qry2);
				header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/scenariolijst.php?folder='.$data['parent_id']);
				exit;
			}
		}
		$fieldcheck = FALSE;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Scenariolijst</title>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="navigation">
    <ul class="toolbartab">
        <li><span class="searchbox"><input type="text" id="searchbox" placeholder="Zoeken"></span></li>
        <li><a href="index.php">Kaart</a></li>
        <?php if (logincheck() && ($_SESSION['accesslevel'] >= $auth['beheer'])) { ?> <li><a href="beheer.php">Beheer</a></li> <?php } ?>
		<?php if (!logincheck()) { ?><li><a href="login.php">Aanmelden</a></li><?php } ?>
        <?php if (logincheck()) { ?><li><a href="account.php">Account</a></span></li>
        <li><a href="logout.php">Afmelden</a></li><?php } ?>
        <li><a href="help.php" rel="scenariolijst" id="help">Help</a></li>
    </ul>
</div>

<div id="content">
	
    
<?php 
if ($_GET['do'] == 'edit') {
	if (is_numeric($data['id'])) {
		?>
    	<h1>Map &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; bewerken</h1>
        <?php
	}
	else {
		?>
    	<h1>Nieuwe map toevoegen</h1>
        <?php
	}
	
    if ($fieldcheck === FALSE) {
    	echo '<p class="error">Geef een naam voor de map.</p>';	
    }
    ?>
    <form method="post">
    <input type="hidden" name="coords" value="<?php echo htmlspecialchars($data['coords']); ?>">
    <table>
    <tr><td>bovenliggende map:</td><td><select name="map">
    <?php
	echo '<option value="0"';
	if ($data['parent_id'] == 0) echo ' selected="selected"';
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
			if ($data['parent_id'] == $row[0]) echo ' selected="selected"';
			echo '>';
			for ($i = 0; $i <= $depth; $i++) {
				if ($i == $depth) {
					if ($c == $n) echo '&nbsp;&boxur; ';
					else echo '&nbsp;&boxvr; ';
				}
				else echo '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			echo htmlspecialchars($row[1]).'</option>';
			if ($row[0] != $data['parent_id']) {
				getfolders($row[0], $depth + 1);
			}
		}
	}
	getfolders(0, 0);
	?>
    </select></td></tr>
    <tr><td>naam:</td><td><input type="text" name="naam" value="<?php echo htmlspecialchars($data['naam']); ?>"></td></tr>
    <tr><td>omschrijving:</td><td><input type="text" name="omschrijving" value="<?php echo htmlspecialchars($data['omschrijving']); ?>"></td></tr>
    </table>
    <input type="submit" id="submitbutton" value="Opslaan"> <a href="?folder=<?php echo htmlspecialchars($data['parent_id']); ?>">Annuleren</a>
    </form> 
    <?php
}
elseif ($_GET['do'] == 'delete') {
	?>
	<h1>Map &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; verwijderen</h1>
    <?php
	if ($fieldcheck === FALSE) {
    	echo '<p class="error">Een map kan alleen verwijderd worden als deze leeg is.</p>';	
    }
	?>
    <p>Je staat op het punt om een map te verwijderen. Het verwijderen van een map kan niet ongedaan gemaakt worden.</p>
    <form method="post">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
    <input type="submit" id="submitbutton" value="Verwijderen"> <a href="?folder=<?php echo htmlspecialchars($data['parent_id']); ?>">Annuleren</a>
    </form> 
    <?php
}
else {
	include('scenariotype.cfg.php');
	echo '<h1>Scenario\'s</h1>';
	
	echo '<div style="float:right;">';
	if (permissioncheck('scenariomappen_bewerken')) {
		echo '<a href="?parent_id='.$folder_id.'&amp;do=edit"><span class="ui-icon ui-icon-folder-collapsed"></span> nieuwe map</a>';
		echo ' | ';
	}
	if (permissioncheck('scenario_bewerken')) {
		echo '<a href="scenario.php?folder_id='.$folder_id.'&amp;do=edit"><span class="ui-icon ui-icon-document"></span> nieuw scenario</a>';
	}
	echo '</div>';
	
	//get folder path
	$path_id = $folder_id;
	$folder_path = array();
	while ($path_id > 0) {
		$qry = "SELECT `id`, `parent_id`, `naam` FROM `".$db['prefix']."scenariomappen`
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $path_id)."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$folder_path[] = array($row[0], htmlspecialchars($row[2]));
			$path_id = $row[1];
		}
		else {
			break;
		}
	}
	//add root
	$folder_path[] = array(0, 'Scenario\'s');
	$folder_path = array_reverse($folder_path);
	echo '<p class="folderpath" style="clear: both;">';
	foreach ($folder_path as $item) {
		echo ' &raquo; ';
		if ($folder_id != $item[0]) echo '<a href="?folder='.$item[0].'">';
		echo $item[1];
		if ($folder_id != $item[0]) echo '</a>';
	}
	echo '</p>';
	
	echo '<ul class="scenariolijst">';
	//geeft mappen met parent id
	$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."scenariomappen`
	WHERE `parent_id` = '".$folder_id."'
	ORDER BY `naam`";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		echo '<li><a href="?folder='.$row[0].'"><span class="ui-icon ui-icon-folder-collapsed"></span> <strong>'.htmlspecialchars($row[1]).'</strong></a> ';
		if (permissioncheck('scenariomappen_bewerken')) {
			echo '<a href="?do=edit&amp;id='.$row[0].'" title="Map bewerken"><span class="ui-icon ui-icon-pencil"></span></a>';
			//check if removable
			$qry2 = "SELECT `id` FROM `".$db['prefix']."scenarios`
			WHERE `map_id` = '".$row[0]."'
			LIMIT 1";
			$res2 = mysqli_query($db['link'], $qry2);
			if (mysqli_num_rows($res2) == 0) {
				$qry2 = "SELECT `id` FROM `".$db['prefix']."scenariomappen`
				WHERE `parent_id` = '".$row[0]."'
				LIMIT 1";
				$res2 = mysqli_query($db['link'], $qry2);
				if (mysqli_num_rows($res2) == 0) {
					echo '<a href="?do=delete&amp;id='.$row[0].'" title="Map verwijderen"><span class="ui-icon ui-icon-trash"></span></a>';
				}
			}
		}
		echo '</li>';
		$folders_available = TRUE;
	}
	//geef scenario's met id
	$qry = "SELECT `id`, `naam`, `type`, `ernst`, `archief` FROM `".$db['prefix']."scenarios`
	WHERE `map_id` = '".$folder_id."'";
	if (!logincheck()) {
		$qry.= " AND `archief` = 0";
	}
	$qry.= " ORDER BY `archief`, `naam`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		while ($row = mysqli_fetch_row($res)) {
			echo '<li><span class="ui-icon ui-icon-'.(($row[4] == 1) ? 'suitcase' : 'document').'"></span> <img src="images/'.$scenario_types_afbeeldingen[$row[2]].'" class="te" width="16" height="16" alt="'.$scenario_types[$row[2]].'" title="'.$scenario_types[$row[2]].'"><img src="images/'.$scenario_ernsten_afbeeldingen[$row[3]].'" class="te" width="16" height="16" alt="'.$scenario_ernsten[$row[3]].'" title="'.$scenario_ernsten[$row[3]].'"> <a href="scenario.php?id='.$row[0].'">'.htmlspecialchars($row[1]).'</a></li>';
		}
	}
	elseif ($folders_available !== TRUE) {
		echo '<li>Geen mappen of scenario\'s in deze map.</li>';
	}
	echo '</ul>';
}
?>
</div>
</body>
</html>
