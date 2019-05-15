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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - kaart</title>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=api-key"></script>
<script type="text/javascript" src="markerwithlabel_packed.js"></script>
<script type="text/javascript" src="map.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="map.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div style="position: fixed; left: 0; top: 0; width: 100%; height: 100%">
	<div class="map-canvas" id="map-canvas"></div>
</div>

<div id="navigation">
    <ul class="toolbartab">
    	<li><span class="searchbox"><input type="text" id="searchbox" placeholder="Zoeken"></span></li>
    	<li id="scnactive"><span><img src="images/scntype_all.png" width="107" height="16" alt="type scenarios"></span></li>
    	<li id="hectoactive"><span>HM-posities</span></li>
        <li id="dripsactive"><span>DRIPs</span></li>
		<?php if (logincheck() && ($_SESSION['accesslevel'] >= $auth['wegvak_bewerken'])) { ?> <li><a href="wegvak.php?do=edit">Nieuw routedeel</a></li> <?php } ?>
        <li><a href="scenariolijst.php">Scenario's</a></li>
        <?php if (logincheck() && ($_SESSION['accesslevel'] >= $auth['beheer'])) { ?> <li><a href="beheer.php">Beheer</a></li> <?php } ?>
		<?php if (!logincheck()) { ?><li><a href="login.php">Aanmelden</a></li><?php } ?>
        <?php if (logincheck()) { ?><li><a href="account.php">Account</a></li>
        <li><a href="logout.php">Afmelden</a></li><?php } ?>
        <li><a href="help.php" rel="index" id="help">Help</a></li>
    </ul>
</div>

</body>
</html>