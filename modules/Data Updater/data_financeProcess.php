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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/data_finance.php&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_finance.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonFinanceInvoiceeID=="") {
		//Fail1
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Get action with highest precendence
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL.="&return=error0$params" ;
			header("Location: {$URL}");
		}
		else {
			//Check access to person
			$checkCount=0 ;
			if ($highestAction=="Update Finance Data_any") {
				try {
					$dataSelect=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
					$sqlSelect="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID ORDER BY surname, preferredName" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { }
				$checkCount=$resultSelect->rowCount() ;
			}
			else {
				try {
					$dataCheck=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCheck="SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { }
				while ($rowCheck=$resultCheck->fetch()) {
					try {
						$dataCheck2=array("gibbonFamilyID"=>$rowCheck["gibbonFamilyID"]); 
						$sqlCheck2="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoiceeID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID" ;
						$resultCheck2=$connection2->prepare($sqlCheck2);
						$resultCheck2->execute($dataCheck2);
					}
					catch(PDOException $e) { }
					while ($rowCheck2=$resultCheck2->fetch()) {
						if ($gibbonFinanceInvoiceeID==$rowCheck2["gibbonFinanceInvoiceeID"]) {
							$checkCount++ ;
						}
					}
				}
			}
			
			if ($checkCount<1) {
			//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				//Proceed!
				$invoiceTo=$_POST["invoiceTo"] ;
				if ($invoiceTo=="Company") {
					$companyName=$_POST["companyName"] ;
					$companyContact=$_POST["companyContact"] ;
					$companyAddress=$_POST["companyAddress"] ;
					$companyEmail=$_POST["companyEmail"] ;
					$companyCCFamily=$_POST["companyCCFamily"] ;
					$companyPhone=$_POST["companyPhone"] ;
					$companyAll=$_POST["companyAll"] ;
					$gibbonFinanceFeeCategoryIDList=NULL ;
					if ($companyAll=="N") {
						$gibbonFinanceFeeCategoryIDList=="" ;
						$gibbonFinanceFeeCategoryIDArray=$_POST["gibbonFinanceFeeCategoryIDList"] ;
						if (count($gibbonFinanceFeeCategoryIDArray)>0) {
							foreach ($gibbonFinanceFeeCategoryIDArray AS $gibbonFinanceFeeCategoryID) {
								$gibbonFinanceFeeCategoryIDList.=$gibbonFinanceFeeCategoryID . "," ;
							}
							$gibbonFinanceFeeCategoryIDList=substr($gibbonFinanceFeeCategoryIDList,0,-1) ;
						}
					}
				}
				else {
					$companyName=NULL ;
					$companyContact=NULL ;
					$companyAddress=NULL ;
					$companyEmail=NULL ;
					$companyCCFamily=NULL ;
					$companyPhone=NULL ;
					$companyAll=NULL ;
					$gibbonFinanceFeeCategoryIDList=NULL ;
				}
				
				//Attempt to notify to DBA
				if ($_SESSION[$guid]["organisationDBA"]!="") {
					$notificationText=sprintf(__($guid, 'A finance data update request has been submitted.')) ;
					setNotification($connection2, $guid, $_SESSION[$guid]["organisationDBA"], $notificationText, "Data Updater", "/index.php?q=/modules/User Admin/data_finance.php") ;
				}
				
				//Write to database
				$existing=$_POST["existing"] ;
				
				try {
					if ($existing!="N") {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "invoiceTo"=>$invoiceTo, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyCCFamily"=>$companyCCFamily, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "gibbonFinanceInvoiceeUpdateID"=>$existing); 
						$sql="UPDATE gibbonFinanceInvoiceeUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID" ;
					}
					else {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "invoiceTo"=>$invoiceTo, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyCCFamily"=>$companyCCFamily, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonFinanceInvoiceeUpdate SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, gibbonPersonIDUpdater=:gibbonPersonIDUpdater" ;
					}
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