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

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Scenarioversies vergelijken</title>
<?php include ('apikey.cfg.php'); ?>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $cfg_googlemapsapikey; ?>&amp;sensor=false"></script>
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
/*
* toon scenario's naast elkaar
*/
//controleer of gegeven id bestaat
if ($scenario_data['id'] > 0) {
	//$row: `id`, `naam`, `type`, `map_id`, `ernst`, `archief`, `organisatie_id`
	$row = $scenario_data;
	
	if (!logincheck() && ($row[5] == 1)) {
		//als niet ingelogd en scenario is archief, dan niet tonen
		echo '<p class="error">Aanmelden is vereist om gearchiveerde scenario\'s te kunnen bekijken.</p>';
	}
	else {
		//controleer versies die getoond moet worden
		$versies = array();
		//eerste versie
		$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie` AS `versie`, `".$db['prefix']."scenarioversies`.`datum` AS `datum`, `".$db['prefix']."users`.`name` AS `username`, `".$db['prefix']."scenarioversies`.`wijzigingen` AS `wijzigingen`, `".$db['prefix']."scenarioversies`.`concept` AS `concept`, `".$db['prefix']."scenarioversies`.`omschrijving` AS `omschrijving` FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."users`
		ON `".$db['prefix']."scenarioversies`.`user_id` = `".$db['prefix']."users`.`id`
		WHERE `scenario_id` = '".$row[0]."'
		AND `versie` = '".mysqli_real_escape_string($db['link'], $_GET['versie1'])."'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$versies[1] = mysqli_fetch_assoc($res);
		}
		else {
			echo 'Geen geldige versie1';
			exit;
		}
		//tweede versie
		$qry = "SELECT `".$db['prefix']."scenarioversies`.`versie` AS `versie`, `".$db['prefix']."scenarioversies`.`datum` AS `datum`, `".$db['prefix']."users`.`name` AS `username`, `".$db['prefix']."scenarioversies`.`wijzigingen` AS `wijzigingen`, `".$db['prefix']."scenarioversies`.`concept` AS `concept`, `".$db['prefix']."scenarioversies`.`omschrijving` AS `omschrijving` FROM `".$db['prefix']."scenarioversies`
		LEFT JOIN `".$db['prefix']."users`
		ON `".$db['prefix']."scenarioversies`.`user_id` = `".$db['prefix']."users`.`id`
		WHERE `scenario_id` = '".$row[0]."'
		AND `versie` = '".mysqli_real_escape_string($db['link'], $_GET['versie2'])."'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$versies[2] = mysqli_fetch_assoc($res);
		}
		else {
			echo 'Geen geldige versie2';
			exit;
		}
		
		echo '<div style="float:right; text-align:right;" class="noprint">';
		echo '<a href="scenario.php?id='.$row[0].'"><span class="ui-icon ui-icon-arrowreturnthick-1-w"></span> terug naar scenario</a>';
		echo '</div>';
		
		echo '<h1>Scenarioversies vergelijken</h1>';
		echo '<p>Schakelingen worden vergeleken op basis van de naam van de schakeling. Als de schakelingnaam is gewijzigd tussen versies, kunnen deze niet goed vergeleken worden.</p>';
		echo '<p>Scenario: <b>'.htmlspecialchars($row[1]).(($row[5] == 1) ? ' (archief)' : '').'</b><br>
		<img src="images/'.$scenario_types_afbeeldingen[$row[2]].'" class="te" width="16" height="16" alt="'.$scenario_types[$row[2]].'" title="'.$scenario_types[$row[2]].'">'.$scenario_types[$row[2]].'<br>';
		echo '<img src="images/'.$scenario_ernsten_afbeeldingen[$row[4]].'" class="te" width="16" height="16" alt="'.$scenario_ernsten[$row[4]].'" title="'.$scenario_ernsten[$row[4]].'">'.$scenario_ernsten[$row[4]].'<br>';
		echo 'Scenario ID: <a href="scenario.php?id='.$row[0].'">'.$row[0].'</a></p>';
		
		//script handler
		echo '<script type="text/javascript" src="scenariobekijken.js"></script>';
		

		//versiehistorie
		echo '<h2>Versiehistorie</h2>';
		echo '<table style="width:100%">';
		echo '<tr><th>Links</th><th>Rechts</th></tr>';
		echo '<tr>';
		foreach($versies as $versie => $val) {
			echo '<td style="width:50%">';
			$table = '<table>
			<tr><th>Versie</th><th>Datum</th><th>Auteur</th><th>Wijzigingen</th></tr>';
			$table .= '<tr><td>';
			$table .= '<a href="scenario.php?id='.$row[0].'&amp;versie='.$val['versie'].'">';
			$table .= (($val['concept'] == 1) ? 'concept' : $val['versie']);
			$table .= '</a>';
			$table .= '</td><td>'.date('d-m-Y', strtotime($val['datum'])).'</td><td>'.htmlspecialchars($val['username']).'</td><td>'.htmlspecialchars($val['wijzigingen']).'</td></tr>';
			$table .= '</table>';
			echo $table;
			echo '</td>';
		}
		echo '</tr></table>';

		echo '<div style="clear:both;"></div>';
		

		//omschrijving
		if (!empty($versies[$scenarioversies[0]]['omschrijving']) || !empty($versies[$scenarioversies[1]]['omschrijving'])) {
			echo '<h2>Omschrijving</h2>';
			echo '<table style="width:100%">';
			echo '<tr';
			if ($versies[$scenarioversies[0]]['omschrijving'] == $versies[$scenarioversies[1]]['omschrijving']) {
				echo ' class="drip_identical"';
			}
			elseif (empty($versies[$scenarioversies[0]]['omschrijving'])) {
				echo ' class="drip_added"';
			}
			elseif (empty($versies[$scenarioversies[1]]['omschrijving'])) {
				echo ' class="drip_removed"';
			}
			else {
				echo ' class="drip_different"';
			}
			echo '>';
			foreach($versies as $versie => $val) {
				echo '<td style="width:50%">';
				echo $val['omschrijving'];
				echo '</td>';
			}
			echo '</tr></table>';
			echo '<div style="clear:both;"></div>';
	}
		
		/*
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
		*/

		//inhoudsopgave
		echo '<div class="newpage"></div>';
		echo '<h2>Inhoudsopgave</h2>';
		echo '<table style="width:100%">';
		echo '<tr>';
		foreach($versies as $versie => $val) {
			echo '<td style="width:50%; vertical-align:top;">';
			$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."schakelingen`
			WHERE `scenario_id` = '".$row[0]."'
			AND `scenarioversie` = '".$val['versie']."'
			ORDER BY `naam`";
			$res = mysqli_query($db['link'], $qry);
			echo '<ul>';
			while ($row2 = mysqli_fetch_row($res)) {
				echo '<li><a href="#'.$row2[0].'">'.(empty($row2[1]) ? htmlspecialchars($row[1]) : htmlspecialchars($row2[1])).'</a></li>';
			}
			echo '</ul>';
			echo '</td>';
		}
		echo '</tr></table>';

		echo '<div style="clear:both;"></div>';

		//vind alle schakelingen van beide versies
		$scenarioversies = array();
		foreach($versies as $versie => $val) {
			$scenarioversies[] = $val['versie'];
		}
		//check of beide versies precies 1 schakeling hebben, vergelijk in dat geval altijd deze twee schakelingen, ongeacht of naam overeen komt
		for ($i=0; $i<=1; $i++) {
			$qry = "SELECT `id` FROM `".$db['prefix']."schakelingen`
			WHERE `scenario_id` = '".$row[0]."'
			AND `scenarioversie` = ".$scenarioversies[$i];
			$res = mysqli_query($db['link'], $qry);
			$num_schakelingen[$i] = mysqli_num_rows($res);
		}
		if (($num_schakelingen[0] == 1) && ($num_schakelingen[1] == 1)) {
			$precies1schakelingperversie = TRUE;
		}
		else {
			$precies1schakelingperversie = FALSE;
		}
		//haal alle schakelingen op
		$qry = "SELECT `id`, `naam`, `omschrijving`, `scenarioversie` FROM `".$db['prefix']."schakelingen`
		WHERE `scenario_id` = '".$row[0]."'
		AND `scenarioversie` IN (".join(',',$scenarioversies).")
		ORDER BY `scenarioversie`, `naam`";
		$res = mysqli_query($db['link'], $qry);
		$schakelingen = array();
		while ($row2 = mysqli_fetch_assoc($res)) {
			if ($precies1schakelingperversie == TRUE) {
				$row2['naam'] = '';
			}
			$schakelingen[$row2['naam']][$row2['scenarioversie']] = $row2;
		}

		//loop door schakelingen
		foreach ($schakelingen as $schakelingnaam => $schakeling) {
			echo '<div class="newpage"></div>';
			echo '<h1>'.((empty($schakelingnaam)) ? '(geen naam opgegeven)' : htmlspecialchars($schakelingnaam)).'</h1>';
			echo '<table style="width:100%">';
			echo '<tr>';
			$drips = array();
			foreach ($scenarioversies as $scenarioversie) {
				echo '<td style="width:50%; vertical-align:top;">';
				if (is_array($schakeling[$scenarioversie])) {
					echo '<a name="'.$schakeling[$scenarioversie]['id'].'"></a>';
					//kaart
					echo '<div id="map_'.$row[0].'_'.$schakeling[$scenarioversie]['id'].'" class="map-canvas" style="width: 430px; height: 500px;"></div>';
					//drips
					//get drips by schakeling
					$qry3 = "SELECT `".$db['prefix']."schakelingdrips`.`id`, `".$db['prefix']."drips`.`name`, `".$db['prefix']."schakelingdrips`.`afbeelding`, `".$db['prefix']."drips`.`owner`, `".$db['prefix']."schakelingdrips`.`drip_id` 
					FROM `".$db['prefix']."schakelingdrips`
					LEFT JOIN `".$db['prefix']."drips`
					ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
					WHERE `schakeling_id`  = '".mysqli_real_escape_string($db['link'], $schakeling[$scenarioversie]['id'])."'
					ORDER BY `".$db['prefix']."drips`.`owner`, `".$db['prefix']."drips`.`name`";
					$res3 = mysqli_query($db['link'], $qry3);
					if (mysqli_num_rows($res3)) {
						while ($row3 = mysqli_fetch_row($res3)) {
							$drips[$row3[4]][$scenarioversie] = $row3;
						}
					}
				}
				echo '</td>';
			}
			echo '</tr>';
			
			//DRIPs
			foreach ($drips as $dripid => $drip) {
				echo '<tr';
				if ($drip[$scenarioversies[0]][2] == $drip[$scenarioversies[1]][2]) {
					echo ' class="drip_identical"';
				}
				elseif (empty($drip[$scenarioversies[0]][2])) {
					echo ' class="drip_added"';
				}
				elseif (empty($drip[$scenarioversies[1]][2])) {
					echo ' class="drip_removed"';
				}
				else {
					echo ' class="drip_different"';
				}
				echo '>';
				foreach ($scenarioversies as $scenarioversie) {
					echo '<td style="width:50%; vertical-align:top;">';
					if (!empty($drip[$scenarioversie])) {
						switch (strtolower($drip[$scenarioversie][3])) {
							case 'gemeente den haag':
								$driplabelstyle = 'GDH'; break;
							case 'provincie zuid-holland':
								$driplabelstyle = 'PZH'; break;
							case 'gemeente rotterdam':
								$driplabelstyle = 'GRD'; break;
							case 'rijkswaterstaat zwn':
								$driplabelstyle = 'RWS'; break;
							default: 
								$driplabelstyle = 'other'; break;
						}
		
						echo '<div class="drips';
						switch ($scenarioversie) {
							case $scenarioversies[0]:
								echo ' floatright'; break;
							default:
								echo ' floatleft';
						}
						echo '"><div class="drip_name org org-'.$driplabelstyle.'">'.(empty($drip[$scenarioversie][1]) ? '(geen)' : htmlspecialchars($drip[$scenarioversie][1])).'</div><div class="drip_image"><img src="store/'.strtoupper(substr($drip[$scenarioversie][2], 0, 1)).'/'.$drip[$scenarioversie][2].'" height="96" alt="'.(empty($drip[$scenarioversie][1]) ? '(geen)' : htmlspecialchars($drip[$scenarioversie][1])).'"></div></div>';
					}
					echo '</td>';
				}
				echo '</tr>';
			}

			if (!empty($schakeling[$scenarioversies[0]]['omschrijving']) || !empty($schakeling[$scenarioversies[1]]['omschrijving'])) {
				echo '<tr';
				if ($schakeling[$scenarioversies[0]]['omschrijving'] == $schakeling[$scenarioversies[1]]['omschrijving']) {
					echo ' class="drip_identical"';
				}
				elseif (empty($schakeling[$scenarioversies[0]]['omschrijving'])) {
					echo ' class="drip_added"';
				}
				elseif (empty($schakeling[$scenarioversies[1]]['omschrijving'])) {
					echo ' class="drip_removed"';
				}
				else {
					echo ' class="drip_different"';
				}
				echo '>';
				//omschrijving
				foreach ($scenarioversies as $scenarioversie) {
					echo '<td style="width:50%; vertical-align:top;">';
					if (is_array($schakeling[$scenarioversie])) {
						//omschrijving
						if (!empty($schakeling[$scenarioversie]['omschrijving'])) {
							echo $schakeling[$scenarioversie]['omschrijving'];
						}
					}
					echo '</td>';
				}
				echo '</tr>';
			}
			
			echo '</table>';
		}
	}
}
else {
	echo '<p>Geen scenario met opgegeven ID.</p>';
}
?>
</div>
</body>
</html>
