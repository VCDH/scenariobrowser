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
<h1>Scenario</h1>
<p>Een scenario bestaat uit een algemeen deel met daaronder &eacute;&eacute;n of meerdere schakelingen.</p>

<h1>Versiehistorie</h1>
<p>Van een scenario kunnen meerdere versies bestaan. Alle versies worden getoond in de tabel en per versie is te zien wie deze heeft opgeslagen. Standaard wordt de meest recente versie/concept getoond. Klik op het versienummer van een scenario om een andere versie te bekijken.</p>


<h1>Gekoppelde stremmingsroutedelen</h1>
<p>Hier wordt een lijst gegeven van de routedelen die aan dit scenario zijn gekoppeld. Deze routedelen worden ook in kaartjes van iedere schakeling getekend in een rode kleur. Op de kaart wordt dit scenario getoond in de popup van de routedelen die hier zijn geselecteerd.</p>

<h1>Schakelingen</h1>
<p>Een schakeling bestaat uit een kaartje met daaronder de DRIP afbeeldingen die bij de schakeling horen. Op het kaartje worden de stremmingsroutedelen in rood weergegeven en omleidingsroutedelen in groen. Daarnaast worden de locaties van de DRIPs die bij de schakeling horen weergegeven. Klik op de kaart (niet op een object op de kaart) om zoomen middels het scrollwiel te activeren. Dit blijft actief zolang de muisaanwijzer binnen de kaart blijft. Door met de muisaanwijzer op een DRIP te gaan staan wordt de DRIP afbeelding getoond voor deze DRIP in deze schakeling.</p>

<h1>Beeldstanden downloaden</h1>
<p>Klik op de link <em>download beeldstanden</em> rechtsboven in het algemene deel van het scenario om alle DRIP afbeeldingen van alle schakelingen van het scenario te downloaden in een ZIP-bestand.</p>

<?php 
if ($_SESSION['accesslevel'] >= $auth['scenario_bewerken']) {
?>
<h1>Scenario bewerken/toevoegen</h1>
<p>Het aanmaken en bewerken van een scenario is gesplitst in het algemene deel van het scenario en de afzonderlijke schakelingen. In het algemene deel worden naam, type en ernst van het scenario vastgelegd. Deze blijven constant over alle versies van het scenario en wijzigingen hierin worden toegepast op alle vorige versies van het scenario. Deze velden zijn verplicht. Routedelen worden aan het scenario gekoppeld door deze aan te klikken op de kaart of te selecteren in de uitklaplijst onder de kaart. Er kunnen meerdere routedelen aan een scenario worden gekoppeld. Het gaat bij hier om stremmingsvakken waarop het scenario van toepassing is. De routedelen zijn weer constant over alle versies. Het bewerken van deze basisinformatie van een scenario kan alleen door personen van de eigen organisatie.</p>

<h1>Scenario dupliceren</h1>
<p>Een nieuw scenario kan vanaf nul worden gemaakt, maar een bestaand scenario kan ook als basis worden genomen voor een nieuw scenario. Gebruik hiervoor de knop <em>dupliceren</em>. Hierna kan worden aangegeven of het hele scenario moet worden overgenomen, of alleen bepaalde onderdelen.</p>

<?php 
if ($_SESSION['accesslevel'] >= $auth['scenario_verwijderen']) {
?>
<h1>Scenario verwijderen</h1>
<p>Een scenario kan verwijderd worden, maar ben hier voorzichtig mee. Het scenario en alle bijbehorende schakelingen wordt namelijk onherstelbaar verwijderd. In veel gevallen zal het onwenselijk zijn om waardevolle historie te vernietigen; het is beter om oude scenario's te archiveren (zie hierboven). Het verwijderen van een scenario kan niet per ongeluk, er wordt om een bevestiging gevraagd die na een wachttijd van vijf seconden nogmaals bevestigd moet worden.</p>
<?php 
}
?>

<h1>Scenario beschikbaar maken/archiveren</h1>
<p>Gearchiveerde scenario's worden niet weergegeven op de kaart, worden niet gevonden door de zoekfunctie en kunnen niet worden bekeken door niet-aangemelde gebruikers. Een nieuw scenario staat standaard op &quot;archief&quot; en moet dus beschikbaar gemaakt worden wanneer dit klaar is. In onbruik geraakte scenario's kunnen weer in archief geplaatst worden. Zo blijft de website bruikbaar voor wegverkeersleiders, zonder scenario's te hoeven verwijderen.</p>

<h1>Beheer overdragen</h1>
<p>Het beheer, dus de rechten om de basisinformatie van een scenario te bewerken, kan worden overgedragen aan een andere organisatie. Onderdelen die hierboven genoemd zijn onder <em>Scenario bewerken/toevoegen</em> kunnen dan zelf niet meer bewerkt worden.</p>

<h1>Versiehistorie bewerken</h1>
<p>Hier kan worden opgegeven wat de wijzigingen zijn ten opzichte van de vorige versie van het scenario. Dit wordt dan getoond in de tabel <em>versiehistorie</em>. Datum en auteur worden automatisch geregisteerd. Bij concepten worden alleen de datum en auteur van de meest recente wijzigingen getoond, daar deze gebruiker hiermee feitelijk de verantwoordelijkheid voor het concept over neemt.</p>

<h1>Scenario definitief maken</h1>
<p>Wanneer een conceptscenario gereed is kan dit definitief worden gemaakt. Het scenario is dan te bekijken zonder in te loggen. <?php if ($_SESSION['accesslevel'] < $auth['scenario_finalizeren']) { ?> Je kunt zelf geen scenario's definitief maken, vraag een collega die dit wel kan als het scenario klaar is.<?php } ?> Het wordt aangeraden om scenario's pas definitief te maken wanneer deze inzetbaar zijn in het netwerkmanagementsysteem.</p>

<h1>Nieuw concept maken</h1>
<p>Wanneer een scenario definitief is gemaakt kan de betreffende versie niet meer worden bewerkt. Om het scenario verder te bewerken moet eerst een nieuw concept gemaakt worden. In het concept zijn de bewerkfuncties vervolgens weer actief.</p>

<h1>Omschrijving toevoegen/bewerken</h1>
<p>Van het scenario kan een algemene omschrijving worden vastgelegd. De tekst wordt per versie afzonderlijk opgeslagen.</p>

<h1>Scenariobijlagen beheren</h1>
<p>Er kunnen bestandsbijlagen aan scenario's worden toegevoegd. Deze worden dan getoond bij dit scenario en kunnen bijvoorbeeld aanvullende informatie bevatten die niet op een andere manier in de scenariobrowser is vast te leggen.</p>

<?php 
}
if ($_SESSION['accesslevel'] >= $auth['scenario_bewerken']) {
?>
<h1>Schakelingen bewerken/toevoegen</h1>
<p>Er nieuwe schakeling kan als lege schakeling worden toegevoegd of door het kopieren van een bestaande schakeling van het scenario. Om technische redenen wordt de schakeling direct toegevoegd en kan dit niet meer geannuleerd worden. Bij vergissing kan een schakeling simpelweg weer verwijderd worden. Het bewerken van schakelingen is niet beperkt tot de eigen organisatie.</p>
<?php
}
?>