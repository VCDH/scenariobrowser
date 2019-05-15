<?php
include('../dbconnect.inc.php');
include('../scenariotype.cfg.php');

$json = array();

//geef scenario's met id
$qry = "SELECT `id`, `naam`, `type`, `ernst` FROM `".$db['prefix']."scenarios`
WHERE `archief` = 0 
ORDER BY `id`";
$res = mysqli_query($db['link'], $qry);
while ($row = mysqli_fetch_row($res)) {
    $json[] = array (
        'id' => (int) $row[0],
        'name' => htmlspecialchars($row[1]),
        'type' => $scenario_types_api[$row[2]],
        'severity' => $scenario_ernsten_api[$row[3]],
        'scenarioDataUrl' => $_SERVER["REQUEST_SCHEME"] . '://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . '/scenario/' . $row[0]
    );
}

header('Content-type: application/json');
echo json_encode($json);
?>