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
function afbeelding_verwijderen($filename) {
	global $db;
	//controleer of afbeelding nog voorkomt
	$qry = "SELECT `id` FROM `".$db['prefix']."schakelingdrips` 
	WHERE `afbeelding` = '".mysqli_real_escape_string($db['link'], $filename)."'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) === 0) {
		//verwijder bestand: niet meer in gebruik
		@unlink('store/'.strtoupper(substr($filename, 0, 1)).'/'.$filename);
	}
}
?>