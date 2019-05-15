<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scenariobrowser - Open Data Access</title>
<link rel="stylesheet" type="text/css" href="../style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<div id="content">
<h1>Open Data Access</h1>
<p>Scenariobrowser offers open data access through JSON feeds for most of the data stored in within Scenariobrowser. This page contains the data description for the open data service.</p>

<h2 id="scenariolist">Scenario list</h2>
<p>A list of all currently published scenario's. Archived scenarios are not published in this list. The scenario list is presented as a flat list without the folder structure.</p>
<?php $url = $_SERVER["REQUEST_SCHEME"] . '://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . '/scenario'; ?>
<p><b>URL:</b> <a href="<?php echo $url ?>"><?php echo htmlspecialchars($url); ?></a></p>
<p>The list is presented as an array of objects. Each object has the following elements:</p>
<table>
<tr><td>id</td><td>Unique id of this scenario within this install of scenariobrowser. Ids are not globally unique over multiple instances of scenariobrowser.</td><td>integer</td><td>1..1</td></tr>
<tr><td>name</td><td>Name of the scenario</td><td>string</td><td>1..1</td></tr>
<tr><td><a href="#type">type</a></td><td>Type of traffic situation this scenario applies to.</td><td>enumeration</td><td>RoadWorks, PublicEvent, Accident, Congestion, Tunnel, Bridge, Generic</td></tr>
<tr><td><a href="#severity">severity</a></td><td>Severity of the scenario</td><td>enumeration</td><td>RoadBlocked, RoadPartiallyObstructed, Advisory</td></tr>
<tr><td>scenarioDataUrl</td><td>URL of JSON feed with details of this scenario<i>, see under <a href="#scenarioDataUrl">Scenario details</a></i></td><td>string</td><td>1..1</td></tr>
</table>

<h3 id="type">type</h3>
<table>
<tr><td>RoadWorks</td><td>Road works affecting road, carriageway or lane availability</td></tr>
<tr><td>PublicEvent</td><td>Public event</td></tr>
<tr><td>Accident</td><td>Accident affecting traffic flow</td></tr>
<tr><td>Congestion</td><td>Congestion due to unspecified cause</td></tr>
<tr><td>Tunnel</td><td>Traffic situation in tunnel, usually tunnel closed</td></tr>
<tr><td>Bridge</td><td>Traffic situation on bridge, usually bridge unavailable for through traffic</td></tr>
<tr><td>Generic</td><td>Scenario that does not fit other types</td></tr>
</table>

<h3 id="severity">severity</h3>
<table>
<tr><td>RoadBlocked</td><td>Road or carriageway is completely blocked, generally with provision of a detour</td></tr>
<tr><td>RoadPartiallyObstructed</td><td>One or more lanes are blocked, with provision of a detour</td></tr>
<tr><td>Advisory</td><td>One or more lanes are blocked, without provision of a detour</td></tr>
</table>

<h2 id="scenarioDataUrl">Scenario details</h2>
<p>Detailed information of a specific scenario, including affected road segments, detour road segments and  variable message signs.</p>
<p><b>URL:</b> <?php echo $url . '/$id'; ?><br>
with $id the id of the scenario from the scenario list. The URL is also provided in the scenarioDataUrl element of the scenarios in the scenario list. When an invalid id is provided, the page will return a 404 status code. Moreover, when the id provided is not numeric, the returned status code is 400.</p>
<p>The list is presented as an array of objects. Each object has the following properties:</p>
<table>
<tr><td>id</td><td><i>see explanation under <a href="#scenariolist">Scenario list</a></i></td><td></td><td></td></tr>
<tr><td>name</td><td><i>see explanation under <a href="#scenariolist">Scenario list</a></i></td><td></td><td></td></tr>
<tr><td>type</td><td><i>see explanation under <a href="#scenariolist">Scenario list</a></i></td><td></td><td></td></tr>
<tr><td>severity</td><td><i>see explanation under <a href="#scenariolist">Scenario list</a></i></td><td></td><td></td></tr>
<tr><td>description</td><td>Description of this scenario. May contain conditions under which the scenario may be applied or other information regarding the scenario</td><td>htmlstring</td><td>0..1</td></tr>
<tr><td><a href="#affectedRoadSegments">affectedRoadSegments</a></td><td>Road segments for which this scenario can be used. Road, carriageway or lane obstruction applies to one or more segments provided (but not necessarily all).</td><td>array of objects</td><td>1..1</td></tr>
<tr><td><a href="#measureGroups">measureGroups</a></td><td>A scenario can contain one or more groups of traffic measures</td><td>array of objects</td><td>1..1</td></tr>
</table>

