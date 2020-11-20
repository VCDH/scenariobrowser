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
if (!logincheck() || !($_SESSION['accesslevel'] >= $auth['wegvak_bewerken'])) {
	header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
    exit;
}

include('dbconnect.inc.php');

//create/edit
if ($_GET['do'] == 'edit') {
	$data = array();
	$scenarios = array();
	//load data
	if (is_numeric($_GET['id'])) {
		$qry = "SELECT * FROM `".$db['prefix']."wegvakken` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$data = mysqli_fetch_assoc($res);
			//get scenarios
			$qry = "SELECT `scenario_id` FROM `".$db['prefix']."wegvakmapping`
			WHERE `wegvak_id` = '".$data['id']."'";
			$res = mysqli_query($db['link'], $qry);
			while ($row = mysqli_fetch_row($res)) {
				$scenarios[] = $row[0];
			}
		}
		else {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
			exit;
		}
	}
	
	//check post data
	if (!empty($_POST)) {
		$fieldcheck = TRUE;
		//check fields
		if (empty($_POST['naam'])) $fieldcheck = FALSE;
		if (!preg_match('/^(\(\d+\.\d+, \d+\.\d+\)){1}(,\(\d+\.\d+, \d+\.\d+\))+$/U', $_POST['coords'])) $fieldcheck = FALSE;
		//check scenarios
		$scenarios = array();
		if (!empty($_POST['scenarios'])) {
			$temp_scenarios = explode(',', $_POST['scenarios']);
			foreach ($temp_scenarios as $scenario_this) {
				if (is_numeric($scenario_this)) {
					$scenarios[] = $scenario_this;
				}
				else {
					$fieldcheck = FALSE;
				}
			}
		}
		//save data
		if ($fieldcheck == TRUE) {
			//extract minimum and maximum coordinates
			$lat_min = 360;
			$lat_max = 0;
			$lon_min = 360;
			$lon_max = 0;
			$coords = $_POST['coords'];
			$coords = substr($coords, 1, -1);
			$coords = explode('),(', $coords);
			for ($i = 0; $i < count($coords); $i++) {
				$coords_this = explode(', ', $coords[$i]);
				$lat_min = min($lat_min, $coords_this[0]);
				$lat_max = max($lat_max, $coords_this[0]);
				$lon_min = min($lon_min, $coords_this[1]);
				$lon_max = max($lon_max, $coords_this[1]);
			}
			//update existing
			if (is_numeric($data['id'])) {
				//update entry
				$qry = "UPDATE `".$db['prefix']."wegvakken` SET
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`weg` = '".mysqli_real_escape_string($db['link'], $_POST['weg'])."',
				`ri` = '".mysqli_real_escape_string($db['link'], $_POST['ri'])."',
				`km_van` = '".mysqli_real_escape_string($db['link'], $_POST['km_van'])."',
				`km_tot` = '".mysqli_real_escape_string($db['link'], $_POST['km_tot'])."',
				`coords` = '".mysqli_real_escape_string($db['link'], $_POST['coords'])."',
				`lat_min` = '".mysqli_real_escape_string($db['link'], $lat_min)."',
				`lat_max` = '".mysqli_real_escape_string($db['link'], $lat_max)."',
				`lon_min` = '".mysqli_real_escape_string($db['link'], $lon_min)."',
				`lon_max` = '".mysqli_real_escape_string($db['link'], $lon_max)."'
				WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
				mysqli_query($db['link'], $qry);
			}
			//insert new
			else {
				$qry = "INSERT IGNORE INTO `".$db['prefix']."wegvakken` SET
				`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam'])."',
				`weg` = '".mysqli_real_escape_string($db['link'], $_POST['weg'])."',
				`ri` = '".mysqli_real_escape_string($db['link'], $_POST['ri'])."',
				`km_van` = '".mysqli_real_escape_string($db['link'], $_POST['km_van'])."',
				`km_tot` = '".mysqli_real_escape_string($db['link'], $_POST['km_tot'])."',
				`coords` = '".mysqli_real_escape_string($db['link'], $_POST['coords'])."',
				`lat_min` = '".mysqli_real_escape_string($db['link'], $lat_min)."',
				`lat_max` = '".mysqli_real_escape_string($db['link'], $lat_max)."',
				`lon_min` = '".mysqli_real_escape_string($db['link'], $lon_min)."',
				`lon_max` = '".mysqli_real_escape_string($db['link'], $lon_max)."'";
				mysqli_query($db['link'], $qry);
				$data['id'] = mysqli_insert_id($db['link']);
			}
			//set scenario mapping
			//remove
			if (empty($scenarios)) {
				$qry = "DELETE FROM `".$db['prefix']."wegvakmapping` 
				WHERE `wegvak_id` = '".$data['id']."'";
			}
			else {
				$qry = "DELETE FROM `".$db['prefix']."wegvakmapping` 
				WHERE `wegvak_id` = '".$data['id']."'
				AND `scenario_id` NOT IN (".join(',', $scenarios).")";
			}
			mysqli_query($db['link'], $qry);
			//add
			foreach($scenarios as $scenario_id) {
				$qry = "INSERT IGNORE INTO `".$db['prefix']."wegvakmapping` SET
				`wegvak_id` = '".$data['id']."',
				`scenario_id` = '".$scenario_id."'";
				mysqli_query($db['link'], $qry);
				echo mysqli_error($db['link']);
			}
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php?lookat='.$data['id'].'');
			exit;
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
//split
elseif ($_GET['do'] == 'split') {
	//load data
	$qry = "SELECT * FROM `".$db['prefix']."wegvakken` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
		exit;
	}
}
//split step 2
elseif ($_GET['do'] == 'splitstep2') {
	$data = array();
	//load data
	$qry = "SELECT * FROM `".$db['prefix']."wegvakken` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_POST['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data1 = mysqli_fetch_assoc($res);
		$data2 = $data1;
		
		//splits coordinaten
		if (is_numeric($_POST['splitatindex'])) {
			//section 1
			$coords = $data1['coords'];
			$coords = substr($coords, 1, -1);
			$coords = explode('),(', $coords);
			$data1['coords'] = array_slice($coords, 0, $_POST['splitatindex']);
			if (!preg_match('/^(\(\d+\.\d+, \d+\.\d+\)){1}(,\(\d+\.\d+, \d+\.\d+\))+$/U', $_POST['splitatcoords'])) {
				$data1['coords'][] = substr($_POST['splitatcoords'], 1, -1);
			}
			$data1['coords'] = '(' . join('),(', $data1['coords']) . ')';
			//section 2
			$coords = $data2['coords'];
			$coords = substr($coords, 1, -1);
			$coords = explode('),(', $coords);
			$data2['coords'] = array_slice($coords, $_POST['splitatindex'] + 1);
			if (!preg_match('/^(\(\d+\.\d+, \d+\.\d+\)){1}(,\(\d+\.\d+, \d+\.\d+\))+$/U', $_POST['splitatcoords'])) {
				array_unshift($data2['coords'], substr($_POST['splitatcoords'], 1, -1));
			}
			$data2['coords'] = '(' . join('),(', $data2['coords']) . ')';
		}
	}
	else {
		header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
		exit;
	}
	
	//check post data
	if (!empty($_POST) && !empty($_POST['coords_1']) && !empty($_POST['coords_2'])) {
		$fieldcheck = TRUE;
		//check fields
		if (empty($_POST['naam_1'])) $fieldcheck = FALSE;
		if (empty($_POST['naam_2'])) $fieldcheck = FALSE;
		if (!preg_match('/^(\(\d+\.\d+, \d+\.\d+\)){1}(,\(\d+\.\d+, \d+\.\d+\))+$/U', $_POST['coords_1'])) $fieldcheck = FALSE;
		if (!preg_match('/^(\(\d+\.\d+, \d+\.\d+\)){1}(,\(\d+\.\d+, \d+\.\d+\))+$/U', $_POST['coords_2'])) $fieldcheck = FALSE;
		//save data
		if ($fieldcheck == TRUE) {
			//extract minimum and maximum coordinates
			$lat_min = array();
			$lat_max = array();
			$lon_min = array();
			$lon_max = array();
			//voor beide routedelen
			for ($a = 1; $a <= 2; $a++) {
				$lat_min[$a] = 360;
				$lat_max[$a] = 0;
				$lon_min[$a] = 360;
				$lon_max[$a] = 0;
				$coords = $_POST['coords_'.$a];
				$coords = substr($coords, 1, -1);
				$coords = explode('),(', $coords);
				for ($i = 0; $i < count($coords); $i++) {
					$coords_this = explode(', ', $coords[$i]);
					$lat_min[$a] = min($lat_min[$a], $coords_this[0]);
					$lat_max[$a] = max($lat_max[$a], $coords_this[0]);
					$lon_min[$a] = min($lon_min[$a], $coords_this[1]);
					$lon_max[$a] = max($lon_max[$a], $coords_this[1]);
				}
			}
			//update existing (1)
			$qry = "UPDATE `".$db['prefix']."wegvakken` SET
			`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam_1'])."',
			`weg` = '".mysqli_real_escape_string($db['link'], $_POST['weg_1'])."',
			`ri` = '".mysqli_real_escape_string($db['link'], $_POST['ri_1'])."',
			`km_van` = '".mysqli_real_escape_string($db['link'], $_POST['km_van_1'])."',
			`km_tot` = '".mysqli_real_escape_string($db['link'], $_POST['km_tot_1'])."',
			`coords` = '".mysqli_real_escape_string($db['link'], $_POST['coords_1'])."',
			`lat_min` = '".mysqli_real_escape_string($db['link'], $lat_min[1])."',
			`lat_max` = '".mysqli_real_escape_string($db['link'], $lat_max[1])."',
			`lon_min` = '".mysqli_real_escape_string($db['link'], $lon_min[1])."',
			`lon_max` = '".mysqli_real_escape_string($db['link'], $lon_max[1])."'
			WHERE `id` = '".mysqli_real_escape_string($db['link'], $data1['id'])."'";
			mysqli_query($db['link'], $qry);
			
			//voeg nieuw toe (2)
			$qry = "INSERT IGNORE INTO `".$db['prefix']."wegvakken` SET
			`naam` = '".mysqli_real_escape_string($db['link'], $_POST['naam_2'])."',
			`weg` = '".mysqli_real_escape_string($db['link'], $_POST['weg_2'])."',
			`ri` = '".mysqli_real_escape_string($db['link'], $_POST['ri_2'])."',
			`km_van` = '".mysqli_real_escape_string($db['link'], $_POST['km_van_2'])."',
			`km_tot` = '".mysqli_real_escape_string($db['link'], $_POST['km_tot_2'])."',
			`coords` = '".mysqli_real_escape_string($db['link'], $_POST['coords_2'])."',
			`lat_min` = '".mysqli_real_escape_string($db['link'], $lat_min[2])."',
			`lat_max` = '".mysqli_real_escape_string($db['link'], $lat_max[2])."',
			`lon_min` = '".mysqli_real_escape_string($db['link'], $lon_min[2])."',
			`lon_max` = '".mysqli_real_escape_string($db['link'], $lon_max[2])."'";
			mysqli_query($db['link'], $qry);
			$new_id = mysqli_insert_id($db['link']);
			
			//kopieer wegvakmapping
			$qry = "INSERT INTO `".$db['prefix']."wegvakmapping`
			(`wegvak_id`, `scenario_id`)
			SELECT
			".$new_id.", `scenario_id` FROM `".$db['prefix']."wegvakmapping`
			WHERE `wegvak_id` = '".mysqli_real_escape_string($db['link'], $data1['id'])."'";
			mysqli_query($db['link'], $qry);
			
			//kopieer omleidingmapping
			$qry = "INSERT INTO `".$db['prefix']."omleidingmapping`
			(`wegvak_id`, `schakeling_id`)
			SELECT
			".$new_id.", `schakeling_id` FROM `".$db['prefix']."omleidingmapping`
			WHERE `wegvak_id` = '".mysqli_real_escape_string($db['link'], $data1['id'])."'";
			mysqli_query($db['link'], $qry);
			
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php?lookat='.$data1['id'].'');
			exit;
		}
		else {
			$data1['naam'] = $_POST['naam_1'];	
			$data1['weg'] = $_POST['weg_1'];
			$data1['ri'] = $_POST['ri_1'];
			$data1['km_van'] = $_POST['km_van_1'];
			$data1['km_tot'] = $_POST['km_tot_1'];
			$data1['coords'] = $_POST['coords_1'];
			$data2['naam'] = $_POST['naam_2'];	
			$data2['weg'] = $_POST['weg_2'];
			$data2['ri'] = $_POST['ri_2'];
			$data2['km_van'] = $_POST['km_van_2'];
			$data2['km_tot'] = $_POST['km_tot_2'];
			$data2['coords'] = $_POST['coords_2'];
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Routedeel <?php if (is_numeric($data['id'])) echo 'Bewerken'; else echo 'Toevoegen'; ?></title>
<?php include ('apikey.cfg.php'); ?>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $cfg_googlemapsapikey; ?>&amp;sensor=false"></script>
<script type="text/javascript" src="markerwithlabel_packed.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<?php
if ($_GET['do'] == 'edit') {
?>
<script type="text/javascript" src="wegvak_bewerken.js"></script>
<?php
}
elseif ($_GET['do'] == 'split') {
?>
<script type="text/javascript" src="wegvak_splitsen.js"></script>
<?php
}
elseif ($_GET['do'] == 'splitstep2') {
?>
<script type="text/javascript" src="wegvak_splitsen2.js"></script>
<?php
}
?>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="navigation">
    <ul class="toolbartab">
        <li><a href="help.php" rel="wegvak" id="help">Help</a></li>
    </ul>
</div>

<div id="content">
<?php
//create/edit
if ($_GET['do'] == 'edit') {
	if (is_numeric($data['id'])) {
		?>
    	<h1>Routedeel &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; bewerken</h1>
        <?php
	}
	else {
		?>
    	<h1>Nieuw routedeel toevoegen</h1>
        <?php
	}
	
    if ($fieldcheck === FALSE) {
    	echo '<p class="error">Niet alle verplichte velden zijn ingevuld. Verplicht zijn een naam en een ingetekende lijn.</p>';
		
    }
    ?>
    <form method="post">
    <input type="hidden" name="coords" value="<?php echo htmlspecialchars($data['coords']); ?>">
    <table>
    <tr><td>naam:</td><td><input type="text" name="naam" value="<?php echo htmlspecialchars($data['naam']); ?>"></td></tr>
    <tr><td>weg:</td><td><input type="text" name="weg" value="<?php echo htmlspecialchars($data['weg']); ?>"></td></tr>
    <tr><td>richting:</td><td><input type="text" name="ri" value="<?php echo htmlspecialchars($data['ri']); ?>"></td></tr>
    <tr><td>kilometer van:</td><td><input type="text" name="km_van" value="<?php echo htmlspecialchars($data['km_van']); ?>"></td></tr>
    <tr><td>kilometer tot:</td><td><input type="text" name="km_tot" value="<?php echo htmlspecialchars($data['km_tot']); ?>"></td></tr>
    </table>
    <div class="map-canvas" id="map-canvas" style="width: 900px; height: 600px;"></div>
    <fieldset>
    <legend id="legend_scn"></legend>
    <?php
	//get folders and scenario's in folders
	function getfolders($parent_id) {
		global $db;
		//mappen
		$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."scenariomappen`
		WHERE `parent_id` = '".$parent_id."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				echo '<li id="map_'.$row[0].'"><h3><span class="ui-icon ui-icon-folder-collapsed"></span> '.htmlspecialchars($row[1]).'</h3>';
				//submappen
				echo '<ul class="scenariolijst">';
				getfolders($row[0]);
				echo '</ul>';
				echo '</li>';
			}
		}
		else {
			$no_subfolders = TRUE;
		}
		//scenarios
		$qry2 = "SELECT `id`, `naam`, `archief` FROM `".$db['prefix']."scenarios`
		WHERE `map_id` = '".$parent_id."'";
		$res2 = mysqli_query($db['link'], $qry2);
		if (mysqli_num_rows($res2)) {
			while ($row2 = mysqli_fetch_row($res2)) {
				echo '<li class="scn" id="scn_'.$row2[0].'">'.htmlspecialchars($row2[1]).(($row2[2] == 1) ? ' [archief]' : '').'</li>';
			}
		}
		elseif ($no_subfolders === TRUE) {
			echo '<li>(lege map)</li>';
		}
	}
	echo '<ul class="scenariolijst">';
	getfolders(0);
	echo '</ul>';
	?>
    </fieldset>
    <input type="hidden" name="scenarios" value="<?php echo join(',', $scenarios); ?>">
    <input type="submit" id="submitbutton" value="Opslaan"> <a href="index.php?lookat=<?php echo htmlspecialchars($data['id']); ?>">Annuleren</a>
    </form> 
    <?php
}
//split
elseif ($_GET['do'] == 'split') {
	
	?>
    <h1>Routedeel &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot; splitsen</h1>
    <p>Klik in onderstaande kaart op het routedeel op het punt waar dit gesplitst moet worden.</p>
    
    <form method="post" action="?do=splitstep2">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
    <input type="hidden" name="coords" value="<?php echo htmlspecialchars($data['coords']); ?>">
    <div class="map-canvas" id="map-canvas" style="width: 900px; height: 600px;"></div>
    <input type="hidden" name="splitatcoords">
    <input type="hidden" name="splitatindex">
    <a href="index.php?lookat=<?php echo htmlspecialchars($data['id']); ?>">Annuleren</a>
    </form> 
    <?php
}
//split step 2
elseif ($_GET['do'] == 'splitstep2') {
	?>
	<h1>Routedeel &quot;<?php echo htmlspecialchars($data1['naam']); ?>&quot; splitsen</h1>
	<?php	
    if ($fieldcheck === FALSE) {
    	echo '<p class="error">Niet alle verplichte velden zijn ingevuld. Verplicht zijn een naam en een ingetekende lijn.</p>';
		
    }
    ?>
    
    <p class="warning">Waarschuwing: het splitsen van een routedeel kan niet ongedaan gemaakt worden!</p>
    
    <form method="post">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($data1['id']); ?>">
    
    <h2>Gesplitst routedeel 1</h2>
    <input type="hidden" name="coords_1" value="<?php echo htmlspecialchars($data1['coords']); ?>">
    <table>
    <tr><td>naam:</td><td><input type="text" name="naam_1" value="<?php echo htmlspecialchars($data1['naam']); ?>"></td></tr>
    <tr><td>weg:</td><td><input type="text" name="weg_1" value="<?php echo htmlspecialchars($data1['weg']); ?>"></td></tr>
    <tr><td>richting:</td><td><input type="text" name="ri_1" value="<?php echo htmlspecialchars($data1['ri']); ?>"></td></tr>
    <tr><td>kilometer van:</td><td><input type="text" name="km_van_1" value="<?php echo htmlspecialchars($data1['km_van']); ?>"></td></tr>
    <tr><td>kilometer tot:</td><td><input type="text" name="km_tot_1" value="<?php echo htmlspecialchars($data1['km_tot']); ?>"></td></tr>
    </table>
    <div class="map-canvas" id="map-canvas_1" style="width: 900px; height: 600px;"></div>
    
    <h2>Gesplitst routedeel 2</h2>
    <input type="hidden" name="coords_2" value="<?php echo htmlspecialchars($data2['coords']); ?>">
    <table>
    <tr><td>naam:</td><td><input type="text" name="naam_2" value="<?php echo htmlspecialchars($data2['naam']); ?>"></td></tr>
    <tr><td>weg:</td><td><input type="text" name="weg_2" value="<?php echo htmlspecialchars($data2['weg']); ?>"></td></tr>
    <tr><td>richting:</td><td><input type="text" name="ri_2" value="<?php echo htmlspecialchars($data2['ri']); ?>"></td></tr>
    <tr><td>kilometer van:</td><td><input type="text" name="km_van_2" value="<?php echo htmlspecialchars($data2['km_van']); ?>"></td></tr>
    <tr><td>kilometer tot:</td><td><input type="text" name="km_tot_2" value="<?php echo htmlspecialchars($data2['km_tot']); ?>"></td></tr>
    </table>
    <div class="map-canvas" id="map-canvas_2" style="width: 900px; height: 600px;"></div>
    
    <input type="submit" id="submitbutton" value="Opslaan"> <a href="?do=split&amp;id=<?php echo htmlspecialchars($data1['id']); ?>">Terug</a> <a href="index.php?lookat=<?php echo htmlspecialchars($data1['id']); ?>">Annuleren</a>
    </form> 
    <?php
}
?>
</div>
</body>
</html>
