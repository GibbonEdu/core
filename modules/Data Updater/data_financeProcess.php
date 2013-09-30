<?
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

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/data_finance.php&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Updater/data_finance.php")==FALSE) {
	//Fail 0
	$URL = $URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonFinanceInvoiceeID=="") {
		//Fail1
		$URL = $URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		//Get action with highest precendence
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL = $URL . "&updateReturn=fail0$params" ;
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
				$URL = $URL . "&updateReturn=fail2" ;
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
					$companyPhone=$_POST["companyPhone"] ;
					$companyAll=$_POST["companyAll"] ;
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
				
				//Attempt to send email to DBA
				if ($_SESSION[$guid]["organisationDBAEmail"]!="" AND $_SESSION[$guid]["organisationDBAName"]!="") {
					$to = $_SESSION[$guid]["organisationDBAEmail"];
					$subject = $_SESSION[$guid]["organisationNameShort"] . " Gibbon Finance Data Update Request";
					$body = "You have a new finance data update request from Gibbon. Please log in and process it as soon as possible.\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
					$headers = "From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;
					mail($to, $subject, $body, $headers) ;
				}
				
				//Write to database
				$existing=$_POST["existing"] ;
				
				try {
					if ($existing!="N") {
						$data=array("invoiceTo"=>$invoiceTo, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "gibbonFinanceInvoiceeUpdateID"=>$existing); 
						$sql="UPDATE gibbonFinanceInvoiceeUpdate SET invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID" ;
					}
					else {
						$data=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "invoiceTo"=>$invoiceTo, "companyName"=>$companyName, "companyContact"=>$companyContact, "companyAddress"=>$companyAddress, "companyEmail"=>$companyEmail, "companyPhone"=>$companyPhone, "companyAll"=>$companyAll, "gibbonFinanceFeeCategoryIDList"=>$gibbonFinanceFeeCategoryIDList, "gibbonPersonIDUpdater"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonFinanceInvoiceeUpdate SET gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, gibbonPersonIDUpdater=:gibbonPersonIDUpdater" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($partialFail==TRUE) {
					//Fail 5
					$URL = $URL . "&updateReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL = $URL . "&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>