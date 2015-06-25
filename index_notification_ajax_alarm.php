<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

@session_start() ;

//Gibbon system-wide includes
include "./functions.php" ;
include "./config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

$type="" ;
if (isset($_GET["type"])) {
	$type=$_GET["type"] ;
}
$output="" ;

if ($type=="general" OR $type=="lockdown") {
	$output.="<div style='width: 100%; height: 100%; background-color: #f00; color: #fff; margin: 0'>" ;
		$output.="<div style='padding-top: 150px; font-size: 120px; font-weight: bold; font-family: arial, sans; text-align: center'>" ;
			if ($type=="general") {
				$output.=_("General Alarm!") ;
				$output.="<audio loop autoplay volume=3>
					<source src=\"./audio/alarm_general.mp3\" type=\"audio/mpeg\">
				</audio>" ;	
			}
			else {
				$output.=_("Lockdown!") ;
				$output.="<audio loop autoplay volume=3>
					<source src=\"./audio/alarm_lockdown.mp3\" type=\"audio/mpeg\">
				</audio>" ;	
			}
	
		$output.="</div>" ;	
	$output.="</div>" ;
}

print $output ;
?>