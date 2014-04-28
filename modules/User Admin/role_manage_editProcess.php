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

$gibbonRoleID=$_GET["gibbonRoleID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/role_manage_edit.php&gibbonRoleID=" . $gibbonRoleID ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if role specified
	if ($gibbonRoleID=="") {
		//Fail1
		$URL=$URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonRoleID"=>$gibbonRoleID); 
			$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID" ;
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
			//Validate Inputs
			$category=$_POST["category"] ;
			$name=$_POST["name"] ;
			$nameShort=$_POST["nameShort"] ;
			$description=$_POST["description"] ;
			
			if ($category=="" OR $name=="" OR $nameShort=="" OR $description=="") {
				//Fail 3
				$URL=$URL . "&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$data=array("name"=>$name, "nameShort"=>$nameShort, "gibbonRoleID"=>$gibbonRoleID); 
					$sql="SELECT * FROM gibbonRole WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonRoleID=:gibbonRoleID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 4
					$URL=$URL . "&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("category"=>$category, "name"=>$name, "nameShort"=>$nameShort, "description"=>$description, "gibbonRoleID"=>$gibbonRoleID); 
						$sql="UPDATE gibbonRole SET category=:category, name=:name, nameShort=:nameShort, description=:description WHERE gibbonRoleID=:gibbonRoleID" ;
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