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

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonAlarmID=$_POST["gibbonAlarmID"] ;
$gibbonPersonID=$_POST["gibbonPersonID"] ;

//Proceed!
if ($gibbonAlarmID=="" OR $gibbonPersonID=="") {
	print "<div class='error'>" ;
		print _("An error has occurred.") ;
	print "</div>" ;
}
else {	
	//Check confirmation of alarm
	try {
		$dataConfirm=array("gibbonAlarmID"=>$gibbonAlarmID, "gibbonAlarmID2"=>$gibbonAlarmID, "gibbonPersonID"=>$gibbonPersonID); 
		$sqlConfirm="SELECT surname, preferredName, gibbonAlarmConfirmID, gibbonPerson.gibbonPersonID AS confirmer, gibbonAlarm.gibbonPersonID as sounder FROM gibbonPerson JOIN gibbonAlarm ON (gibbonAlarm.gibbonAlarmID=:gibbonAlarmID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmConfirm.gibbonAlarmID=:gibbonAlarmID2) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID" ;
		$resultConfirm=$connection2->prepare($sqlConfirm);
		$resultConfirm->execute($dataConfirm);
	}
	catch(PDOException $e) { print $e->getMessage() ;}
	
	if ($resultConfirm->rowCount()!=1) {
		print "<div class='error'>" ;
			print _("An error has occurred.") ;
		print "</div>" ;
	}
	else {
		$rowConfirm=$resultConfirm->fetch() ;
		
		print "<td style='color: #fff'>" ;
			print formatName("", $rowConfirm["preferredName"],$rowConfirm["surname"], "Staff", true, true) . "<br/>" ;
		print "</td>" ;
		print "<td style='color: #fff'>" ;
			if ($rowConfirm["sounder"]==$rowConfirm["confirmer"]) {
				print _("NA") ;
			}
			else {
				if ($rowConfirm["gibbonAlarmConfirmID"]!="") {
					print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
				}
			}
		print "</td>" ;
		print "<td style='color: #fff'>" ;
			if ($rowConfirm["sounder"]!=$rowConfirm["confirmer"]) {
				if ($rowConfirm["gibbonAlarmConfirmID"]=="") {
					print "<a target='_parent' href='" . $_SESSION[$guid]["absoluteURL"] . "/index_notification_ajax_alarmConfirmProcess.php?gibbonPersonID=" . $rowConfirm["confirmer"] . "&gibbonAlarmID=$gibbonAlarmID'><img title='" . _('Confirm') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick_light.png'/></a> " ;
				}
			}
		print "</td>" ;
	}
}
?>