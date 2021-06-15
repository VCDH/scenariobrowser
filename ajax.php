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
include('dbconnect.inc.php');
mysqli_set_charset($db['link'], "utf8");
//map contents
if ($_GET['type'] == 'wvk') {
	$qry = "SELECT DISTINCT `".$db['prefix']."wegvakken`.`id` AS `id`, `".$db['prefix']."wegvakken`.`naam` AS `naam`, `".$db['prefix']."wegvakken`.`coords` AS `coords`, ";
	if ($_GET['all'] == 'true')	$qry .= " (`".$db['prefix']."scenarios`.`archief` = 0) ";
	else $qry .= " ((`".$db['prefix']."scenarios`.`archief` = 0) && (`".$db['prefix']."scenarios`.`type` != 'w') && (`".$db['prefix']."scenarios`.`type` != 'e')) ";
	$qry .= " AS `scn`
	FROM `".$db['prefix']."wegvakken`
	LEFT JOIN `".$db['prefix']."wegvakmapping`
	ON `".$db['prefix']."wegvakken`.`id` = `".$db['prefix']."wegvakmapping`.`wegvak_id`
	LEFT JOIN `".$db['prefix']."scenarios`
	ON `".$db['prefix']."wegvakmapping`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
	ORDER BY `".$db['prefix']."wegvakken`.`naam`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$content = array();
		while ($data = mysqli_fetch_assoc($res)) {
			//unpack coordinates
			$coords = substr($data['coords'], 1, -1);
			$coords = explode('),(', $coords);
			$data['coords'] = array();
			for ($i = 0; $i < count($coords); $i++) {
				$coords_this = explode(', ', $coords[$i]);
				$data['coords'][] = $coords_this[0];
				$data['coords'][] = $coords_this[1];
			}
			//htmlspecialchars name
			$data['naam'] = htmlspecialchars($data['naam']);
			//add to output
			$content[] = $data;
		}
	}
	//return json
	header('Content-type: application/json');
	echo json_encode($content);
}
//infowindow
if ($_GET['type'] == 'infowindow') {
	$qry = "SELECT * FROM `".$db['prefix']."wegvakken` WHERE `id` = '".mysqli_real_escape_string($db['link'],$_GET['id'] )."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		echo '<h1>'.htmlspecialchars($data['naam']).'</h1>';
		echo '<table>';
		echo '<tr><td>weg:</td><td>';
		if (is_numeric(substr($data['weg'], 1))) {
			echo '<span class="wegnummer_'.strtoupper(substr($data['weg'], 0, 1)).'">';
		}
		else {
			echo '<span>';
		}
		echo htmlspecialchars($data['weg']).'</span> '.htmlspecialchars($data['ri']);
		echo '</td></tr>';
		if (!empty($data['km_van']) && !empty($data['km_tot'])) {
			echo '<tr><td>km:</td><td>';
			echo 'van '.number_format($data['km_van'], 1, ',', '.').' tot '.number_format($data['km_tot'], 1, ',', '.');
			echo '</td></tr>';
		}
		//get scenarios by segment
		$qry = "SELECT `".$db['prefix']."scenarios`.`id`, `".$db['prefix']."scenarios`.`naam`, `".$db['prefix']."scenarios`.`type`, `".$db['prefix']."scenarios`.`ernst`, `".$db['prefix']."scenarios`.`archief` FROM `".$db['prefix']."wegvakmapping`
		LEFT JOIN `".$db['prefix']."scenarios`
		ON `".$db['prefix']."wegvakmapping`.`scenario_id` = `".$db['prefix']."scenarios`.`id`
		WHERE `wegvak_id` = '".$data['id']."'";
		if ($_GET['all'] == 'false') $qry .= " AND `".$db['prefix']."scenarios`.`type` != 'w' AND `".$db['prefix']."scenarios`.`type` != 'e'";
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
		if (permissioncheck('wegvak_bewerken')) {
			echo '<p><a href="wegvak.php?do=edit&amp;id='.$data['id'].'"><span class="ui-icon ui-icon-pencil"></span> routedeel bewerken</a> | <a href="wegvak.php?do=split&amp;id='.$data['id'].'"><span class="ui-icon ui-icon-scissors"></span> routedeel splitsen</a></p>';
		}
		echo '<p>';
		if (permissioncheck('scenario_bewerken')) {
			echo '<a href="scenario.php?wegvak_id='.$data['id'].'&amp;do=edit"><span class="ui-icon ui-icon-document"></span> nieuw scenario</a> | ';
		}
		echo '<a href="scenariowegvak.php?wegvakken='.$data['id'].'"><span class="ui-icon ui-icon-search"></span> alle scenario\'s met dit wegvak</a>';
		echo '</p>';
	}
	else {
		echo 'Kan wegvak niet vinden in database.';	
	}
}
//hectopunten
if ($_GET['type'] == 'hecto') {
	//extract minimum and maximum coordinates
	$coords = $_GET['bounds'];
	$coords = substr($coords, 2, -2);
	$coords = explode('), (', $coords);
	for ($i = 0; $i < count($coords); $i++) {
		$coords[$i] = explode(', ', $coords[$i]);
	}
	$qry = "SELECT `wegnummer`, `pos_wol`, `hecto`, `letter`, `lat`, `lon` FROM `".$db['prefix']."hectopunten`
	WHERE `lat` BETWEEN '".$coords[0][0]."' AND '".$coords[1][0]."'
	AND `lon` BETWEEN '".$coords[0][1]."' AND '".$coords[1][1]."'";
	if ($_GET['zoom'] >= 17) {
		//full set
	}
	elseif ($_GET['zoom'] >= 16) {
		$qry .= "AND SUBSTR(`hecto`, -1) IN ('0', '5')";
	}
	elseif ($_GET['zoom'] >= 15) {
		$qry .= "AND SUBSTR(`hecto`, -1) IN ('0', '5')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 14) {
		$qry .= "AND SUBSTR(`hecto`, -1) = '0'
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 13) {
		$qry .= "AND SUBSTR(`hecto`, -2) IN ('00', '25', '50', '75')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 12) {
		$qry .= "AND SUBSTR(`hecto`, -2) IN ('00', '50')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	else { // <= 11
		$qry .= "AND SUBSTR(`hecto`, -2) = '00'
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$content = array();
		while ($data = mysqli_fetch_row($res)) {
			//add to output
			if ($_GET['zoom'] >= 16) {
				$name = $data[0].(($data[1] != 'M') ? ' '.$data[1] : '').' '.number_format($data[2]/10, 1).((!empty($data[3])) ? ' '.$data[3] : '');
			}
			else {
				$name = $data[0].' '.number_format($data[2]/10, 1);
			}
			$content[] = array($data[4], $data[5], $name);
		}
	}
	//return json
	header('Content-type: application/json');
	echo json_encode($content);
}

