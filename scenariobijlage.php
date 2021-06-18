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


//include database gegevens
include('dbconnect.inc.php');

//download bestand
if ($_GET['do'] == 'getfile') {
	//selecteer groep-id van gebruiker
	$org = 0;
	$qry = "SELECT `organisation` FROM `".$db['prefix']."users`
	WHERE `".$db['prefix']."users`.`id` = '".$_SESSION['id']."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		$org = $row[0];
	}
	//controleer of er een bestand is
	$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`bestand`, `".$db['prefix']."scenariobijlagen`.`grootte`, `".$db['prefix']."scenariobijlagen`.`toegang`, `".$db['prefix']."users`.`organisation`, `".$db['prefix']."scenariobijlagen`.`bestandsnaam` 
	FROM `".$db['prefix']."scenariobijlagen`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `".$db['prefix']."scenariobijlagen`.`id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$row = mysqli_fetch_row($res);
		if (($row[2] <= 1) && !logincheck()) {
			//geen toegang want niet ingelogd
			http_response_code(401); //unauthorized
		}
		elseif (($row[2] == 0) && ($row[3] != $org)) {
			//geen toegang want wel ingelogd, maar geen rechten
			http_response_code(403); //forbidden
		}
		else {
			//content types
			$content_types = array(
			'.doc' => 'application/msword',
			'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'.xls' => 'application/vnc.ms-excel',
			'.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'.pdf' => 'application/pdf',
			'.jpg' => 'image/jpeg',
			'.png' => 'image/png',
			'.gif' => 'image/gif',
			'.bmp' => 'image/bmp',
			'.txt' => 'text/plain'
			);
			//bepaal extensie
			$filetype = strtolower(substr($row[0], strrpos($row[0], '.')));
			$file = 'attachments/'.strtoupper(substr($row[0], 0, 1)).'/'.$row[0];
			if (file_exists($file)) {
				//geef bestand
				header('Content-Description: File Transfer');
				header('Content-Type: '.((array_key_exists($filetype, $content_types)) ? $content_types[$filetype] : 'application/octet-stream'));
				header('Content-Disposition: attachment; filename='.sprintf('"%s"', addcslashes(basename($row[4]), '"\\'))); 
				header('Content-Transfer-Encoding: binary');
				header('Connection: Keep-Alive');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . $row[1]);
				ob_clean();
				readfile($file);
			}
			else {
				http_response_code(404); //not found
			}
		}
	}
	else {
		http_response_code(404); //not found
	}
	exit;
}

//redirect if not logged in
if (!logincheck()) {
	http_response_code(401); //unauthorized
	if (empty($_POST)) header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/index.php');
    exit;
}

//check if authorized
if ($_SESSION['accesslevel'] < $auth['scenario_bewerken']) {
	http_response_code(403); //forbidden
	exit;
}

//selecteer groep-id van gebruiker
$qry = "SELECT `organisation` FROM `".$db['prefix']."users`
WHERE `".$db['prefix']."users`.`id` = '".$_SESSION['id']."'";
$res = mysqli_query($db['link'], $qry);
$row = mysqli_fetch_row($res);
$org = $row[0];

if ($_GET['do'] == 'delete') {
	//bepaal of verwijderd mag worden
	$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`id`, `".$db['prefix']."scenariobijlagen`.`bestand`
	FROM `".$db['prefix']."scenariobijlagen`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `".$db['prefix']."scenariobijlagen`.`id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	AND (`".$db['prefix']."scenariobijlagen`.`toegang` >= 1
	OR `".$db['prefix']."users`.`organisation` = '".$org."')
	ORDER BY `".$db['prefix']."scenariobijlagen`.`bestandsnaam`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$row = mysqli_fetch_row($res);
		//verwijder uit database
		$qry = "DELETE FROM `".$db['prefix']."scenariobijlagen`
		WHERE `id` = '".$row[0]."'";
		mysqli_query($db['link'], $qry);
		//controleer of het bestand nog gebruikt wordt
		$qry = "SELECT `id`
		FROM `".$db['prefix']."scenariobijlagen`
		WHERE `bestand` = '".mysqli_real_escape_string($db['link'], $row[1])."'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res) === 0) {
			//verwijder bestand
			echo $file = 'attachments/'.strtoupper(substr($row[1], 0, 1)).'/'.$row[1];
			@unlink($file);
		}
	}
	exit;
}

