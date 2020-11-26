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

$drip_url = 'http://assets.vcdh.nl/tabel.php?download=json';

//verwerk gebruikerswijziging
if (($_GET['do'] == 'useredit') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	if (empty($_POST['email'])) $fieldcheck = FALSE;
	if (!is_numeric($_POST['accesslevel']) || ($_POST['accesslevel'] < 0) || ($_POST['accesslevel'] > $_SESSION['accesslevel'])) $fieldcheck = FALSE;
	//check organisation
	$qry = "SELECT `organisation`, `accesslevel` FROM `".$db['prefix']."users`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	$row = mysqli_fetch_row($res);
	//controleer of bewerkt mag worden
	if (!permissioncheck('gebruikers_beheren_alle') && !permissioncheck('gebruikers_beheren_eigen', $row[0])) {
		$fieldcheck = FALSE;
		$gebruiker_gewijzigd = FALSE;
	}
	//sta niet toe om gebruikers te bewerken met hoger accesslevel dan eigen
	if ($row[1] > $_SESSION['accesslevel']) {
		$fieldcheck = FALSE;
		$gebruiker_gewijzigd = FALSE;
	}
	//save data
	if ($fieldcheck == TRUE) {
		//query om rij aan te passen
		$qry = "UPDATE `".$db['prefix']."users`
		SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
		`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
		`phone` = '" . mysqli_real_escape_string($db['link'], $_POST['phone']) . "',
		`accesslevel` = '" . mysqli_real_escape_string($db['link'], $_POST['accesslevel']) . "'
		WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		//voer query uit
		$gebruiker_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
//verwerk organisatiewijziging
if (($_GET['do'] == 'organisationedit') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	if (empty($_POST['email'])) $fieldcheck = FALSE;
	//check organisation
	$qry = "SELECT `id` FROM `".$db['prefix']."organisations`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	$row = mysqli_fetch_row($res);
	if (!permissioncheck('organisaties_beheren_alle') && !permissioncheck('organisaties_beheren_eigen', $row[0])) {
		$fieldcheck = FALSE;
		$organisatie_gewijzigd = FALSE;
	}
	//save data
	if ($fieldcheck == TRUE) {
		if (is_numeric($_GET['id'])) {
			//query om rij aan te passen
			$qry = "UPDATE `".$db['prefix']."organisations`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`afdeling` = '" . mysqli_real_escape_string($db['link'], $_POST['afdeling']) . "',
			`emailadres` = '" . mysqli_real_escape_string($db['link'], $_POST['emailadres']) . "',
			`telefoon` = '" . mysqli_real_escape_string($db['link'], $_POST['telefoon']) . "',
			`allowsignup` = " . (($_POST['allowsignup'] == 'true') ? 'TRUE' : 'FALSE') . "
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		}
		else {
			$qry = "INSERT INTO `".$db['prefix']."organisations`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`afdeling` = '" . mysqli_real_escape_string($db['link'], $_POST['afdeling']) . "',
			`emailadres` = '" . mysqli_real_escape_string($db['link'], $_POST['emailadres']) . "',
			`telefoon` = '" . mysqli_real_escape_string($db['link'], $_POST['telefoon']) . "',
			`allowsignup` = " . (($_POST['allowsignup'] == 'true') ? 'TRUE' : 'FALSE');
		}
		//voer query uit
		$organisatie_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
//drip import
elseif (permissioncheck('dripdb_bijwerken') && ($_GET['do'] == 'dripimport')) {
	$json = file_get_contents($drip_url);
	$json = json_decode($json, TRUE);
	if ($json === NULL) {
		$drip_import_error = 'json';
	}
	else {
		$qry = "TRUNCATE TABLE `".$db['prefix']."dripstemp`";
		mysqli_query($db['link'], $qry);
		
		foreach ($json as $data) {
			//check of het een DRIP is
			if ($data['assettypename'] == 'DRIP') {
				//gebruik template in plaats van type, als template beschikbaar
				if (!empty($data['template'])) {
					$data['type'] = $data['template'];
				}
				//controleer of bestaat, zo niet voeg toe
				$qry = "SELECT * FROM `".$db['prefix']."drips`
				WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['assetid'])."'";
				$res = mysqli_query($db['link'], $qry);
				if (!mysqli_num_rows($res)) {
					$qry = "INSERT INTO `".$db['prefix']."drips` SET
					`id` = '".mysqli_real_escape_string($db['link'], $data['assetid'])."',
					`latitude` = '".mysqli_real_escape_string($db['link'], $data['latitude'])."',
					`longitude` = '".mysqli_real_escape_string($db['link'], $data['longitude'])."',
					`rotation` = '".mysqli_real_escape_string($db['link'], $data['heading'])."',
					`name` = '".mysqli_real_escape_string($db['link'], $data['code'])."',
					`comment` = '".mysqli_real_escape_string($db['link'], $data['memo'])."',
					`road` = '".mysqli_real_escape_string($db['link'], $data['weg'])."',
					`direction` = '".mysqli_real_escape_string($db['link'], $data['richting'])."',
					`position` = '".mysqli_real_escape_string($db['link'], $data['hectometer'])."',
					`code` = '".mysqli_real_escape_string($db['link'], $data['naam'])."',
					`type` = '".mysqli_real_escape_string($db['link'], $data['type'])."',
					`status` = '".mysqli_real_escape_string($db['link'], $data['status'])."',
					`owner` = '".mysqli_real_escape_string($db['link'], $data['aansturing'])."'";
					if (!empty($data['standaardtekst'])) {
						$qry .= ", `defaulttext` = '1'";
					}
					mysqli_query($db['link'], $qry);
				}
				else {
					//controleer wijzigingen
					$oldvalues = mysqli_fetch_assoc($res);
					$changed = FALSE;
					//check of DRIP wordt gebruikt, zo niet dan gewoon bijwerken
					$qry = "SELECT * FROM `".$db['prefix']."schakelingdrips`
					WHERE `drip_id` = '".mysqli_real_escape_string($db['link'], $data['assetid'])."'";
					$res = mysqli_query($db['link'], $qry);
					if (mysqli_num_rows($res)) {
						//drip in gebruik
						//controleer of belangrijke velden gewijzigd zijn.
						if ($oldvalues['name'] != $data['code']) $changed = TRUE;
						if ($oldvalues['status'] != $data['status']) $changed = TRUE;
						if ($oldvalues['owner'] != $data['aansturing']) $changed = TRUE;
						if ($oldvalues['type'] != $data['type']) $changed = TRUE;
						if (abs($oldvalues['latitude'] - $data['latitude']) > 0.01) $changed = TRUE;
						if (abs($oldvalues['longitude'] - $data['longitude']) > 0.006) $changed = TRUE;
						if (abs($oldvalues['rotation'] - $data['heading']) > 15) $changed = TRUE;
					}

					//wanneer naam, status, owner gewijzigd of lat, lng, rot meer dan zoveel gewijzigd, sla op in tijdelijke database om te vragen in wizard
					//anders werk bij in reguliere database
					if ($changed == FALSE) {
						$qry = "UPDATE `".$db['prefix']."drips` SET ";
					}
					else {
						$qry = "INSERT INTO `".$db['prefix']."dripstemp` SET
						`id` = '".mysqli_real_escape_string($db['link'], $data['assetid'])."', ";
					}
					$qry .= "`latitude` = '".mysqli_real_escape_string($db['link'], $data['latitude'])."',
					`longitude` = '".mysqli_real_escape_string($db['link'], $data['longitude'])."',
					`rotation` = '".mysqli_real_escape_string($db['link'], $data['heading'])."',
					`name` = '".mysqli_real_escape_string($db['link'], $data['code'])."',
					`comment` = '".mysqli_real_escape_string($db['link'], $data['memo'])."',
					`road` = '".mysqli_real_escape_string($db['link'], $data['weg'])."',
					`direction` = '".mysqli_real_escape_string($db['link'], $data['richting'])."',
					`position` = '".mysqli_real_escape_string($db['link'], $data['hectometer'])."',
					`code` = '".mysqli_real_escape_string($db['link'], $data['naam'])."',
					`type` = '".mysqli_real_escape_string($db['link'], $data['type'])."',
					`status` = '".mysqli_real_escape_string($db['link'], $data['status'])."',
					`owner` = '".mysqli_real_escape_string($db['link'], $data['aansturing'])."'";
					if (!empty($data['standaardtekst'])) {
						$qry .= ", `defaulttext` = '1'";
					}
					else {
						$qry .= ", `defaulttext` = '0'";
					}
					if ($changed == FALSE) {
						$qry .= " WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['assetid'])."'";
					}
					mysqli_query($db['link'], $qry);
				}
			}
		}
	}
}
//DRIP opslaan
elseif (permissioncheck('dripdb_bijwerken') && ($_GET['do'] == 'dripwizard') && is_numeric($_GET['save'])) {
	//controleer of bestaat, zo ja, sla op
	$qry = "SELECT * FROM `".$db['prefix']."dripstemp`
	WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['save'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		$qry = "UPDATE `".$db['prefix']."drips` SET
		`latitude` = '".mysqli_real_escape_string($db['link'], $data['latitude'])."',
		`longitude` = '".mysqli_real_escape_string($db['link'], $data['longitude'])."',
		`rotation` = '".mysqli_real_escape_string($db['link'], $data['rotation'])."',
		`name` = '".mysqli_real_escape_string($db['link'], $data['name'])."',
		`comment` = '".mysqli_real_escape_string($db['link'], $data['comment'])."',
		`road` = '".mysqli_real_escape_string($db['link'], $data['road'])."',
		`direction` = '".mysqli_real_escape_string($db['link'], $data['direction'])."',
		`position` = '".mysqli_real_escape_string($db['link'], $data['position'])."',
		`code` = '".mysqli_real_escape_string($db['link'], $data['code'])."',
		`type` = '".mysqli_real_escape_string($db['link'], $data['type'])."',
		`status` = '".mysqli_real_escape_string($db['link'], $data['status'])."',
		`owner` = '".mysqli_real_escape_string($db['link'], $data['owner'])."',
		`defaulttext` = '".mysqli_real_escape_string($db['link'], $data['defaulttext'])."'
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
		mysqli_query($db['link'], $qry);
		$qry = "DELETE FROM `".$db['prefix']."dripstemp`
		WHERE `id` = '".mysqli_real_escape_string($db['link'], $data['id'])."'";
		mysqli_query($db['link'], $qry);
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Beheer</title>
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
        <li><a href="index.php">Kaart</a></li>
		<li><a href="scenariolijst.php">Scenario's</a></li>
        <li><a href="account.php">Account</a></li>
        <li><a href="logout.php">Afmelden</a></li>
        <li><a href="help.php" rel="beheer" id="help">Help</a></li>
    </ul>
</div>

<div id="content">
<?php 
if (($_GET['do'] == 'userlist') || ($gebruiker_gewijzigd === TRUE)) {
	echo '<a href="?">Terug</a>';
	echo '<h1>Gebruikers</h1>';
	if ($gebruiker_gewijzigd === TRUE) {
		echo '<p class="success">Gebruikersgegevens gewijzigd!</p>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT `".$db['prefix']."users`.`id` AS `id`, `".$db['prefix']."users`.`name` AS `name`, `".$db['prefix']."users`.`email` AS `email`, `".$db['prefix']."users`.`phone` AS `phone`, `".$db['prefix']."users`.`accesslevel` AS `accesslevel`, `".$db['prefix']."organisations`.`name` AS `organisation`, `".$db['prefix']."users`.`organisation` AS `organisation_id`
	FROM `".$db['prefix']."users`
	LEFT JOIN `".$db['prefix']."organisations`
	ON `".$db['prefix']."users`.`organisation` = `".$db['prefix']."organisations`.`id`";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
        echo '<div id="userlist">';
		echo '<input class="search" placeholder="Zoeken">';
		//start html tabel
		echo '<table>';
		echo '<tr><th>Naam</th><th>Organisatie</th><th>E-mailadres</th><th>Telefoonnummer</th><th>Toegangsniveau</th><th></th></tr>';
		echo '<tbody class="list">';
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td class="name">' . htmlspecialchars($data['name']) . '</td>';
			echo '<td class="org">' . htmlspecialchars($data['organisation']) . '</td>';
			echo '<td>' . htmlspecialchars($data['email']) . '</td>';
			echo '<td>' . htmlspecialchars($data['phone']) . '</td>';
			echo '<td>' . ((array_key_exists($data['accesslevel'], $accesslevel_available)) ? $accesslevel_available[$data['accesslevel']] : htmlspecialchars($data['accesslevel'])) . '</td>';
			echo '<td>';
			if ((permissioncheck('gebruikers_beheren_alle') || permissioncheck('gebruikers_beheren_eigen', $data['organisation_id'])) && ($data['accesslevel'] <= $_SESSION['accesslevel'])) echo '<a href="?do=useredit&amp;id=' . $data['id'] . '">Bewerk</a><!-- <a href="?do=userdelete&amp;id=' . $data['id'] . '">Verwijder</a>-->';
			echo '</td>';
			echo '</tr>';
		}
		//eind html tabel
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		?>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.2.0/list.min.js"></script>
		<script type="text/javascript">
		var userList = new List('userlist', {
			valueNames: [ 'name', 'org' ]
		});
		</script>
		<?php
	}
	//geen resultaten
	else {
		echo '<p>Er zijn geen gebruikers.</p>';
	} 
	if (permissioncheck('gebruikers_beheren_eigen')) {
		echo '<p><a href="?do=useredit">Gebruiker toevoegen</a></p>';
	}
}
//edit user
elseif (permissioncheck('gebruikers_beheren_eigen') && ($_GET['do'] == 'useredit') && ($gebruiker_gewijzigd != TRUE)) {
	if (!is_numeric($_GET['id'])) {
		echo '<h1>Nieuwe gebruiker toevoegen</h1>';
	}
	else {
		echo '<h1>Bewerk gebruiker</h1>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT * FROM `".$db['prefix']."users`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
		$data = mysqli_fetch_assoc($result);
	}
		
	if ($fieldcheck === FALSE) {
		echo '<p class="error">Niet alle velden zijn ingevuld.</p>';
	}
	if ($gebruiker_gewijzigd === FALSE) {
		echo '<p class="error">Kan gebruiker niet opslaan.</p>';
	}
	?>
	<form method="post">
	<table>
	<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
	<tr><td>E-mail:</td><td><input type="text" name="email" value="<?php echo htmlspecialchars($data['email']); ?>"></td></tr>
	<tr><td>Telefoon:</td><td><input type="text" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>"></td></tr>
    <tr><td>Organisatie:</td><td><select name="organisation"><?php
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."organisations`";
	$res = mysqli_query($db['link'], $qry);
	while ($data2 = mysqli_fetch_assoc($res)) {
		echo '<option value="'.$data2['id'].'"';
		if ($data['organisation'] == $data2['id']) echo ' selected="selected"';
		echo '>'.htmlspecialchars($data2['name']).'</option>';	
	}
	?></select></td></tr>
	<tr><td>Toegangsniveau:</td><td><select name="accesslevel"><?php
	foreach ($accesslevel_available as $accesslevel_this => $description_this) {
		echo '<option value="'.$accesslevel_this.'"';
		if ($data['accesslevel'] == $accesslevel_this) echo ' selected="selected"';
		echo '>'.$description_this.'</option>';	
	}
	?></select></td></tr>
	<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?do=userlist">Annuleren</a></td></tr>
	</table>
	</form>
	<?php
}
//organisations list
elseif (($_GET['do'] == 'organisationlist') || ($organisatie_gewijzigd === TRUE)) {
	echo '<a href="?">Terug</a>';
	echo '<h1>Organisaties</h1>';
	if ($organisatie_gewijzigd === TRUE) {
		echo '<p class="success">Organisatiegegevens gewijzigd!</p>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT * FROM `".$db['prefix']."organisations`";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
		//start html tabel
		echo '<div id="userlist">';
		echo '<input class="search" placeholder="Zoeken">';
		echo '<table>';
		echo '<tr><th>Naam</th><th>E-mail-suffix</th><th>Afdeling</th><th>E-mail</th><th>Telefoon</th><th>Reg</th><th></th></tr>';
		echo '<tbody class="list">';
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td class="name">' . htmlspecialchars($data['name']) . '</td>';
			echo '<td>' . htmlspecialchars($data['email']) . '</td>';
			echo '<td>' . htmlspecialchars($data['afdeling']) . '</td>';
			echo '<td>' . htmlspecialchars($data['emailadres']) . '</td>';
			echo '<td>' . htmlspecialchars($data['telefoon']) . '</td>';
			echo '<td>' . (($data['allowsignup'] == '1') ? 'Ja' : 'Nee') . '</td>';
			echo '<td>';
			if (permissioncheck('organisaties_beheren_alle') || permissioncheck('organisaties_beheren_eigen', $data['id'])) echo '<a href="?do=organisationedit&amp;id=' . $data['id'] . '">Bewerk</a>';
			echo '</td>';
			echo '</tr>';
		}
		//eind html tabel
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		?>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.2.0/list.min.js"></script>
		<script type="text/javascript">
		var userList = new List('userlist', {
			valueNames: [ 'name' ]
		});
		</script>
		<?php
	}
	//geen resultaten
	else {
		echo '<p>Er zijn geen organisaties. Da\'s op zich best gek gegeven dat je wel ingelogd bent. Misschien maar eens naar kijken dan...</p>';
	}
	if (permissioncheck('organisaties_beheren_alle')) {
		echo '<p><a href="?do=organisationedit">Organisatie toevoegen</a></p>';
	}
}
//edit organisation
elseif (permissioncheck('organisaties_beheren_eigen') && ($_GET['do'] == 'organisationedit') && ($organisatie_gewijzigd != TRUE)) {
	if (!is_numeric($_GET['id'])) {
		echo '<h1>Organisatie toevoegen</h1>';
		$data['allowsignup'] = '1';
	}
	else {
		echo '<h1>Bewerk organisatie</h1>';
		//query om inhoud van tabel te selecteren
		$sql = "SELECT * FROM `".$db['prefix']."organisations`
		WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		//voer query uit
		$result = mysqli_query($db['link'], $sql);
		//als er een of meer rijen zijn
		if (mysqli_num_rows($result) > 0) {
			$data = mysqli_fetch_assoc($result);
		}
	}
	if (!empty($_POST)) {
		$data = $_POST;
	}
	if ($fieldcheck === FALSE) {
		echo '<p class="error">Niet alle velden zijn ingevuld.</p>';
	}
	if ($gebruiker_gewijzigd === FALSE) {
		echo '<p class="error">Kan organisatie niet opslaan.</p>';
	}
	?>
	<form method="post">
	<table>
	<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
	<tr><td>E-mail-suffix*:</td><td><input type="text" name="email" value="<?php echo htmlspecialchars($data['email']); ?>"></td></tr>
	<tr><td>Afdeling:</td><td><input type="text" name="afdeling" value="<?php echo htmlspecialchars($data['afdeling']); ?>"></td></tr>
	<tr><td>E-mail:</td><td><input type="text" name="emailadres" value="<?php echo htmlspecialchars($data['emailadres']); ?>"></td></tr>
	<tr><td>Telefoon:</td><td><input type="text" name="telefoon" value="<?php echo htmlspecialchars($data['telefoon']); ?>"></td></tr> 
	<tr><td></td><td><input type="checkbox" name="allowsignup" id="inp-cb-allowsignup" value="true"<?php if ($data['allowsignup'] == '1') echo ' checked="checked"' ?>><label for="inp-cb-allowsignup">Account aanmaken openstellen</label></td></tr>
	<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?do=organisationlist">Annuleren</a></td></tr>
	</table>
	</form>
	<p>*) Gedeelte van e-mailadres na @. Gebruikt om te bepalen of gebruikers zichzelf mogen registreren of niet.</p>
	<?php
}
//drips
elseif (permissioncheck('dripdb_bijwerken') && (($_GET['do'] == 'drips') || (!empty($drip_import_error)))) {
	echo '<a href="?">Terug</a>';
	echo '<h1>DRIP database bijwerken</h1>';
	if ($drip_import_error == 'json') {
		echo '<p class="error">Fout bij laden van informatie van '.$drip_url.'</p>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT `id` FROM `".$db['prefix']."dripstemp`";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	$rows = mysqli_num_rows($result);
	if ($rows > 0) {
		echo '<p>Er '.(($rows == 1) ? 'is' : 'zijn').' '.$rows.' niet-verwerkte '.(($rows == 1) ? 'wijziging' : 'wijzigingen').'. <a href="?do=dripwizard">Import-tovenaar hervatten</a>.</p>';
		echo '<p>of</p>';
		echo '<p><a href="?do=dripimport">Nieuwe DRIP-informatie inladen van assets.vcdh.nl en import-tovenaar opnieuw starten</a></p>';
	}
	else {
		echo '<p><a href="?do=dripimport">Import-tovenaar starten</a></p>';
	}
}
//drip import
elseif (permissioncheck('dripdb_bijwerken') && (($_GET['do'] == 'dripwizard') || ($_GET['do'] == 'dripimport'))) {
	echo '<h1>Import-tovenaar</h1>';
	$qry = "SELECT `id` FROM `".$db['prefix']."dripstemp`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		if (!is_numeric($_GET['s'])) {
			$skip = 0;
		}
		else {
			$skip = $_GET['s'];
		}
		$qry = "SELECT * FROM `".$db['prefix']."dripstemp`
		LIMIT ".$skip.", 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			$new = mysqli_fetch_assoc($res);
			//haal oude informatie op
			$qry = "SELECT * FROM `".$db['prefix']."drips`
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $new['id']) . "'
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			$old = mysqli_fetch_assoc($res);
			echo '<h2>DRIP '.$new['id'].'</h2>';
			echo '<table>';
			echo '<tr><th>Veld</th><th>Huidig</th><th>Nieuw</th></tr>';
			$fields = array(
			'Naam' => 'name',
			'Code' => 'code',
			'Weg' => 'road',
			'Richting' => 'direction',
			'Km' => 'position',
			'Lengtegraad' => 'latitude',
			'Breedtegraad' => 'longitude',
			'Rotatie' => 'rotation',
			'Type' => 'type',
			'Status' => 'status',
			'Aansturing' => 'owner',
			'Standaardtekst' => 'defaulttext',
			'Opmerking' => 'comment'
			);
			foreach ($fields as $k => $v) {
				echo '<tr><th>'.$k.'</th><td>'.htmlspecialchars($old[$v]).'</td><td'.(($old[$v] != $new[$v]) ? ' class="highlight"' : '').'>'.htmlspecialchars($new[$v]).'</td></tr>';
			}
			echo '<tr><th></th><td></td><td><a href="http://assets.vcdh.nl/index.php?lookat='.$new['id'].'" target="_blank">Bekijk op kaart</a></td></tr>';
			echo '</table>';
			echo '<p><a href="?do=dripwizard&amp;s='.($skip+1).'"><span class="ui-icon ui-icon-seek-next"></span> Overslaan</a> | <a href="?do=dripwizard&amp;save='.$new['id'].'&amp;s='.$skip.'"><span class="ui-icon ui-icon-disk"></span> DRIP bijwerken</a></p>';
		}
		//einde bereikt, maar er zijn nog niet-geimporteerde
		else {
			echo '<p>Einde van lijst bereikt, maar er zijn nog niet-bijgewerkte DRIPs.</p>';
			echo '<p><a href="?do=dripwizard">&laquo; terug naar begin</a></p>';
			
		}
		echo '<p><a href="?do=drips">Afbreken</a><p>';
	}
	//niets te importeren
	else {
		echo '<p>Goed nieuws! De DRIP-database is volledig bijgewerkt!</p>';
		echo '<p><a href="?">&laquo; terug</a></p>';
	}
}
//main page
else {
	echo '<h1>Beheer</h1>';
	//user mgmt
	if (permissioncheck('gebruikers_beheren_eigen')) {
	?>
		<p><a href="?do=userlist">Gebruikers weergeven</a></p>
	<?php 
	}
	if (permissioncheck('organisaties_beheren_eigen')) { 
	?>
		<p><a href="?do=organisationlist">Organisaties weergeven</a></p>
	<?php
	}
	if (permissioncheck('dripdb_bijwerken')) { 
	?>
		<p><a href="?do=drips">DRIP database bijwerken</a></p>
	<?php
	}
}
?>
</div>
</body>
</html>