//drips
if ($_GET['type'] == 'drips') {
	$qry = "SELECT `id`, `latitude`, `longitude`, `rotation`, `name`, `owner`, `status`, `defaulttext` 
	FROM `".$db['prefix']."drips` 
	WHERE `status` != 'Verwijderd'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$content = array();
		while ($data = mysqli_fetch_assoc($res)) {
			if (is_numeric($data['latitude']) && is_numeric($data['longitude'])) {
				//reset rotation if necessary
				if (!is_numeric($data['rotation'])) $data['rotation'] = 0;
				//set status to lowercase
				$data['status'] = strtolower($data['status']);
				//set defaulttext true/false
				if (!empty($data['defaulttext'])) {
					$data['defaulttext'] = 1;
				}
				else {
					$data['defaulttext'] = 0;
				}
				//add to output
				$content[] = $data;
			}
		}
	}
	//return json
	header('Content-type: application/json');
	echo json_encode($content, JSON_FORCE_OBJECT);
}
//drip infowindow
if ($_GET['type'] == 'dripinfo') {
	$qry = "SELECT * FROM `".$db['prefix']."drips`  WHERE `id` = '".mysqli_real_escape_string($db['link'],$_GET['id'] )."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		echo '<h1>'.htmlspecialchars($data['name']).'</h1>';
		echo '<table>';
		if (!empty($data['comment'])) {
			echo '<tr><td>opmerking:</td><td>'.htmlspecialchars($data['comment']).'</td></tr>';
		}
		if (!empty($data['code'])) {
			echo '<tr><td>code:</td><td>'.htmlspecialchars($data['code']).'</td></tr>';
		}
		if (!empty($data['road'])) {
			echo '<tr><td>weg:</td><td>'.htmlspecialchars($data['road']).'</td></tr>';
		}
		if (!empty($data['direction'])) {
			echo '<tr><td>richting:</td><td>'.htmlspecialchars($data['direction']).'</td></tr>';
		}
		if (!empty($data['position'])) {
			echo '<tr><td>km:</td><td>'.htmlspecialchars($data['position']).'</td></tr>';
		}
		echo '<tr><td>template/type:</td><td>'.htmlspecialchars($data['type']).'</td></tr><tr><td>status:</td><td>'.htmlspecialchars($data['status']).'</td></tr><tr><td>aansturing:</td><td>'.htmlspecialchars($data['owner']).'</td></tr>';
		echo '</table>';
		echo '<p><a href="drip.php?do=scenariobyvms&amp;id='.$data['id'].'">Zoek scenario\'s bij DRIP</a></p>';
	}
	else {
		echo 'Kan DRIP niet vinden in database.';	
	}
}
//drip infowindow
if ($_GET['type'] == 'schakelingdripafbeelding') {
	$qry = "SELECT `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."drips`.`name` FROM `".$db['prefix']."schakelingdrips`  
	LEFT JOIN `".$db['prefix']."drips`
	ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
	WHERE `".$db['prefix']."schakelingdrips`.`id` = '".mysqli_real_escape_string($db['link'],$_GET['id'] )."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		echo '<h1>'.htmlspecialchars($row[1]).'</h1>';
		echo '<img src="store/'.strtoupper(substr($row[0], 0, 1)).'/'.$row[0].'" height="96" alt="'.(empty($row[1]) ? '(geen)' : htmlspecialchars($row[1])).'">';
	}
	else {
		echo 'Kan afbeelding niet vinden.';	
	}
}
//mapobjects voor scenarioweergave
if ($_GET['type'] == 'mapobjects') {
	$content = array();
	//selecteer omleidingen bij schakeling
	$qry = "SELECT `".$db['prefix']."wegvakken`.`coords` FROM `".$db['prefix']."omleidingmapping`
	LEFT JOIN `".$db['prefix']."wegvakken`
	ON `".$db['prefix']."omleidingmapping`.`wegvak_id` = `".$db['prefix']."wegvakken`.`id`
	WHERE `".$db['prefix']."omleidingmapping`.`schakeling_id` = '".mysqli_real_escape_string($db['link'],$_GET['sck'])."'";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		$data['type'] = 'line';
		$data['color'] = '#090';
		//unpack coordinates
		$coords = substr($data['coords'], 1, -1);
		$coords = explode('),(', $coords);
		$data['coords'] = array();
		for ($i = 0; $i < count($coords); $i++) {
			$coords_this = explode(', ', $coords[$i]);
			$data['coords'][] = $coords_this[0];
			$data['coords'][] = $coords_this[1];
		}
		//add to output
		$content[] = $data;
	}
	//selecteer wegvakken bij scenario
	$qry = "SELECT `".$db['prefix']."wegvakken`.`coords` FROM `".$db['prefix']."wegvakmapping`
	LEFT JOIN `".$db['prefix']."wegvakken`
	ON `".$db['prefix']."wegvakmapping`.`wegvak_id` = `".$db['prefix']."wegvakken`.`id`
	WHERE `".$db['prefix']."wegvakmapping`.`scenario_id` = '".mysqli_real_escape_string($db['link'],$_GET['scn'])."'";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		$data['type'] = 'line';
		$data['color'] = '#C00';
		//unpack coordinates
		$coords = substr($data['coords'], 1, -1);
		$coords = explode('),(', $coords);
		$data['coords'] = array();
		for ($i = 0; $i < count($coords); $i++) {
			$coords_this = explode(', ', $coords[$i]);
			$data['coords'][] = $coords_this[0];
			$data['coords'][] = $coords_this[1];
		}
		//add to output
		$content[] = $data;
	}
	//selecteer DRIPs bij schakeling
	$qry = "SELECT `".$db['prefix']."schakelingdrips`.`id` AS `id`, `".$db['prefix']."drips`.`latitude` AS `latitude`, `".$db['prefix']."drips`.`longitude` AS `longitude`, `".$db['prefix']."drips`.`rotation` AS `rotation`, `".$db['prefix']."drips`.`name` AS `name`, `".$db['prefix']."drips`.`owner` AS `owner`, `".$db['prefix']."drips`.`status` AS `status` 
	FROM `".$db['prefix']."schakelingdrips` 
	LEFT JOIN `".$db['prefix']."drips` 
	ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
	WHERE `".$db['prefix']."schakelingdrips`.`schakeling_id` = '".mysqli_real_escape_string($db['link'],$_GET['sck'])."'
	AND `".$db['prefix']."schakelingdrips`.`drip_id` IS NOT NULL";
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_assoc($res)) {
		if (is_numeric($data['latitude']) && is_numeric($data['longitude'])) {
			$data['type'] = 'drip';
			//reset rotation if necessary
			if (!is_numeric($data['rotation'])) $data['rotation'] = 0;
			else $data['rotation'] = (int) $data['rotation'];
			$data['name'] = htmlspecialchars($data['name']);
			//set opacity from status
			if (strtolower($data['status']) != 'bestaand') {
				$data['opacity'] = 0.3;
			}
			else {
				$data['opacity'] = 1;
			}
			unset($data['status']);
			//set line and fill colors
			switch(strtolower($data['owner'])) {
				case 'gemeente den haag' : $data['strokecolour'] = '#155429'; $data['fillcolour'] = '#fff'; break;
				case 'rijkswaterstaat zwn' : $data['strokecolour'] = '#000'; $data['fillcolour'] = '#c9f'; break;
				case 'provincie zuid-holland' : $data['strokecolour'] = '#000'; $data['fillcolour'] = '#c00'; break;
				case 'gemeente rotterdam' : $data['strokecolour'] = '#000'; $data['fillcolour'] = '#9AC61E'; break;
				case 'z\'meer' : $data['strokecolour'] = '#0072b9'; $data['fillcolour'] = '#ffe00f'; break;
				default : $data['strokecolour'] = '#000'; $data['fillcolour'] = '#666'; 
			}
			unset($data['owner']);
			//add to output
			$content[] = $data;
		}
	}
	
	//return json
	header('Content-type: application/json');
	echo json_encode($content);
}
?>