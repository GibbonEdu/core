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

$gibbonModuleID=$_GET["gibbonModuleID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/module_manage_update.php&gibbonModuleID=" . $gibbonModuleID ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/module_manage_update.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if role specified
	if ($gibbonModuleID=="") {
		//Fail1
		$URL=$URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		//NAMED
		try {
			$data=array("gibbonModuleID"=>$gibbonModuleID); 
			$sql="SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			
			$versionDB=$_POST["versionDB"] ;
			$versionCode=$_POST["versionCode"] ;

			//Validate Inputs
			if ($versionDB=="" OR $versionCode=="" OR $versionDB>=$versionCode) {
				//Fail 3
				$URL=$URL . "&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {	
				include $_SESSION[$guid]["absolutePath"] . "/modules/" . $row["name"] . "/CHANGEDB.php" ;
				
				$partialFail=FALSE;
				foreach ($sql AS $version) {
					if ($version[0]>$versionDB AND $version[0]<=$versionCode) {
						$sqlTokens=explode(";end", $version[1]) ;
						foreach ($sqlTokens AS $sqlToken) {
							if (trim($sqlToken)!="") {
								try {
									$result=$connection2->query($sqlToken);   
								}
								catch(PDOException $e) { 
									$partialFail=TRUE;
								}
							}
						}
					}
				}
				
				if ($partialFail==TRUE) {
					//Fail 5
					$URL=$URL . "&updateReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Update DB version
					try {
						$data=array("versionCode"=>$versionCode, "name"=>$row["name"]); 
						$sql="UPDATE gibbonModule SET version=:versionCode WHERE name=:name" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Success 0
					$URL=$URL . "&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>