<h3 id="affectedRoadSegments">affectedRoadSegments</h3>
<table>
<tr><td>id</td><td>Unique id of the road segment within this install of scenariobrowser. Ids are not globally unique over multiple instances of scenariobrowser. Encounter of this id in other scenarios means that it is the same road segment.</td><td>integer</td><td>1..1</td></tr>
<tr><td>name</td><td>Name of the road segment</td><td>string</td><td>1..1</td></tr>
<tr><td>road</td><td>Road number or road name</td><td>string</td><td>0..1</td></tr>
<tr><td>roadDirection</td><td>Carriageway identifier</td><td>string</td><td>0..1</td></tr>
<tr><td>roadKmFrom</td><td>Starting kilometre point of segment</td><td>float</td><td>0..1</td></tr>
<tr><td>roadKmTo</td><td>Ending kilometre point of segment</td><td>float</td><td>0..1</td></tr>
<tr><td>lineString</td><td>Comma separated list of lat-long pairs in WGS84 describing the segment for display on a map.</td><td>string</td><td>1..1</td></tr>
</table>

<h3 id="measureGroups">measureGroups</h3>
<table>
<tr><td>id</td><td>Unique id of the measure group within this install of scenariobrowser. Ids are not globally unique over multiple instances of scenariobrowser. A new version of the same scenario will have different measure group ids</td><td>integer</td><td>1..1</td></tr>
<tr><td><a href="#detourRoadSegments">detourRoadSegments</a></td><td>Road segments which comprise the detour(s) for this scenario</td><td>array of objects</td><td>1..1</td></tr>
<tr><td><a href="#variableMessageSigns">variableMessageSigns</a></td><td>Variable Message Signs in this measure group</td><td>array of objects</td><td>1..1</td></tr>
<tr><td>name</td><td>Name of the measure group</td><td>string</td><td>0..1</td></tr>
<tr><td>description</td><td>Description of this measure group. May contain conditions under which the measure group may be applied or other information regarding the measure group</td><td>htmlstring</td><td>0..1</td></tr>
</table>

<h3 id="detourRoadSegments">detourRoadSegments</h3>
<table>
<tr><td>id</td><td>Unique id of the road segment within this install of scenariobrowser. Ids are not globally unique over multiple instances of scenariobrowser. Encounter of this id in other scenarios means that it is the same road segment. affectedRoadSegments and detourRoadSegments share the same namespace.</td><td>integer</td><td>1..1</td></tr>
<tr><td>name</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
<tr><td>road</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
<tr><td>roadDirection</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
<tr><td>roadKmFrom</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
<tr><td>roadKmTo</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
<tr><td>lineString</td><td><i>see explanation under <a href="#affectedRoadSegments">affectedRoadSegments</a></i></td><td></td><td></td></tr>
</table>

<h3 id="variableMessageSigns">variableMessageSigns</h3>
<table>
<tr><td>id</td><td>Unique id of the variable message sign within this install of scenariobrowser. Ids are not globally unique over multiple instances of scenariobrowser. Encounter of this id in other scenarios means that it is the same variable message sign.</td><td>integer</td><td>1..1</td></tr>
<tr><td>name</td><td>Name of the variable message sign</td><td>string</td><td>1..1</td></tr>
<tr><td>lat</td><td>Latitude of the variable message sign in WGS84 for display on a map</td><td>float</td><td>1..1</td></tr>
<tr><td>lon</td><td>Longitude of the variable message sign in WGS84 for display on a map</td><td>float</td><td>1..1</td></tr>
<tr><td>bearing</td><td>Bearing of the variable message sign in WGS84 for display on a map</td><td>int</td><td>1..1</td></tr>
<tr><td>imageURL</td><td>URL linking to an image file for the image that is displayed on this variable message sign when this measure group is activated. This image is not necessarily displayed when this measure group is active, due to conflicting measure groups that may be active simultaneously. For actual displayed images see the feed at <a href="http://opendata.technolution.nl/opendata/displaydata/v1/">http://opendata.technolution.nl/opendata/displaydata/v1/</a></td><td>string</td><td>1..1</td></tr>
</table>

</div>

</body>
</html>