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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$search=$_GET["search"] ;

if ($gibbonSchoolYearID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/billingSchedule_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/billingSchedule_manage_add.php")==FALSE) {
		//Fail 0
		$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		$name=$_POST["name"] ;
		$active=$_POST["active"] ;
		$description=$_POST["description"] ;
		$invoiceIssueDate=$_POST["invoiceIssueDate"] ;
		$invoiceDueDate=$_POST["invoiceDueDate"] ;
			
		if ($name=="" OR $active=="" OR $invoiceIssueDate=="" OR $invoiceDueDate=="") {
			//Fail 3
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
	
			//Write to database
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "name"=>$name, "active"=>$active, "description"=>$description, "invoiceIssueDate"=>dateConvert($guid, $invoiceIssueDate), "invoiceDueDate"=>dateConvert($guid, $invoiceDueDate), "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="INSERT INTO gibbonFinanceBillingSchedule SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, active=:active, description=:description, invoiceIssueDate=:invoiceIssueDate, invoiceDueDate=:invoiceDueDate, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='" . date("Y-m-d H:i:s") . "'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
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
?>