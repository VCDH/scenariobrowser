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

//Description: converts a filesize in bytes into human readable format with unit
//accepts filesize in bytes and optional parameter to convert in SI units rather than binary units
//Usage: int convertfilesize( int $filesize [, bool $SI] )

if (!function_exists('convertfilesize')) { function convertfilesize($filesize, $SI = FALSE) {
	
	//define division factor and unit notation
	if ($SI == TRUE) {
		$factor = 1000;
		$unit = 'B';
	}
	else {
		$factor = 1024;
		$unit = 'iB';
	}
	
	//calculate best fit unit (up to terabytes)
	$divisions = 0;
	while (($filesize > $factor) && ($divisions < 4)) {
		$filesize = $filesize / $factor;
		$divisions++;
	}
	
	//define unit
	switch($divisions){
		case 0:
			$unit = ' bytes';
			break;
		case 1:
			$unit = ' k'.$unit;
			break;
		case 2:
			$unit = ' M'.$unit;
			break;
		case 3:
			$unit = ' G'.$unit;
			break;
		case 4:
			$unit = ' T'.$unit;
			break;
		default:
			$unit = ' !error-undifined!';
	}
	
	//round filesize
	if ($filesize < 10) {
		$filesize = number_format($filesize,2,',','');
	} 
	elseif ($filesize < 100) {
		$filesize = number_format($filesize,1,',','');
	}
	else {
		$filesize = number_format($filesize,0,',','');
	}
	
	//return human-readable file size
	return $filesize.$unit;
}}
?>