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
//niet vergeten search.js ook bij te werken bij wijzigingen
$scenario_types = array('w' => 'Werkzaamheden', 'e' => 'Evenement', 'i' => 'Ongeval', 'f' => 'File', 't' => 'Tunnel', 'b' => 'Brug', 'a' => 'Algemeen');
$scenario_types_api = array('w' => 'RoadWorks', 'e' => 'PublicEvent', 'i' => 'Accident', 'f' => 'Congestion', 't' => 'Tunnel', 'b' => 'Bridge', 'a' => 'Generic');
$scenario_ernsten = array('d' => 'Dicht (incl. omleiden)', 'h' => 'Hinder (incl. omleiden)', 'i' => 'Informeren');
$scenario_ernsten_api = array('d' => 'RoadBlocked', 'h' => 'RoadPartiallyObstructed', 'i' => 'Advisory');
$scenario_types_afbeeldingen = array('w' => 'werk.png', 'e' => 'evenement.png', 'i' => 'ongeval.png', 'f' => 'file.png', 't' => 'tunnel.png', 'b' => 'brug.png', 'a' => 'algemeen.png');
$scenario_ernsten_afbeeldingen = array('d' => 'dicht.png', 'h' => 'omleiden.png', 'i' => 'informeren.png');
?>