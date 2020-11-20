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
include('dbconnect.inc.php');
mysqli_set_charset($db['link'], "utf8");

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - DRIPs</title>
<?php include ('apikey.cfg.php'); ?>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $cfg_googlemapsapikey; ?>&amp;sensor=false"></script>
<script type="text/javascript" src="markerwithlabel_packed.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="print.css" media="print">
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
        <li><a href="help.php" rel="drip" id="help">Help</a></li>
    </ul>
</div>

<div id="content"> 
<?php 
if ($_GET['do'] == 'scenariobyvms') {
	//vind DRIP naam
	$qry = "SELECT `name` FROM `".$db['prefix']."drips`
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		echo '<h1>DRIP &quot;'.htmlspecialchars($row[0]).'&quot;</h1>';
		echo '<p>';
		if ($_GET['view'] == 'all') {
			echo '<a href="?do=scenariobyvms&amp;id='.htmlspecialchars($_GET['id']).'">Toon alleen recentste definitieve scenarioversies</a>';
		}
		else {
			echo '<a href="?do=scenariobyvms&amp;id='.htmlspecialchars($_GET['id']).'&amp;view=all">Toon alle scenarioversies</a>';
		}
		echo '</p>';
	}
	else {
		echo '<p class="error">Geen DRIP met dit ID.</p>';
	}
	
	include('scenariotype.cfg.php');
	//toon alle
	if ($_GET['view'] == 'all') {
		$qry = "SELECT `".$db['prefix']."schakelingen`.`scenario_id`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarios`.`type`, `".$db['prefix']."scenarios`.`ernst`, `".$db['prefix']."scenarios`.`archief`, `".$db['prefix']."schakelingen`.`id`, `".$db['prefix']."schakelingen`.`naam`, `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."schakelingen`.`scenarioversie`, `".$db['prefix']."scenarioversies`.`concept`
		FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		INNER JOIN `".$db['prefix']."schakelingen`
		ON `".$db['prefix']."scenarioversies`.`scenario_id` = `".$db['prefix']."schakelingen`.`scenario_id` AND `".$db['prefix']."scenarioversies`.`versie` = `".$db['prefix']."schakelingen`.`scenarioversie`
		LEFT JOIN `".$db['prefix']."schakelingdrips`
		ON `".$db['prefix']."schakelingen`.`id` = `".$db['prefix']."schakelingdrips`.`schakeling_id`
		WHERE `".$db['prefix']."schakelingdrips`.`drip_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
		ORDER BY `".$db['prefix']."scenarios`.`archief`, `".$db['prefix']."scenarios`.`naam`";
	}
	//toon enkel meest recente definitief
	else {
		$qry = "SELECT `".$db['prefix']."schakelingen`.`scenario_id`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarios`.`type`, `".$db['prefix']."scenarios`.`ernst`, `".$db['prefix']."scenarios`.`archief`, `".$db['prefix']."schakelingen`.`id`, `".$db['prefix']."schakelingen`.`naam`, `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."schakelingen`.`scenarioversie`, `sv1`.`concept`
		FROM `".$db['prefix']."scenarioversies` AS `sv1`
		LEFT JOIN `".$db['prefix']."scenarioversies` AS `sv2`
		ON `sv1`.`scenario_id` = `sv2`.`scenario_id` AND `sv1`.`versie` < `sv2`.`versie`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `sv1`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		INNER JOIN `".$db['prefix']."schakelingen`
		ON `sv1`.`scenario_id` = `".$db['prefix']."schakelingen`.`scenario_id` AND `sv1`.`versie` = `".$db['prefix']."schakelingen`.`scenarioversie`
		LEFT JOIN `".$db['prefix']."schakelingdrips`
		ON `".$db['prefix']."schakelingen`.`id` = `".$db['prefix']."schakelingdrips`.`schakeling_id`
		WHERE `sv2`.`versie` IS NULL
		AND `sv1`.`concept` = 0
		AND `".$db['prefix']."schakelingdrips`.`drip_id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
		ORDER BY `".$db['prefix']."scenarios`.`archief`, `".$db['prefix']."scenarios`.`naam`";
	}
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		echo '<table>';
		echo '<tr><th>Afbeelding</th><th>Scenario</th><th>Versie</th><th>Schakeling</th></tr>';
		while ($row = mysqli_fetch_row($res)) {
			echo '<tr>';
			echo '<td><img src="store/'.strtoupper(substr($row[7], 0, 1)).'/'.$row[7].'" height="96" alt="DRIP"></td>';
			echo '<td><a href="scenario.php?id='.$row[0].'&amp;versie='.$row[8].'"><img src="images/'.$scenario_types_afbeeldingen[$row[2]].'" class="te" width="16" height="16" alt="'.$scenario_types[$row[2]].'" title="'.$scenario_types[$row[2]].'"><img src="images/'.$scenario_ernsten_afbeeldingen[$row[3]].'" class="te" width="16" height="16" alt="'.$scenario_ernsten[$row[3]].'" title="'.$scenario_ernsten[$row[3]].'">'.htmlspecialchars($row[1]).(($row[4] == 1) ? '<span class="ui-icon ui-icon-suitcase"></span>' : '').'</a></td>';
			echo '<td>'.(($row[9] == '1') ? 'concept' : $row[8]).'</td>';
			echo '<td><a href="scenario.php?id='.$row[0].'&amp;versie='.$row[8].'#'.$row[5].'">'.htmlspecialchars($row[6]).'</a></td>';
			echo '</tr>';
		}
		echo '</table>';
	}
	else {
		echo '<p class="error">Geen scenario\'s met deze DRIP.</p>';
	}
}
?>
</div>
</body>
</html>
