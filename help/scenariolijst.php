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
?>
<h1>Scenario's</h1>
<p>Deze pagina toont alle scenario's in een mappenstructuur.</p>

<h1>Navigeren</h1>
<p>Klik op een map om de inhoud hiervan te bekijken. Boven de lijst wordt het pad weergeven, klik op een van de bovenliggende mappen in dit pad om terug te keren naar de betreffende map.</p>

<h1>Scenario's</h1>
<p>Klik op een scenario om dit te bekijken.</p>

<h2>Pictogrammen bij scenario's</h2>
<p>Bij scenario's worden twee pictogrammen weergegeven die het type en de ernst van een scenario aangeven. Als iemand een beter woord weet voor &quot;ernst&quot; is dat van harte welkom. Het Engelse &quot;severity&quot; dekt de lading beter, maar ik wil geen random Engels woord tussendoor in verder Nederlandstalige software. De betekenis van de pictogrammen is hieronder weergegeven:</p>
<ul>
<?php
include('scenariotype.cfg.php');
foreach ($scenario_types as $k => $v) {
	echo '<li><img src="images/'.$scenario_types_afbeeldingen[$k].'" class="te" width="16" height="16" alt="'.$v.'" title="'.$v.'">'.htmlspecialchars($v).'</li>';
}
foreach ($scenario_ernsten as $k => $v) {
	echo '<li><img src="images/'.$scenario_ernsten_afbeeldingen[$k].'" class="te" width="16" height="16" alt="'.$v.'" title="'.$v.'">'.htmlspecialchars($v).'</li>';
}
?>
</ul>

<h2>Gearchiveerde scenario's</h2>
<p>Scenario's kunnen gearchiveerd zijn. Deze worden dan niet op de kaart weergegeven bij de gekoppelde routedelen. Gearchiveerde scenario's staan wel in de lijst op deze pagina, en worden dan voorafgegaan door het pictogram van een attach&eacute;koffer.</p>

<h1>Zoeken</h1>
<p>De zoekfunctie werkt identiek aan de zoekfunctie op de <a href="index.php">kaartweergave</a>.</p>
<?php 
if ($_SESSION['accesslevel'] >= $auth['scenario_bewerken']) {
?>
<h1>Scenario's beheren</h1>
<p>Vanuit deze pagina kunnen nieuwe scenario's worden toegevoegd via de link <em>nieuw scenario</em>. Standaard wordt het scenario dan toegevoegd aan de map die op dat moment geopend is. Scenario's kunnen bewerkt worden vanuit de scenarioweergave, klik daarvoor eerst op een scenario om dit te openen.</p>
<?php 
}
if ($_SESSION['accesslevel'] >= $auth['scenariomappen_bewerken']) {
?>
<h1>Mappen beheren</h1>
<p>Een nieuwe map kan worden toegevoegd via de link <em>nieuwe map</em>. Standaard wordt de map dan toegevoegd aan de map die op dat moment geopend is. De naam van een map kan gewijzigd worden en een map kan verplaatst worden door op het potloodje achter de map in de lijst te klikken. Een map kan verwijderd worden door op het prullenbakje achter de map in de lijst te klikken. Alleen lege mappen kunnen verwijderd worden; als een map niet leeg is wordt het prullenbakje niet weergegeven, om niet de valse suggestie te wekken dat de map verwijderd zou kunnen worden.</p>
<?php
}
?>
