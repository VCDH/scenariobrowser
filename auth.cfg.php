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
$auth = array();
$auth['wegvak_bewerken'] = 100;
$auth['scenario_bewerken'] = 50; //ook voor schakelingen
$auth['scenario_finalizeren'] = 100;
$auth['scenario_verwijderen'] = 200;
$auth['scenario_overdragen'] = 200;
$auth['scenariomappen_bewerken'] = 200;
$auth['beheer'] = 200;
$auth['gebruikers_beheren_eigen'] = 200;
$auth['gebruikers_beheren_alle'] = 250;
$auth['organisaties_beheren_eigen'] = 200;
$auth['organisaties_beheren_alle'] = 250;
$auth['dripdb_bijwerken'] = 250;

$accesslevel_available = array(0 => 'Meekijker', 50 => 'Scenario bewerken', 100 => 'Scenario finalizeren', 200 => 'Beheerder', 255 => 'Superbeheerder');

?>