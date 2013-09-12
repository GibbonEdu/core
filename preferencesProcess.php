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

//Start session
session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check to see if academic year id variables are set, if not set them 
if (($_SESSION[$guid]["gibbonAcademicYearID"]=="") OR ($_SESSION[$guid]["gibbonAcademicYearID"]=="")) {
	setCurrentSchoolYear($guid, $connection2) ;
}

//Check password address is not blank
$calendarFeedPersonal=$_POST["calendarFeedPersonal"] ;
$personalBackground=$_POST["personalBackground"] ;

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=preferences.php" ;

//Check passwords are not blank
if (FALSE) {
	$URL=$URL . "&editReturn=fail0" ;
	header("Location: {$URL}");
}
//Otherwise proceed
else {
	try {
		$data=array("calendarFeedPersonal"=>$calendarFeedPersonal, "personalBackground"=>$personalBackground, "username"=>$_SESSION[$guid]["username"]); 
		$sql="UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal, personalBackground=:personalBackground WHERE (username=:username)" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$URL=$URL . "&editReturn=fail1" ;
		header("Location: {$URL}");
		break ;
	}
	
	$_SESSION[$guid]["personalBackground"]=$personalBackground ;
	
	$_SESSION[$guid]["calendarFeedPersonal"]=$calendarFeedPersonal ;
	$URL=$URL . "&editReturn=success0" ;
	header("Location: {$URL}");
}
?>