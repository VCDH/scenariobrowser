<?php 
/*
 	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
    Copyright (C) 2021 Gemeente Den Haag, Netherlands
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

//include database gegevens
include('dbconnect.inc.php');

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Scenarioversies vergelijken</title>
<?php include ('apikey.cfg.php'); ?>
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
/*
* toon scenario's met opgegeven wegvakken
*/

//controleer wegvakken
$wegvakken_raw = explode(',', $_GET['wegvakken']);
$wegvakken_validated = array();
if (is_array($wegvakken_raw)) {
	foreach($wegvakken_raw as $wegvak) {
		if (is_numeric($wegvak)) {
			$wegvak = (int) $wegvak;
			if (!in_array($wegvak, $wegvakken_validated)) {
				$wegvakken_validated[] = $wegvak;
			}
		}
	}
}

if (!empty($wegvakken_validated)) {
	//geeft lijst met wegvakken
	echo '<p>Scenario\'s met de volgende stremmings- of omleidingsroutedelen:</p>';
	$qry = "SELECT `naam` FROM `".$db['prefix']."wegvakken`
	WHERE `id` IN (" . join(',', $wegvakken_validated) . ")";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		echo '<ul>';
		while ($row = mysqli_fetch_row($res)) {
			echo '<li>';
			echo htmlspecialchars($row[0]);
			echo '</li>';
		}
		echo '</ul>';
	}
	
	//vind scenario's met stremmingsroutedeel
	$scenarios = array();
	$qry = "SELECT distinct `scenario_id` FROM `".$db['prefix']."wegvakmapping`
	WHERE `wegvak_id` IN (" . join(',', $wegvakken_validated) . ")";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		while ($row = mysqli_fetch_row($res)) {
			$scenarios[] = $row[0];
		}
	}
	//vind scenario's met omleidingsroutedeel
	$qry = "SELECT distinct `".$db['prefix']."schakelingen`.`scenario_id` FROM `".$db['prefix']."omleidingmapping`
	LEFT JOIN `".$db['prefix']."schakelingen`
	ON `".$db['prefix']."omleidingmapping`.`schakeling_id` = `".$db['prefix']."schakelingen`.`id`
	LEFT JOIN `".$db['prefix']."scenarioversies`
	ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."schakelingen`.`scenario_id`
	WHERE `wegvak_id` IN (" . join(',', $wegvakken_validated) . ")
	AND `concept` = 0";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		while ($row = mysqli_fetch_row($res)) {
			$scenarios[] = $row[0];
		}
	}

	echo '<table>';
	//get scenarios
	$qry = "SELECT `".$db['prefix']."scenarios`.`id`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarios`.`type`, `".$db['prefix']."scenarios`.`ernst`, `".$db['prefix']."scenarios`.`archief` FROM `".$db['prefix']."scenarios`
	WHERE `id` IN (" . join(',', $scenarios) . ")";
	$qry .= " ORDER BY `".$db['prefix']."scenarios`.`naam`";
	$res = mysqli_query($db['link'], $qry);
	$lijst_scenario = array();
	$lijst_archief = array();
	if (mysqli_num_rows($res)) {
		include('scenariotype.cfg.php');	
		while ($row = mysqli_fetch_row($res)) {
			$li = '<a href="scenario.php?id='.$row[0].'"><img src="images/'.$scenario_types_afbeeldingen[$row[2]].'" class="te" width="16" height="16" alt="'.$scenario_types[$row[2]].'" title="'.$scenario_types[$row[2]].'"><img src="images/'.$scenario_ernsten_afbeeldingen[$row[3]].'" class="te" width="16" height="16" alt="'.$scenario_ernsten[$row[3]].'" title="'.$scenario_ernsten[$row[3]].'">'.htmlspecialchars($row[1]).'</a>';
			if ($row[4] == 1) {
				$lijst_archief[] = $li;
			}
			else {
				$lijst_scenario[] = $li;
			}
		}
	}

	//scenariolijst
	echo '<tr><td>scenario\'s:</td><td>';
	if (!empty($lijst_scenario)) {
		echo '<ul>';
		foreach ($lijst_scenario as $li) {
			echo '<li>';
			echo $li;
			echo '</li>';
		}
		echo '</ul>';
	}
	else {
		echo '(geen)';
	}
	//lijst gearchiveerde scenario's
	echo '</td></tr>';
	if (!empty($lijst_archief)) {
		echo '<tr><td>archief:</td><td>';
		echo '<ul>';
		foreach ($lijst_archief as $li) {
			echo '<li><s>';
			echo $li;
			echo '</s></li>';
		}
		echo '</ul>';
		echo '</td></tr>';
	}
	echo '</table>';
}
else {
	echo '<p>Geen geldige routedelen opgegeven.</p>';
}
?>
</div>
</body>
</html>
