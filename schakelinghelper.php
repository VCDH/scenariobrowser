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

//include database gegevens
include('dbconnect.inc.php');
header('Content-type: application/json');
$json = array();

if ($_GET['get'] == 'drips') {
	//get drips by schakeling
	$qry = "SELECT `".$db['prefix']."schakelingdrips`.`id`, `".$db['prefix']."drips`.`name`, `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."drips`.`latitude`, `".$db['prefix']."drips`.`longitude`, `".$db['prefix']."drips`.`rotation`, `".$db['prefix']."drips`.`status`, `".$db['prefix']."drips`.`owner`, `".$db['prefix']."drips`.`id` FROM `".$db['prefix']."schakelingdrips`
	LEFT JOIN `".$db['prefix']."drips`
	ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
	WHERE `schakeling_id`  = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	ORDER BY `".$db['prefix']."drips`.`owner`, `".$db['prefix']."drips`.`name`";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		//reset rotation if necessary
		if (!is_numeric($row[5])) $row[5] = 0;
		else $row[5] = (int) $row[5];
		//set opacity from status
		if (strtolower($row[6]) != 'bestaand') {
			$opacity = 0.3;
		}
		else {
			$opacity = 1;
		}
		//set line and fill colors
		switch(strtolower($row[7])) {
			case 'gemeente den haag' : $strokecolour = '#155429'; $fillcolour = '#fff'; $driplabelstyle = 'GDH'; break;
			case 'rijkswaterstaat zwn' : $strokecolour = '#000'; $fillcolour = '#c9f'; $driplabelstyle = 'RWS'; break;
			case 'provincie zuid-holland' : $strokecolour = '#000'; $fillcolour = '#c00'; $driplabelstyle = 'PZH'; break;
			case 'gemeente rotterdam' : $strokecolour = '#000'; $fillcolour = '#9AC61E'; $driplabelstyle = 'GRD'; break;
			case 'z\'meer' : $strokecolour = '#0072b9'; $fillcolour = '#ffe00f'; $driplabelstyle = 'GZM'; break;
			default : $strokecolour = '#000'; $fillcolour = '#666'; $driplabelstyle = 'other'; 
		}
		$json[] = array('id' => $row[0], 'name' => (empty($row[1]) ? '(geen)' : htmlspecialchars($row[1])), 'img' => '<img src="store/'.strtoupper(substr($row[2], 0, 1)).'/'.$row[2].'" height="96">', 'lat' => $row[3], 'lng' => $row[4], 'rotation' => $row[5], 'opacity' => $opacity, 'strokecolour' => $strokecolour, 'fillcolour' => $fillcolour, 'drip_id' => $row[8], 'org' => $driplabelstyle);
	}
}
elseif ($_GET['get'] == 'dripdelete') {
	//haal afbeeldingsnaam bij DRIP
	$qry = "SELECT `afbeelding` FROM `".$db['prefix']."schakelingdrips` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	$filename = mysqli_fetch_row($res);
	$filename = $filename[0];
	//verwijder DRIP
	$qry = "DELETE FROM `".$db['prefix']."schakelingdrips`
	WHERE `id`  = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	mysqli_query($db['link'], $qry);
	//verwijder in onbruik geraakte afbeeldingen
	include_once('afbeeldingverwijderen.fct.php');
	afbeelding_verwijderen($filename);
	$json = TRUE;
}
elseif ($_GET['get'] == 'dripselect') {
	//get drips by schakeling
	$qry = "SELECT `id`, `name`, `owner` FROM `".$db['prefix']."drips`
	WHERE `status` != 'Verwijderd'
	ORDER BY `owner`, `name`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$html = '<div id="dripselect">';
		$html .= '<input class="search" placeholder="Zoeken">';
		
		$html .= '<table>';
		$html .= '<tbody class="list">';
		while ($row = mysqli_fetch_row($res)) {
			$html .= '<tr id="dripid_'.$row[0].'"><td class="name">'.htmlspecialchars($row[1]).'</td><td class="org">'.htmlspecialchars($row[2]).'</td></tr>';
		}
		$html .= '</tbody>';
		$html .= '</table></div>';
	}
	else {
		$html = 'Geen DRIPs gevonden.';
	}
	$json['html'] = $html;
}
elseif ($_GET['get'] == 'driprename') {
	//get drips by schakeling
	$qry = "UPDATE `".$db['prefix']."schakelingdrips` SET
	`drip_id` = '".mysqli_real_escape_string($db['link'], $_GET['drip_id'])."'
	WHERE `id`  = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	mysqli_query($db['link'], $qry);
	$json = TRUE;
}

echo json_encode($json, JSON_FORCE_OBJECT);
?>
