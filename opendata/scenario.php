<?php
if (!is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

include('../dbconnect.inc.php');
include('../scenariotype.cfg.php');

$json = array();

//geef scenario's met id
$qry = "SELECT `id`, `naam`, `type`, `ernst` FROM `".$db['prefix']."scenarios`
WHERE `archief` = 0 
AND `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']). "'
LIMIT 1";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    $row = mysqli_fetch_row($res);
    $json['id'] = (int) $row[0];
    $json['name'] = htmlspecialchars($row[1]);
    $json['type'] = $scenario_types_api[$row[2]];
    $json['severity'] = $scenario_ernsten_api[$row[3]];
    $json['affectedRoadSegments'] = array();
    $json['measureGroups'] = array();
    //haal wegvakken op
    $qry = "SELECT `".$db['prefix']."wegvakken`.`id`, `naam`, `weg`, `ri`, `km_van`, `km_tot`, `coords` FROM `".$db['prefix']."wegvakmapping`
    LEFT JOIN `".$db['prefix']."wegvakken`
    ON `".$db['prefix']."wegvakmapping`.`wegvak_id` = `".$db['prefix']."wegvakken`.`id`
    WHERE `".$db['prefix']."wegvakmapping`.`scenario_id` = '" . mysqli_real_escape_string($db['link'], $json['id']). "'";
    $res = mysqli_query($db['link'], $qry);
    while ($row = mysqli_fetch_row($res)) {
        $affectedRoadSegments = array (
            'id' => (int) $row[0],
            'name' => htmlspecialchars($row[1]),
            'lineString' => htmlspecialchars($row[6])
        );
        if (!empty($row[2])) $affectedRoadSegments['road'] = htmlspecialchars($row[2]);
        if (!empty($row[3])) $affectedRoadSegments['roadDirection'] = htmlspecialchars($row[3]);
        if (!empty($row[4])) $affectedRoadSegments['roadKmFrom'] = (float) $row[4];
        if (!empty($row[5])) $affectedRoadSegments['roadKmTo'] = (float) $row[5];
        
        $json['affectedRoadSegments'][] = $affectedRoadSegments;
    }
    //selecteer meest recente definitieve scenario
    $qry = "SELECT `scenario_id`, `versie`, `omschrijving` FROM `".$db['prefix']."scenarioversies`
    WHERE `scenario_id` = '" . mysqli_real_escape_string($db['link'], $json['id']). "'
    AND `concept` = 0
    ORDER BY `versie` DESC
    LIMIT 1";
    $res = mysqli_query($db['link'], $qry);
    if (mysqli_num_rows($res)) {
        $row = mysqli_fetch_row($res);
        if (!empty($row[2])) $json['description'] = htmlspecialchars($row[2]);
        //selecteer schakelingen
        $qry = "SELECT `id`, `naam`, `omschrijving` FROM `".$db['prefix']."schakelingen`
        WHERE `scenario_id` = '" . mysqli_real_escape_string($db['link'], $row[0]). "'
        AND `scenarioversie` = '" . mysqli_real_escape_string($db['link'], $row[1]). "'";
        $res = mysqli_query($db['link'], $qry);
        while ($row = mysqli_fetch_row($res)) {
            $measureGroup = array (
                'id' => (int) $row[0],
                'detourRoadSegments' => array(),
                'variableMessageSigns' => array()
            );
            if (!empty($row[1])) $measureGroup['name'] = htmlspecialchars($row[1]);
            if (!empty($row[2])) $measureGroup['description'] = htmlspecialchars($row[2]);
            //selecteer omleidingswegvakken
            $qry = "SELECT `".$db['prefix']."wegvakken`.`id`, `naam`, `weg`, `ri`, `km_van`, `km_tot`, `coords` FROM `".$db['prefix']."omleidingmapping`
            LEFT JOIN `".$db['prefix']."wegvakken`
            ON `".$db['prefix']."omleidingmapping`.`wegvak_id` = `".$db['prefix']."wegvakken`.`id`
            WHERE `".$db['prefix']."omleidingmapping`.`schakeling_id` = '" . mysqli_real_escape_string($db['link'], $measureGroup['id']). "'";
            $res = mysqli_query($db['link'], $qry);
            while ($row = mysqli_fetch_row($res)) {
                $detourRoadSegments = array (
                    'id' => (int) $row[0],
                    'name' => htmlspecialchars($row[1]),
                    'lineString' => htmlspecialchars($row[6])
                );
                if (!empty($row[2])) $detourRoadSegments['road'] = htmlspecialchars($row[2]);
                if (!empty($row[3])) $detourRoadSegments['roadDirection'] = htmlspecialchars($row[3]);
                if (!empty($row[4])) $detourRoadSegments['roadKmFrom'] = (float) $row[4];
                if (!empty($row[5])) $detourRoadSegments['roadKmTo'] = (float) $row[5];
                
                $measureGroup['detourRoadSegments'][] = $detourRoadSegments;
            }
            //selecteer DRIPs
            $qry = "SELECT `".$db['prefix']."drips`.`id`, `name`, `latitude`, `longitude`, `rotation`, `afbeelding` FROM `".$db['prefix']."schakelingdrips`
            LEFT JOIN `".$db['prefix']."drips`
            ON `".$db['prefix']."schakelingdrips`.`drip_id` = `".$db['prefix']."drips`.`id`
            WHERE `".$db['prefix']."schakelingdrips`.`schakeling_id` = '" . mysqli_real_escape_string($db['link'], $measureGroup['id']). "'";
            $res = mysqli_query($db['link'], $qry);
            while ($row = mysqli_fetch_row($res)) {
                $measureGroup['variableMessageSigns'][] = array (
                    'id' => (int) $row[0],
                    'name' => htmlspecialchars($row[1]),
                    'lat' => (float) $row[2],
                    'lon' => (float) $row[3],
                    'bearing' => (int) $row[4],
                    'imageUrl' => $_SERVER["REQUEST_SCHEME"] . '://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/', -20)) . '/store/'.strtoupper(substr($row[5], 0, 1)).'/' . htmlspecialchars($row[5])
                );
            }
            
            $json['measureGroups'][] = $measureGroup;
        }
    }
}
else {
    header('HTTP/1.0 404 Not Found');
    exit;
}

header('Content-type: application/json');
echo json_encode($json);
?>