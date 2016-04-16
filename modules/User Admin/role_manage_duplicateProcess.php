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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonRoleID=$_GET["gibbonRoleID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/role_manage.php&gibbonRoleID=$gibbonRoleID" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage_duplicate.php")==FALSE) {
	//Fail 0
	$URL.="&duplicateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$name=$_POST["name"] ;
	$nameShort=$_POST["nameShort"] ;
	
	if ($gibbonRoleID=="" OR $name=="" OR $nameShort=="") {
		//Fail 3
		$URL.="&duplicateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Lock table
		try {
			$sql="LOCK TABLE gibbonRole WRITE, gibbonPermission WRITE" ;
			$result=$connection2->query($sql);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&duplicateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}			
		
		//Get next autoincrement for unit
		try {
			$sqlAI="SHOW TABLE STATUS LIKE 'gibbonRole'";
			$resultAI=$connection2->query($sqlAI);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&duplicateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}			
		
		$rowAI=$resultAI->fetch();
		$AI=str_pad($rowAI['Auto_increment'], 8, "0", STR_PAD_LEFT) ;
		$partialFail=FALSE ; 
		
		if ($AI=="") {
			//Fail 2
			$URL.="&duplicateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("gibbonRoleID"=>$gibbonRoleID); 
				$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&duplicateReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&duplicateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;
				try {
					$data=array("gibbonRoleID"=>$AI, "category"=>$row["category"], "name"=>$name, "nameShort"=>$nameShort, "description"=>$row["description"]); 
					$sql="INSERT INTO gibbonRole SET gibbonRoleID=:gibbonRoleID, category=:category, name=:name, nameShort=:nameShort, description=:description, type='Additional'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&duplicateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				//Duplicate permissions
				try {
					$dataPermissions=array("gibbonRoleID"=>$gibbonRoleID); 
					$sqlPermissions="SELECT * FROM gibbonPermission WHERE gibbonRoleID=:gibbonRoleID" ;
					$resultPermissions=$connection2->prepare($sqlPermissions);
					$resultPermissions->execute($dataPermissions);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
					print $e->getMessage() ;
				}

			while ($rowPermissions=$resultPermissions->fetch()) {
				$copyOK=TRUE ;
				try {
					$dataCopy=array("gibbonRoleID"=>$AI, "gibbonActionID"=>$rowPermissions["gibbonActionID"]); 
					$sqlCopy="INSERT INTO gibbonPermission SET gibbonRoleID=:gibbonRoleID, gibbonActionID=:gibbonActionID" ;
					$resultCopy=$connection2->prepare($sqlCopy);
					$resultCopy->execute($dataCopy);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
				}
			}
				
				
				//Unlock locked database tables
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
				
				if ($partialFail==TRUE) {
					//Fail 6
					$URL.="&duplicateReturn=fail6" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&duplicateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>