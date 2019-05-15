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
<h1>Kaart</h1>
<p>De kaart beschikt over de standaardfunctionaliteiten van Google Maps. Sleep om het kaartbeeld te verplaatsen en gebruik het scrollwiel om te zoomen. Satellietweergave en Streetview zijn eveneens beschikbaar. Op de kaart worden de beschikbare routedelen weergegeven.</p>

<h1>Routedelen</h1>
<p>Een routedeel is een gedeelte van het beschikbaar wegennet tussen keuzepunten, convergentiepunten of tussenpunten. Een routedeel heeft een richting, het begin van een routedeel is weergegeven door een blauwe bol en het einde van een routedeel is een pijl.</p>
<p>Een routedeel is rood als er scenario's voor dit routedeel beschikbaar zijn, en is grijs wanneer dit niet het geval is. Door met de muis op een routedeel te gaan staan licht dit op en worden extra pijlen weergegeven om de rijrichting aan te geven.</p>
<p>Om de kaart voor wegverkeersleiders overzichtelijk te houden is het mogelijk om routedelen grijs te kleuren wanneer er enkel wegwerkzaamheden- en evenementenscenario's voor dit routedeel beschikbaar zijn. Gebruik de knop met de zes pictogrammen bovenaan om hiertussen te schakelen.</p>
<p>Klik op een routedeel om een popup te openen. In deze popup worden enkele eigenschappen van het routedeel weergegeven en een lijst met scenario's die aan dit routedeel zijn gekoppeld. Hierbij gaat het om scenario's die geschikt zijn bij stremmingen op dit routedeel. Klik op de naam van een scenario om dit te bekijken.</p>

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

<h1>Zoeken</h1>
<p>De zoekfunctie kan gebruikt worden om hectometerposities, scenario's en routedelen te zoeken. De zoekfunctie is niet hoofdlettergevoelig.</p>

<h2>Hectometerposities</h2>
<p>Er wordt automatisch gezocht op hectometerposities wanneer een wegnummer (inclusief A/N), optionele rijrichting en hectometerpunt worden opgegeven. De rijrichting kan geschreven worden als <em>Li</em> en <em>Re</em>, maar ook <em>links</em>, <em>rechts</em> en letters van afritten, parallelbanen of verbindingsbogen kunnen gebruikt worden. Wanneer de rijrichting wordt weggelaten, worden alle rijbanen weergegeven. Het hectometerpunt wordt exact ge&iuml;nterpreteerd en mag &eacute;&eacute;n decimaal bevatten. Spaties tussen letters en cijfers mogen weggelaten worden. Format: <span style="font-family: monospace;">&lt;wegnummer&gt; [li|links|re|rechts|a-z] &lt;hectometer&gt; [a-z]</span></p>
<p>Enkele voorbeelden:</p>
<ul>
<li>A4 Li 46,3</li>
<li>A4 rechts 46.3</li>
<li>A4re46.3</li>
<li>A4 f 46.3</li>
<li>A4 46.3 f</li>
<li>A4 li f 46.3</li>
<li>A4f li46.3</li>
<li>A4li46.3f</li>
</ul>
<p>De gevonden resultaten worden weergegeven in een lijst. Wanneer een gevonden hectometerpositie in rood wordt weergegeven, is er geen routedeel bij deze positie gevonden. Klik op een hectometerpositie om hier naar toe te springen op de kaart. Als er een routedeel bij de positie gevonden is wordt tevens dit routedeel opgelicht op de kaart en de popup van het routedeel geopend op de exacte positie van de aangeklikte hectometerpositie.</p>

<h2>Scenario's en routedelen</h2>
<p>Naast hectometerposities wordt tevens automatisch gezocht op scenario's en routedelen waarbij de opgegeven zoekterm voorkomt in de naam van het scenario of routedeel. Bedenk dat in dit geval spaties en streepjes e.d. op exact dezelfde manier in de naam moeten voorkomen om gevonden te kunnen worden. Klik op de naam van een scenario om dit te openen of op de naam van een routedeel om hierop in te zoomen op de kaart.</p>

<h1>HM-posities weergeven</h1>
<p>Klik op de knop <em>HM-posities</em> om alle hectometerposities op de kaart weer te geven. Naar mate de kaart meer is uitgezoomd worden posities weggelaten om de kaart overzichtelijk te houden.</p>

<h1>DRIPs</h1>
<p>Klik op de knop <em>DRIPs</em> om alle DRIPs in Zuid-West Nederland op de kaart weer te geven. Door op een DRIP te klikken wordt aanvullende informatie over deze DRIP getoond in een popup. In deze popup staat ook een link om alle scenario's die gebruik maken van de betreffende DRIP te zoeken. De DRIPs in deze website zijn een snapshot, een beheerder kan dit bijwerken met de actuele informatie van de DRIP-website.</p>
<?php 
if (permissioncheck('wegvak_bewerken')) {
?>
<h1>Routedelen bewerken en toevoegen</h1>
<p>Bestaande routedelen kunnen worden bewerkt door op het routedeel te klikken en vervolgens op de link <em>routedeel bewerken</em> te klikken in de popup van het routedeel. Nieuwe routedelen kunnen worden toegevoegd via de knop <em>Routedeel toevoegen</em> bovenaan. Gek genoeg kunnen routedelen niet meer worden verwijderd.</p>
<?php
}
if (permissioncheck('scenario_bewerken')) {
?>
<h1>Scenario toevoegen</h1>
<p>Er kan een nieuw scenario worden toegevoegd door op een routedeel te klikken en vervolgens op de link <em>scenario toevoegen</em> te klikken in de popup van het routedeel. Het gekozen routedeel wordt automatisch als  stremmingsroutedeel voor het nieuwe scenario geselecteerd. Automatisch wordt de laatst gebruikte map uit de scenariolijst geselecteerd als opslaglocatie voor het nieuwe scenario. Beide kunnen eventueel daarna nog gewijzigd worden. Nieuwe scenario's kunnen overigens ook vai de scenariolijst worden toegevoegd.</p>
<?php
}
?>