elseif ($_GET['do'] == 'setaccess') {
	//controleer of geldig verzoek
	if (!in_array($_GET['value'], array('0', '1', '2'))) {
		http_response_code(400); //bad request
		exit;
	}
	//bepaal of gewijzigd mag worden
	$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`id`
	FROM `".$db['prefix']."scenariobijlagen`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `".$db['prefix']."scenariobijlagen`.`id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	AND (`".$db['prefix']."scenariobijlagen`.`toegang` >= 1
	OR `".$db['prefix']."users`.`organisation` = '".$org."')
	ORDER BY `".$db['prefix']."scenariobijlagen`.`bestandsnaam`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$row = mysqli_fetch_row($res);
		//zet nieuwe status
		$qry = "UPDATE `".$db['prefix']."scenariobijlagen` SET
		`toegang` = '".mysqli_real_escape_string($db['link'], $_GET['value'])."'
		WHERE `id` = '".$row[0]."'";
		mysqli_query($db['link'], $qry);
	}
	exit;
}

elseif ($_GET['do'] == 'setarchive') {
	//controleer of geldig verzoek
	if (!in_array($_GET['value'], array('0', '1'))) {
		http_response_code(400); //bad request
		exit;
	}
	//bepaal of gewijzigd mag worden
	$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`id`
	FROM `".$db['prefix']."scenariobijlagen`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `".$db['prefix']."scenariobijlagen`.`id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'
	AND (`".$db['prefix']."scenariobijlagen`.`toegang` >= 1
	OR `".$db['prefix']."users`.`organisation` = '".$org."')
	ORDER BY `".$db['prefix']."scenariobijlagen`.`bestandsnaam`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$row = mysqli_fetch_row($res);
		//zet nieuwe status
		$qry = "UPDATE `".$db['prefix']."scenariobijlagen` SET
		`archief` = '".mysqli_real_escape_string($db['link'], $_GET['value'])."'
		WHERE `id` = '".$row[0]."'";
		mysqli_query($db['link'], $qry);
	}
	exit;
}

//controleer of er een scenario is met het gegeven id
$qry = "SELECT `id`, `naam` FROM `".$db['prefix']."scenarios` WHERE `id` = '".mysqli_real_escape_string($db['link'], $_GET['id'])."'";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
	$data = mysqli_fetch_assoc($res);
}
else {
	http_response_code(400); //bad request
	exit;
}

if ($_GET['do'] == 'upload') {
	//check if files
	if (empty($_FILES['files']['name'])) {
		http_response_code(400); //bad request
		exit;
	}
	
	$files = array('files' => array());
	
	//handle uploads
	foreach ($_FILES["files"]["error"] as $key => $error) {
		$name = $_FILES["files"]["name"][$key];
		$tmp_name = $_FILES["files"]["tmp_name"][$key];
		$md5 = md5_file($tmp_name);
		$size = $_FILES["files"]["size"][$key];
		$filetype = strtolower(substr($name, strrpos($name, '.')));
		if ($error == UPLOAD_ERR_OK) {
			//check filesize
			if ($size >= 25000*1024) {
				$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Bestand is te groot, maximumgrootte 10 MB.');
			}
			//check filetype
			elseif (!in_array($filetype, array('.doc', '.docx', '.xls', '.xlsx', '.pdf', '.jpg', '.png', '.gif', '.bmp', '.txt'))) {
				$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Bestandstype niet toegestaan.');
			}
			else {
				//insert in database
				$qry = "INSERT INTO `".$db['prefix']."scenariobijlagen` SET
				`scenario_id` = '".$data['id']."',
				`datum` = NOW(),
				`user_id` = '".$_SESSION['id']."',
				`bestandsnaam` = '".mysqli_real_escape_string($db['link'], $_FILES["files"]["name"][$key])."',
				`grootte` = '".$size."',
				`bestand` = '".$md5.$filetype."'";
				mysqli_query($db['link'], $qry);
				$target_file = 'attachments/'.strtoupper(substr($md5, 0, 1)).'/'.$md5.$filetype;
				if (!file_exists($target_file)) {
					//move original file
					move_uploaded_file($tmp_name, $target_file);
				}
				$files['files'][] = array('name' => $name, 'size' => $size, 'url' => $target_file);
			}
		}
		else {
			$files['files'][] = array('name' => $name, 'size' => $size, 'error' => 'Kan bestand niet opslaan');
		}
	}
	header('Content-type: application/json');
	echo json_encode($files, JSON_FORCE_OBJECT);
	exit;
}

