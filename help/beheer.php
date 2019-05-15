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
<h1>Beheer</h1>
<p>Verschillende beheerdingetjes voor de website.</p>

<?php 
if ($_SESSION['accesslevel'] >= $auth['gebruikers_beheren']) {
?>
<h1>Gebruikers beheren</h1>
<p>Klik op <em>Gebruikers weergeven</em> om een lijst te tonen van alle geregistreerde gebruikers. Van hier uit kunnen de accountrechten van een gebruiker gewijzigd worden, alsmede de geregistreerde gebruikersgegevens.</p>
<p>Gebruik de zoekfunctie om te filteren op naam of organisatie.</p>
<p>Toegangsrechten werken op basis van een toegangsniveau (<em>clearance level</em>). Voor verschillende functies is vastgelegd welk toegangsniveau minimaal vereist is om er gebruik van te mogen maken. Zie hiervoor onderstaande overzichten.</p>
<?php 
echo '<table style="float:left;">';
echo '<tr><th>Toegangsniveau gebruiker</th><th>Waarde</th></tr>';
foreach ($accesslevel_available as $k => $v) {
	echo '<tr><td>'.htmlspecialchars($v).'</td><td>'.$k.'</td></tr>';
}
echo '</table>';
echo '<table style="float:left; margin-left: 16px;">';
echo '<tr><th>Functie</th><th>Minimaal vereist toegangsniveau</th></tr>';
foreach ($auth as $k => $v) {
	echo '<tr><td>'.htmlspecialchars($k).'</td><td>'.$v.'</td></tr>';
}
echo '</table>';
}

if ($_SESSION['accesslevel'] >= $auth['organisaties_beheren']) {
?>
<h1 style="clear:left;">Organisaties beheren</h1>
<p>Geef een lijst weer van de organisaties die binnen het systeem geregisteerd zijn. Hierbij wordt de naam van de organisatie en het deel van de de e-mailadressen na de @ vastgelegd (<em>E-mail-suffix</em>). Personen in het bezit van een e-mailadres van &eacute;&eacute;n van de geregistreerde organisaties kunnen zelf een account aanmaken op de website, wanneer de optie <em>Account aanmaken openstellen</em> voor de organisatie is ingeschakeld.</p>
<p>Daarnaast worden afdeling, e-mailadres en telefoonnummer van de organisatie vastgelegd. Deze worden samen met de organisatienaam getoond onder het kopje <em>Beheer</em> van een scenario.</p>
<?php
}

if ($_SESSION['accesslevel'] >= $auth['dripdb_bijwerken']) {
?>
<h1 style="clear:left;">DRIP database bijwerken</h1>
<p>Indien er wijzigingen zijn in het DRIP-areaal kunnen deze worden ge&iuml;porteerd van <a href="http://drips.s200.nl/">drips.s200.nl</a>. Om te voorkomen dat er vanalles stuk gaat is dit proces niet geautomatiseerd.</p>
<p>Middels de import-tovenaar kan de meest recente stand van zaken opgehaald worden. Wijzigingen in wegnummer, rijrichting, hectometrering, standaardtekst en opmerkingen worden automatisch verwerkt. Wijzigingen in naam, status, aansturing, type en positie op de kaart worden aangehouden en dienen handmatig geaccordeerd te worden. Kleine wijzigingen in co&ouml;rdinaten (lat &lt;= 0.01; lng &lt;= 0.006) en draairichting (&lt;= 15 graden) worden wel automatisch geaccepteerd.</p>
<p>Bij niet-automatisch geaccepteerde wijzigingen wordt per DRIP een overzicht getoond met de bestaande en nieuwe waarden. Deze kunnen dan per DRIP geaccepteerd of overgeslagen worden.</p>
<?php
}
?>