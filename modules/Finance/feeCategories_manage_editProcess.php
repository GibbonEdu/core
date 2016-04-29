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

include "./moduleFunctions.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonFinanceFeeCategoryID=$_GET["gibbonFinanceFeeCategoryID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/feeCategories_manage_edit.php&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/feeCategories_manage_edit.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonFinanceFeeCategoryID=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonFinanceFeeCategoryID"=>$gibbonFinanceFeeCategoryID); 
			$sql="SELECT * FROM gibbonFinanceFeeCategory WHERE gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID" ;
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
			//Proceed!
			$name=$_POST["name"] ;
			$nameShort=$_POST["nameShort"] ;
			$active=$_POST["active"] ;
			$description=$_POST["description"] ;
			if ($name=="" OR $nameShort=="" OR $active=="") {
					$URL.="&return=error1" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("name"=>$name, "nameShort"=>$nameShort, "active"=>$active, "description"=>$description, "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonFinanceFeeCategoryID"=>$gibbonFinanceFeeCategoryID); 
					$sql="UPDATE gibbonFinanceFeeCategory SET name=:name, nameShort=:nameShort, active=:active, description=:description, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
							$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
					$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>