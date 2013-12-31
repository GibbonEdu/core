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

include "../../functions.php" ;
include "../../config.php" ;

include "./moduleFunctions.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPersonID=$_POST["gibbonPersonID"] ; 
$date=date("Y-m-d") ; 
$type="Positive" ; 
$descriptor="Quick Star" ; 
$level="" ; 
$comment="" ; 
$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ; 
	
if ($gibbonPersonID=="" OR $date=="" OR $type=="" OR $descriptor=="" OR $gibbonPlannerEntryID=="") {
	print "Error" ;
}
else {
	//Write to database
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID, "date"=>$date, "type"=>$type, "descriptor"=>$descriptor, "level"=>$level, "comment"=>$comment, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="INSERT INTO gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL=$URL . "&addReturn=fail2" ;
		header("Location: {$URL}");
		break ;
	}
	
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID=" . $connection2->lastInsertID() . "&gibbonPersonID=&gibbonRollGroupID=&gibbonYearGroupID=&type='><img style='margin-top: -30px; margin-left: 60px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
}
?>