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

//fix cross-site cookie issue

$url = 'http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/wddhandler2.php?do=' . htmlspecialchars($_GET['do']) . '&id=' . htmlspecialchars($_GET['id']) . '&drip_id=' . htmlspecialchars($_GET['drip_id']) . '&ow=' . htmlspecialchars($_GET['ow']) . '&image=' . htmlspecialchars($_GET['image']);

?>
<script type="text/javascript">
parent.close_wdd(<?php echo '\'' . $url . '\'' ?>);
</script>
<?php
exit;
?>