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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/budgets_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/budgets_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$name=$_POST["name"] ;
	$nameShort=$_POST["nameShort"] ;
	$active=$_POST["active"] ;
	$category=$_POST["category"] ;
	
	//Lock table
	try {
		$sql="LOCK TABLES gibbonFinanceBudget WRITE, gibbonFinanceBudgetPerson WRITE" ;
		$result=$connection2->query($sql);   
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL.="&addReturn=fail2" ;
		header("Location: {$URL}");
		break ;
	}
	
	//Get next autoincrement
	try {
		$sqlAI="SHOW TABLE STATUS LIKE 'gibbonFinanceBudget'" ;
		$resultAI=$connection2->query($sqlAI);   
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL.="&addReturn=fail2" ;
		header("Location: {$URL}");
		break ;
	}
	
	$rowAI=$resultAI->fetch();
	$AI=str_pad($rowAI['Auto_increment'], 4, "0", STR_PAD_LEFT) ;
	
			
	if ($name=="" OR $nameShort=="" OR $active=="" OR $category=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check for uniqueness
		try {
			$data=array("name"=>$name, "nameShort"=>$nameShort); 
			$sql="SELECT * FROM gibbonFinanceBudget WHERE name=:name OR nameShort=:nameShort" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("name"=>$name, "nameShort"=>$nameShort, "active"=>$active, "category"=>$category, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="INSERT INTO gibbonFinanceBudget SET name=:name, nameShort=:nameShort, active=:active, category=:category, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='" . date("Y-m-d H:i:s") . "'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
		
			//Scan through staff
			$partialFail=FALSE ;
			$staff=array() ;
			if (isset($_POST["staff"])) {
				$staff=$_POST["staff"] ;
			}
			$access=$_POST["access"] ;
			if ($access!="Full" AND $access!="Write" AND $access!="Read") {
				$role="Read" ;
			}
			if (count($staff)>0) {
				foreach ($staff as $t) {
					//Check to see if person is already registered in this budget
					try {
						$dataGuest=array("gibbonPersonID"=>$t, "gibbonFinanceBudgetID"=>$AI); 
						$sqlGuest="SELECT * FROM gibbonFinanceBudgetPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
						$resultGuest=$connection2->prepare($sqlGuest);
						$resultGuest->execute($dataGuest);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					if ($resultGuest->rowCount()==0) {
						try {
							$data=array("gibbonPersonID"=>$t, "gibbonFinanceBudgetID"=>$AI, "access"=>$access); 
							$sql="INSERT INTO gibbonFinanceBudgetPerson SET gibbonPersonID=:gibbonPersonID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID, access=:access" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
					}
				}
			}
		
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { }

			if ($partialFail==TRUE) {
				//Fail 5
				$URL.="&addReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>