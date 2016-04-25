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

$orphaned="" ;
if (isset($_GET["orphaned"])) {
	if ($_GET["orphaned"]=="true") {
		$orphaned="true" ;
	}
}

$gibbonModuleID=$_GET["gibbonModuleID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/module_manage_uninstall.php&gibbonModuleID=" . $gibbonModuleID ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/module_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_uninstall.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if role specified
	if ($gibbonModuleID=="") {
		//Fail1
		$URL.="&deleteReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonModuleID"=>$gibbonModuleID); 
			$sql="SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID" ;
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
			$row=$result->fetch() ;
			$module=$row["name"] ;
			$partialFail=FALSE ;
			
			//Check for tables and views to remove, and remove them
			$tables=NULL ;
			if (isset($_POST["remove"])) {
				$tables=$_POST["remove"] ;
			}
			if (is_array($tables)) {
				if (count($tables)>0) {
			 		foreach ($tables AS $table) {
			 			$type=NULL ;
			 			$name=NULL ;
			 			if (substr($table, 0 ,5)=="Table") {
			 				$type="TABLE" ;
			 				$name=substr($table, 6) ;
			 			}
			 			else if (substr($table, 0 ,4)=="View") {
			 				$type="VIEW" ;
			 				$name=substr($table, 5) ;
			 			}
			 			if ($type!=NULL AND $name!=NULL) {
			 				try {
								$dataDelete=array(); 
								$sqlDelete="DROP $type $name" ;
								$resultDelete=$connection2->prepare($sqlDelete);
								$resultDelete->execute($dataDelete);
							}
							catch(PDOException $e) { 
								print $e->getMessage() . "<br/><br/>" ;
								$partialFail=TRUE ;
							}
			 			}
			 		}
			 	}
			}
			
			//Get actions to remove permissions
			try {
				$data=array("gibbonModuleID"=>$gibbonModuleID); 
				$sql="SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			while ($row=$result->fetch()) {
				//Remove permissions
				try {
					$dataDelete=array("gibbonActionID"=>$row["gibbonActionID"]); 
					$sqlDelete="DELETE FROM gibbonPermission WHERE gibbonActionID=:gibbonActionID" ;
					$resultDelete=$connection2->prepare($sqlDelete);
					$resultDelete->execute($dataDelete);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
				}
			}
			
			//Remove actions
			try {
				$dataDelete=array("gibbonModuleID"=>$gibbonModuleID); 
				$sqlDelete="DELETE FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
			}
			
			//Remove module
			try {
				$dataDelete=array("gibbonModuleID"=>$gibbonModuleID); 
				$sqlDelete="DELETE FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
			}
			
			//Remove hooks
			try {
				$dataDelete=array("gibbonModuleID"=>$gibbonModuleID); 
				$sqlDelete="DELETE FROM gibbonHook WHERE gibbonModuleID=:gibbonModuleID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
			}
			
			//Remove settings
			try {
				$dataDelete=array("scope"=>$module); 
				$sqlDelete="DELETE FROM gibbonSetting WHERE scope=:scope" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
			}
			
			
			if ($partialFail==TRUE) {
				//Fail3
				$URL.="&deleteReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Update main menu
				$mainMenu = new Gibbon\menuMain();
				$mainMenu->setMenu() ;
			
				//Success 0
				if ($orphaned!="true") {
					$URLDelete=$URLDelete . "&deleteReturn=success0" ;
				}
				else {
					$URLDelete=$URLDelete . "&deleteReturn=success1" ;
				}
				header("Location: {$URLDelete}");
			}
		}
	}
}
?>