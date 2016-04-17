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

$gibbonStaffID=$_GET["gibbonStaffID"] ;
$search=$_GET["search"] ;

if ($gibbonStaffID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/staff_manage_edit_contract_add.php&gibbonStaffID=$gibbonStaffID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_manage_edit_contract_add.php")==FALSE) {
		//Fail 0
		$URL.="&addReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonStaffID=="") {
			//Fail1
			$URL.="&addReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonStaffID"=>$gibbonStaffID); 
				$sql="SELECT gibbonStaffID, username FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;
				$username=$row["username"] ;
				
				$title=$_POST["title"] ;
				$status=$_POST["status"] ;
				$dateStart=NULL ;
				if (isset($_POST["dateStart"])) {
					$dateStart=dateConvert($guid, $_POST["dateStart"]) ;
				}
				$dateEnd=NULL ;
				if (isset($_POST["dateEnd"])) {
					$dateEnd=dateConvert($guid, $_POST["dateEnd"]) ;
				}
				$salaryScale=NULL ;
				if (isset($_POST["salaryScale"])) {
					$salaryScale=$_POST["salaryScale"] ;
				}
				$salaryAmount=NULL ;
				if (isset($_POST["salaryAmount"])) {
					$salaryAmount=$_POST["salaryAmount"] ;
				}
				$salaryPeriod=NULL ;
				if (isset($_POST["salaryPeriod"])) {
					$salaryPeriod=$_POST["salaryPeriod"] ;
				}
				$responsibility=NULL ;
				if (isset($_POST["responsibility"])) {
					$responsibility=$_POST["responsibility"] ;
				}
				$responsibilityAmount=NULL ;
				if (isset($_POST["responsibilityAmount"])) {
					$responsibilityAmount=$_POST["responsibilityAmount"] ;
				}
				$responsibilityPeriod=NULL ;
				if (isset($_POST["responsibilityPeriod"])) {
					$responsibilityPeriod=$_POST["responsibilityPeriod"] ;
				}
				$housingAmount=NULL ;
				if (isset($_POST["housingAmount"])) {
					$housingAmount=$_POST["housingAmount"] ;
				}
				$housingPeriod=NULL ;
				if (isset($_POST["housingPeriod"])) {
					$housingPeriod=$_POST["housingPeriod"] ;
				}
				$travelAmount=NULL ;
				if (isset($_POST["travelAmount"])) {
					$travelAmount=$_POST["travelAmount"] ;
				}
				$travelPeriod=NULL ;
				if (isset($_POST["travelPeriod"])) {
					$travelPeriod=$_POST["travelPeriod"] ;
				}
				$retirementAmount=NULL ;
				if (isset($_POST["retirementAmount"])) {
					$retirementAmount=$_POST["retirementAmount"] ;
				}
				$retirementPeriod=NULL ;
				if (isset($_POST["retirementPeriod"])) {
					$retirementPeriod=$_POST["retirementPeriod"] ;
				}
				$bonusAmount=NULL ;
				if (isset($_POST["bonusAmount"])) {
					$bonusAmount=$_POST["bonusAmount"] ;
				}
				$bonusPeriod=NULL ;
				if (isset($_POST["bonusPeriod"])) {
					$bonusPeriod=$_POST["bonusPeriod"] ;
				}
				$education=NULL ;
				if (isset($_POST["education"])) {
					$education=$_POST["education"] ;
				}
				$notes=NULL ;
				if (isset($_POST["notes"])) {
					$notes=$_POST["notes"] ;
				}
				$contractUpload=NULL ;
				if ($_FILES['file1']["tmp_name"]!="") {
					$time=time() ;
					//Check for folder in uploads based on today's date
					$path=$_SESSION[$guid]["absolutePath"] ; ;
					if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
						mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
					}
					//Move 240 attached file, if there is one
					if ($_FILES['file1']["tmp_name"]!="") {
						$unique=FALSE;
						$count=0 ;
						while ($unique==FALSE AND $count<100) {
							$suffix=randomPassword(16) ;
							if ($count==0) {
								$contractUpload="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_" . $suffix . strrchr($_FILES["file1"]["name"], ".") ;
							}
							else {
								$contractUpload="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "" . "_$count_" . $suffix . strrchr($_FILES["file1"]["name"], ".") ;
							}
							
							if (!(file_exists($path . "/" . $contractUpload))) {
								$unique=TRUE ;
							}
							$count++ ;
						}
						if (!(move_uploaded_file($_FILES["file1"]["tmp_name"],$path . "/" . $contractUpload))) {
							$contractUpload="" ;
							$imageFail=TRUE ;
						}
					}
					else {
						$contractUpload="" ;
					}
				}
			
				if ($title=="" OR $status=="") {
					//Fail 3
					$URL.="&addReturn=fail3&step=1" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonStaffID"=>$gibbonStaffID, "title"=>$title, "status"=>$status, "dateStart"=>$dateStart, "dateEnd"=>$dateEnd, "salaryScale"=>$salaryScale, "salaryAmount"=>$salaryAmount, "salaryPeriod"=>$salaryPeriod, "responsibility"=>$responsibility, "responsibilityAmount"=>$responsibilityAmount, "responsibilityPeriod"=>$responsibilityPeriod, "housingAmount"=>$housingAmount, "housingPeriod"=>$housingPeriod, "travelAmount"=>$travelAmount, "travelPeriod"=>$travelPeriod, "retirementAmount"=>$retirementAmount, "retirementPeriod"=>$retirementPeriod, "bonusAmount"=>$bonusAmount, "bonusPeriod"=>$bonusPeriod, "education"=>$education, "notes"=>$notes, "contractUpload"=>$contractUpload, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="INSERT INTO gibbonStaffContract SET gibbonStaffID=:gibbonStaffID, title=:title, status=:status, dateStart=:dateStart, dateEnd=:dateEnd, salaryScale=:salaryScale, salaryAmount=:salaryAmount, salaryPeriod=:salaryPeriod, responsibility=:responsibility, responsibilityAmount=:responsibilityAmount, responsibilityPeriod=:responsibilityPeriod, housingAmount=:housingAmount, housingPeriod=:housingPeriod, travelAmount=:travelAmount, travelPeriod=:travelPeriod, retirementAmount=:retirementAmount, retirementPeriod=:retirementPeriod, bonusAmount=:bonusAmount, bonusPeriod=:bonusPeriod, education=:education, notes=:notes, contractUpload=:contractUpload, gibbonPersonIDCreator=:gibbonPersonIDCreator" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
			
					//Success 0
					$URL.="&addReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>