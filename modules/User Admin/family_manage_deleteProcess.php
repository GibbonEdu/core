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

$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
$search=$_GET["search"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage_delete.php&gibbonFamilyID=$gibbonFamilyID&search=$search" ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage.php&search=$search" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_delete.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if family specified
	if ($gibbonFamilyID=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonFamilyID"=>$gibbonFamilyID); 
			$sql="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			$URL.="&return=error2" ;
			header("Location: {$URL}");
		}
		else {
			//Delete children
			try {
				$dataDelete=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlDelete="DELETE FROM gibbonFamilyChild WHERE gibbonFamilyID=:gibbonFamilyID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { }

			//Delete adults
			try {
				$dataDelete=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlDelete="DELETE FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { }
			
			//Delete Family
			try {
				$dataDelete=array("gibbonFamilyID"=>$gibbonFamilyID); 
				$sqlDelete="DELETE FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
				$resultDelete=$connection2->prepare($sqlDelete);
				$resultDelete->execute($dataDelete);
			}
			catch(PDOException $e) { 
					$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			$URLDelete=$URLDelete . "&return=success0" ;
			header("Location: {$URLDelete}");
		}
	}
}
?>