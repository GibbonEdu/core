<?
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

//Setup variables
$output="" ;
if (isset($_GET["id"])) {
	$id=$_GET["id"] ;
}
else {
	$id="" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Timetable/tt.php")==FALSE) {
	//Acess denied
	$output.="<div class='error'>" ;
		$output.=_("You do not have access to this page.") ;
	$output.="</div>" ;
}
else {
	include "./modules/Timetable/moduleFunctions.php" ;
	$ttDate="" ;
	if ($_POST["ttDate"]!="") {
		$ttDate=dateConvertToTimestamp(dateConvert($guid, $_POST["ttDate"]));
	}
	
	if ($_POST["fromTT"]=="Y") {
		if ($_POST["schoolCalendar"]=="on" OR $_POST["schoolCalendar"]=="Y") {
			$_SESSION[$guid]["viewCalendarSchool"]="Y" ;
		}
		else {
			$_SESSION[$guid]["viewCalendarSchool"]="N" ;
		}
		
		if ($_POST["personalCalendar"]=="on" OR $_POST["personalCalendar"]=="Y") {
			$_SESSION[$guid]["viewCalendarPersonal"]="Y" ;
		}
		else {
			$_SESSION[$guid]["viewCalendarPersonal"]="N" ;
		}
	}
	
	$tt=renderTT($guid, $connection2, $_SESSION[$guid]["gibbonPersonID"], $id, FALSE, $ttDate) ;
	if ($tt!=FALSE) {
		$output.=$tt ;
	}
	else {
		$output.="<div class='error'>" ;
			$output.=_("There is no information for the date specified.") ;
		$output.="</div>" ;
	}
}

print $output ;
?>