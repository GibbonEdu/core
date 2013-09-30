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

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonLibraryItemEventID=$_GET["gibbonLibraryItemEventID"] ;
$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;

if ($gibbonLibraryItemID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_lending_item_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_edit.php")==FALSE) {
		//Fail 0
		$URL = $URL . "&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if event specified
		if ($gibbonLibraryItemEventID=="" OR $gibbonLibraryItemID=="") {
			//Fail1
			$URL = $URL . "&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonLibraryItemEventID"=>$gibbonLibraryItemEventID, "gibbonLibraryItemID"=>$gibbonLibraryItemID); 
				$sql="SELECT * FROM gibbonLibraryItemEvent JOIN gibbonLibraryItem ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID AND gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL = $URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL = $URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Validate Inputs
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
				$returnExpected=NULL ;
				if ($_POST["returnExpected"]!="") {
					$returnExpected=dateConvert($_POST["returnExpected"]) ;
				}
				$returnAction=$_POST["returnAction"] ;
				$gibbonPersonIDReturnAction=NULL ;
				if ($_POST["gibbonPersonIDReturnAction"]!="") {
					$gibbonPersonIDReturnAction=$_POST["gibbonPersonIDReturnAction"] ;
				}
				
				//Write to database
				try {
					$data=array("gibbonLibraryItemEventID"=>$gibbonLibraryItemEventID, "type"=>$type, "status"=>$status, "gibbonPersonIDOut"=>$_SESSION[$guid]["gibbonPersonID"], "timestampOut"=>date('Y-m-d H:i:s', time()), "returnExpected"=>$returnExpected, "returnAction"=>$returnAction, "gibbonPersonIDReturnAction"=>$gibbonPersonIDReturnAction); 
					$sql="UPDATE gibbonLibraryItemEvent SET type=:type, status=:status, gibbonPersonIDOut=:gibbonPersonIDOut, timestampOut=:timestampOut, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&addReturn=fail2" . $e->getMessage() ;
					header("Location: {$URL}");
					break ;
				}
				
				try {
					$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID, "status"=>$status, "gibbonPersonIDStatusRecorder"=>$_SESSION[$guid]["gibbonPersonID"], "timestampStatus"=>date('Y-m-d H:i:s', time()), "returnExpected"=>$returnExpected, "returnAction"=>$returnAction, "gibbonPersonIDReturnAction"=>$gibbonPersonIDReturnAction); 
					$sql="UPDATE gibbonLibraryItem SET status=:status, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
						
				//Success 0
				$URL = $URL . "&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>