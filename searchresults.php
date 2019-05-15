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
//include database gegevens
include('dbconnect.inc.php');
header('Content-type: application/json');
$json = array();
//doorzoek hecto wanneer juiste format
$format = '/^[AN]{1}([0-9]+?)\h*?(Li|Re|links|rechts|[a-z]|(Li|Re|links|rechts)\h+[a-z]|[a-z]\h+(Li|Re|links|rechts)|\h)\h*?(\d+[,\.]*\d*)\h*?([a-z]?)$/Ui';
if (preg_match($format, $_GET['term'], $matches)) {
	//als het laatste deel een letter is, dan gebruik die
	if (!empty($matches[6])) {
		$letter = $matches[6];
	}
	//probeer iets zinvols te maken van middelste deel
	elseif (!empty($matches[2])) {
		if (preg_match('/^([a-z])$/Ui', $matches[2], $letter)) {
			$letter = $letter[1];
		}
		elseif (preg_match('/^(Li|Re|links|rechts)\h+([a-z])$/Ui', $matches[2], $letter)) {
			$letter = $letter[2];
		}
		elseif (preg_match('/^([a-z])\h+(Li|Re|links|rechts)$/Ui', $matches[2], $letter)) {
			$letter = $letter[1];
		}
		elseif (preg_match('/^Li|links$/Ui', $matches[2])) {
			$wol = 'L';
		}
		elseif (preg_match('/^Re|rechts$/Ui', $matches[2])) {
			$wol = 'R';
		}
	}
	//verwerk hecto
	$hecto = round(str_replace(',', '.', $matches[5])*10);
	$km = str_replace(',', '.', $matches[5]);
	//zoek positie
	$qry = "SELECT `wegnummer`, `pos_wol`, `hecto`, `letter`, `lat`, `lon` FROM `".$db['prefix']."hectopunten` 
	WHERE SUBSTRING(`wegnummer`, 2) = '".$matches[1]."'
	AND `hecto` = '".$hecto."'";
	if (!empty($wol)) {
		$qry .= " AND `pos_wol` = '".$wol."'";
	}
	if (!empty($letter)) {
		$qry .= " AND `letter` = '".$letter."'";
	}
	$res = mysqli_query($db['link'], $qry);
	while ($data = mysqli_fetch_row($res)) {
		$arr = array('label' => $data[0].(($data[1] != 'M') ? ' '.$data[1] : '').' '.number_format($data[2]/10, 1).((!empty($data[3])) ? ' '.$data[3] : ''), 'category' => 'hmp', 'latlng' => $data[4].','. $data[5]);
		//zoek wegvak bij positie
		$qry2 = "SELECT `id` FROM `".$db['prefix']."wegvakken` 
		WHERE SUBSTRING(`weg`, 2) LIKE '".$matches[1]."'
		AND 
		((`km_van` <= '".$km."'
		AND `km_tot` >= '".$km."')
		OR
		(`km_tot` <= '".$km."'
		AND `km_van` >= '".$km."'))";
		if (!empty($data[3])) {
			$qry2 .= " AND `ri` LIKE '".$data[3]."'";
		}
		else {
			$qry2 .= " AND `ri` LIKE '".$data[1]."%'";
		}
		$qry2 .= " LIMIT 1";
		$res2 = mysqli_query($db['link'], $qry2);
		if (mysqli_num_rows($res2)) {
			$row2 = mysqli_fetch_row($res2);
			$arr['id'] = $row2[0];
			
		}
		$json[] = $arr;
	}
}

//doorzoek scenario's
include('scenariotype.cfg.php');
//zoek op id
if (is_numeric($_GET['term'])) {
	$qry = "SELECT `id`, `naam`, `type`, `ernst` FROM `".$db['prefix']."scenarios` 
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['term'])."'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		$json[] = array('id' => $row[0], 'label' => htmlspecialchars($row[1]), 'category' => 'scn', 'type' => $row[2], 'ernst' => $row[3]);
	}
}
//zoek op naam
$qry = "SELECT `id`, `naam`, `type`, `ernst` FROM `".$db['prefix']."scenarios` 
WHERE `naam` LIKE '%".mysqli_real_escape_string($db['link'], $_GET['term'])."%'
AND `archief` = 0";
$res = mysqli_query($db['link'], $qry);
while ($row = mysqli_fetch_row($res)) {
	$json[] = array('id' => $row[0], 'label' => htmlspecialchars($row[1]), 'category' => 'scn', 'type' => $row[2], 'ernst' => $row[3]);
}
//doorzoek wegvakken
$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."wegvakken` 
WHERE `naam` LIKE '%".mysqli_real_escape_string($db['link'], $_GET['term'])."%'";
$res = mysqli_query($db['link'], $qry);
while ($row = mysqli_fetch_row($res)) {
	$json[] = array('id' => $row[0], 'label' => htmlspecialchars($row[1]), 'category' => 'wvk');
}
if (empty($json)) {
	$json[] = array('label' => 'Geen resultaten', 'category' => '');
}
echo json_encode($json);
?>