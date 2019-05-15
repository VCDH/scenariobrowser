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
<h1>Naam</h1>
<p>Een optionele naam van de schakeling. Als een scenario uit meerdere schakelingen bestaat worden de schakelingen op naam gesorteerd.</p>

<h1>DRIPs</h1>
<p>DRIPs en bijbehorende beeldstanden kunnen op meerdere manieren worden toegevoegd. Wijzigingen aan DRIPs worden direct op de achtergrond opgeslagen.</p>

<h2>DRIP toevoegen via WebDRIP Designer</h2>
<p>De Scenariobrowser heeft een directe koppeling met de WebDRIP Designer om beeldstanden te kunnen maken. Klik op de knop <em>Nieuw via WebDRIP Designer...</em> om hier gebruik van te maken. Er volgt dan eerst een lijst met alle DRIPs om te kiezen voor welke DRIP een plaatje gemaakt wordt. Na het kiezen van de DRIP opent de WebDRIP Designer en kunnen al de functionaliteiten van deze uitstekende ontwerptool gebruikt worden. Klik op de OK-knop in de WebDRIP Designer om de afbeelding te bevestigen.</p>

<h2>DRIP toevoegen via upload</h2>
<p>Er kunnen ook bestaande afbeeldingen in PNG formaat worden toegevoegd. Selecteer deze via de knop <em>Nieuw via Upload...</em> of sleep simpelweg de bestanden naar het browservenster. Er kunnen meerdere bestanden in &eacute;&eacute;n keer worden ge&uuml;pload.</p>

<h2>Overschrijven</h2>
<p>De optie <em>overschrijven</em> staat standaard aan. Dit betekent dat wanneer er een nieuwe afbeelding wordt toegevoegd voor een DRIP waarvoor al een afbeelding gemaakt is, de bestaande afbeelding overschreven wordt. Wanneer de optie <em>overschrijven</em> uitgeschakeld wordt, worden nieuwe afbeeldingen altijd toegevoegd, ook als er al een afbeelding voor de betreffende DRIP bestaat.</p>

<h2>Bewerken</h2>
<p>Een DRIP-afbeelding kan worden bewerkt door op de afbeelding te klikken. De WebDRIP Designer zal dan worden geopend. Als de afbeelding via de WebDRIP Designer is toegevoegd, dan wordt de bestaande afbeelding in de WebDRIP Designer geladen zodat deze kan worden aangepast. Als de afbeelding via upload is toegevoegd, zal de WebDRIP Designer een melding geven dat deze de afbeelding niet kan laden. Er kan dan wel een vervangende afbeelding worden gemaakt voor de gekozen DRIP.</p>

<h2>Hernoemen</h2>
<p>Klik op de naam boven een DRIP-afbeelding om een andere DRIP te kiezen voor deze afbeelding.</p>

<h2>Verwijderen</h2>
<p>Klik op het prullenbakje achter de naam boven een DRIP-afbeelding om deze afbeelding uit de schakeling te verwijderen. Er wordt niet om een bevestiging gevraagd.</p>

<h1>Omschrijving</h1>
<p>Er kan een optionele omschrijving worden toegevoegd aan de schakeling. Deze kan bijvoorbeeld gebruikt worden om inschakelvoorwaarden voor de schakeling te beschrijven.</p>

<h1>Omleidingsroutes</h1>
<p>Via de kaart kunnen de omleidingsroutes worden aangegeven door het aanklikken van de betreffende routedelen. Deze routedelen zullen dan groen worden. De routedelen kunnen eventueel ook via de uitklaplijst onder de kaart worden geselecteerd.</p>