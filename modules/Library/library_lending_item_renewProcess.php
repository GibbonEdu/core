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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonLibraryItemEventID=$_GET["gibbonLibraryItemEventID"] ;
$gibbonLibraryItemID=$_GET["gibbonLibraryItemID"] ;

if ($gibbonLibraryItemID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_lending_item_renew.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Library/library_lending_item_renew.php")==FALSE) {
		//Fail 0
		$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if event specified
		if ($gibbonLibraryItemEventID=="" OR $gibbonLibraryItemID=="") {
			//Fail1
			$URL.="&return=error1" ;
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
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				//Validate Inputs
				$returnExpected=NULL ;
				if ($_POST["returnExpected"]!="") {
					$returnExpected=dateConvert($guid, $_POST["returnExpected"]) ;
				}
				
				//Write to database
				try {
					$data=array("gibbonLibraryItemEventID"=>$gibbonLibraryItemEventID, "returnExpected"=>$returnExpected); 
					$sql="UPDATE gibbonLibraryItemEvent SET returnExpected=:returnExpected WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&return=error2" . $e->getMessage() ;
					header("Location: {$URL}");
					exit() ;
				}
				
				try {
					$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID, "returnExpected"=>$returnExpected); 
					$sql="UPDATE gibbonLibraryItem SET returnExpected=:returnExpected WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
						
				//Success 0
				$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>