elseif ($_GET['do'] == 'getlist') {
	//selecteer bijlagen
	include_once('convertfilesize.fct.php');
	$files = array();
	$qry = "SELECT `".$db['prefix']."scenariobijlagen`.`id`, `".$db['prefix']."scenariobijlagen`.`datum`, `".$db['prefix']."users`.`name`, `".$db['prefix']."scenariobijlagen`.`bestandsnaam`, `".$db['prefix']."scenariobijlagen`.`grootte`, `".$db['prefix']."scenariobijlagen`.`bestand`, `".$db['prefix']."scenariobijlagen`.`toegang`, `".$db['prefix']."scenariobijlagen`.`archief` 
	FROM `".$db['prefix']."scenariobijlagen`
	LEFT JOIN `".$db['prefix']."users`
	ON `".$db['prefix']."scenariobijlagen`.`user_id` = `".$db['prefix']."users`.`id`
	WHERE `".$db['prefix']."scenariobijlagen`.`scenario_id` = '".$data['id']."'
	AND (`".$db['prefix']."scenariobijlagen`.`toegang` >= 1
	OR `".$db['prefix']."users`.`organisation` = '".$org."')
	ORDER BY `".$db['prefix']."scenariobijlagen`.`bestandsnaam`";
	$res = mysqli_query($db['link'], $qry);
	while ($row = mysqli_fetch_row($res)) {
		$files[] = array('id' => $row[0],
		'datum' => $row[1],
		'user' => $row[2],
		'bestandsnaam' => $row[3],
		'grootte' => convertfilesize($row[4]),
		'toegang' => $row[6],
		'archief' => $row[7]);
	}
	header('Content-type: application/json');
	echo json_encode($files);
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Scenariobijlagen</title>
<script type="text/javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<script src="jQuery-File-Upload-9.12.3/js/jquery.iframe-transport.js"></script>
<script src="jQuery-File-Upload-9.12.3/js/jquery.fileupload.js"></script>
<script type="text/javascript" src="scenariobijlage.js"></script>
<script type="text/javascript" src="search.js"></script>
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="navigation">
    <ul class="toolbartab">
        <li><a href="help.php" rel="scenariobijlage" id="help">Help</a></li>
    </ul>
</div>

<div id="content">  

<h1>Bijlagen bij scenario &quot;<?php echo htmlspecialchars($data['naam']); ?>&quot;</h1>
<p><a href="scenario.php?id=<?php echo $data['id']; ?>">&laquo; terug naar scenario</a></p>

<form method="post">

<ul id="bijlagen">
</ul>
<div id="uploadarea" style="clear:both;">
<!-- The fileinput-button span is used to style the file input field as button -->
<span class="fileinput-button">
    <span>Upload bestanden...</span>
    <!-- The file input field used as target for the file upload widget -->
    <input id="fileupload" type="file" name="files[]" multiple>
</span>
<!-- The container for the uploaded files -->
<div id="files" class="files"></div>
</div>

<input type="hidden" name="id" value="<?php echo $data['id']; ?>">
</form> 

</div>
</body>
</html>