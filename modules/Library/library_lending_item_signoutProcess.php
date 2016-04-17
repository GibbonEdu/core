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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$statusCurrent=$_POST["statusCurrent"] ;
$status=$_POST["status"] ;
$type="Other" ;
if ($status=="Decommissioned") {
	$type="Decommission" ;
}
else if ($status=="Lost") {
	$type="Loss" ;
}
else if ($status=="On Loan") {
	$type="Loan" ;
}
else if ($status=="Repair") {
	$type="Repair" ;
}
else if ($status=="Reserved") {
	$type="Reserve" ;
}
$gibbonPersonIDStatusResponsible=$_POST["gibbonPersonIDStatusResponsible"] ;
if ($_POST["returnExpected"]!="") {
	$returnExpected=dateConvert($guid, $_POST["returnExpected"]) ;
}
$returnAction=$_POST["returnAction"] ;
$gibbonPersonIDReturnAction=NULL ;
if ($_POST["gibbonPersonIDReturnAction"]!="") {
	$gibbonPersonIDReturnAction=$_POST["gibbonPersonIDReturnAction"] ;
}

$gibbonLibraryItemID=$_POST["gibbonLibraryItemID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_lending_item_signOut.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ;
$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_signOut.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonLibraryItemID=="" OR $status=="" OR $gibbonPersonIDStatusResponsible=="" OR $statusCurrent!="Available") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
			$sql="SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()!=1) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID, "type"=>$type, "status"=>$status, "gibbonPersonIDStatusResponsible"=>$gibbonPersonIDStatusResponsible, "gibbonPersonIDOut"=>$_SESSION[$guid]["gibbonPersonID"], "timestampOut"=>date('Y-m-d H:i:s', time()), "returnExpected"=>$returnExpected, "returnAction"=>$returnAction, "gibbonPersonIDReturnAction"=>$gibbonPersonIDReturnAction); 
				$sql="INSERT INTO gibbonLibraryItemEvent SET gibbonLibraryItemID=:gibbonLibraryItemID, type=:type, status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDOut=:gibbonPersonIDOut, timestampOut=:timestampOut, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" . $e->getMessage() ;
				header("Location: {$URL}");
				exit() ;
			}
			
			try {
				$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID, "status"=>$status, "gibbonPersonIDStatusResponsible"=>$gibbonPersonIDStatusResponsible, "gibbonPersonIDStatusRecorder"=>$_SESSION[$guid]["gibbonPersonID"], "timestampStatus"=>date('Y-m-d H:i:s', time()), "returnExpected"=>$returnExpected, "returnAction"=>$returnAction, "gibbonPersonIDReturnAction"=>$gibbonPersonIDReturnAction); 
				$sql="UPDATE gibbonLibraryItem SET status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			//Success 0
			$URL=$URLSuccess . "&addReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>