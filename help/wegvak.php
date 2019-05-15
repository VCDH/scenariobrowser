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
<h1>Routedeel</h1>
<p>Een routedeel is een gedeelte van het beschikbaar wegennet tussen keuzepunten, convergentiepunten of tussenpunten. Routedelen worden gebruikt om aan te geven op welke geografische wegdelen een scenario van toepassing is. Daarnaast worden de routedelen ook gebruikt om omleidingsroutes in schakelingen aan te geven. De routedelen vormen dus de basis van het beschikbaar wegennet.</p>

<h1>Eigenschappen van een routedeel</h1>
<p>Een routedeel heeft enkele eigenschappen:</p>
<ul>
<li><strong>naam</strong> - verplichte naam van het routedeel, waarschijlijk via afgesproken conventie, of verzin iets leuks.</li>
<li><strong>weg</strong> - wegnummer of straatna(a)m(en). Gebruik het wegnummer als de weg beschikt over hectometrering, om het routedeel op basis van hectometrering te kunnen opzoeken.</li>
<li><strong>richting</strong> - rijrichting van het routedeel: <em>Li</em>, <em>Re</em> of de letter van de afrit/toerit, verbindingsweg of parallelbaan.</li>
<li><strong>kilometer van</strong> - Wanneer de weg beschikt over hectometrering: de hectometerpositie van de start van het routedeel, uitgedrukt in kilometers. Decimalen zijn toegestaan.</li>
<li><strong>kilometer tot</strong> - Wanneer de weg beschikt over hectometrering: de hectometerpositie van het einde van het routedeel, uitgedrukt in kilometers. Decimalen zijn toegestaan.</li>
</ul>

<h1>Kaart</h1>
<p>Geef het routedeel op de kaart aan door een lijn te tekenen. Klik met de linkermuisknop op de kaart om een punt/lijnstuk aan de lijn toe te voegen. Klik met de rechtermuisknop om het laatste punt/lijnstuk van de lijn te verwijderen. Teken de lijn in de rijrichting. Gebruik zoveel tussenpunten als nodig is om de lijn <em>goed genoeg</em> weer te geven, om de voorkomen dat gebruikers met een minder goede computer problemen krijgen met het weergeven van alle wegvakken op de kaart.</p>

<h1>Gekoppelde scenario's</h1>
<p>Onder de kaart wordt een (uitklapbare) mappenstructuur weergegeven met de scenario's. Deze kan gebruikt worden om scenario's aan dit routedeel te koppelen, maar dit kan ook later vanuit de scenario's. Het gaat hier om scenario's waarbij dit routedeel het stremmingsvak van de geselecteerde scenario's is. Scenario's waarbij dit routedeel onderdeel is van een omleidingsroute worden hier niet aangegeven. Geselecteerde scenario's worden met een groene achtergrond weergegeven.</p>

<h1>Routedeel splitsen</h1>
<p>Een routedeel kan desgewenst worden gesplitst in twee routedelen. Hiervoor moet een punt op het routedeel worden aangegeven waar het routedeel geknipt wordt. Vervolgens kunnen voor beide delen een (nieuwe) naam en wegeigenschappen worden opgegeven. Scenario's waarvoor het routedeel als stremmingsroutedeel en schakelingen waarvoor het routedeel als omleidingsroutedeel gebruikt worden zullen automatisch aan de twee nieuw ontstane routedelen gekoppeld worden.</p>
<p>Het is niet mogelijk om twee routedelen samen te voegen tot &eacute;&eacute;n routedeel.</p>
