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

include "../../functions.php" ;
include "../../config.php" ;

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

$gibbonTTSpaceBookingID=$_GET["gibbonTTSpaceBookingID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceBooking_manage_delete.php&gibbonTTSpaceBookingID=" . $gibbonTTSpaceBookingID ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/spaceBooking_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/spaceBooking_manage_delete.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		if ($gibbonTTSpaceBookingID=="") {
			//Fail1
			$URL.="&deleteReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				if ($highestAction=="Manage Space Bookings_allBookings") {
					$data=array("gibbonTTSpaceBookingID1"=>$gibbonTTSpaceBookingID, "gibbonTTSpaceBookingID2"=>$gibbonTTSpaceBookingID); 
					$sql="(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name" ; 
				}
				else {
					$data=array("gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonTTSpaceBookingID1"=>$gibbonTTSpaceBookingID, "gibbonTTSpaceBookingID2"=>$gibbonTTSpaceBookingID); 
					$sql="(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID1 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID2 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name" ; 
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonTTSpaceBookingID"=>$gibbonTTSpaceBookingID); 
					$sql="DELETE FROM gibbonTTSpaceBooking WHERE gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL.="&deleteReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
	
				//Success 0
				$URLDelete=$URLDelete . "&deleteReturn=success0" ;
				header("Location: {$URLDelete}");
			}
		}
	}
}
?>