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

$gibbonSchoolYearTermID=$_GET["gibbonSchoolYearTermID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/schoolYearTerm_manage_edit.php&gibbonSchoolYearTermID=" . $gibbonSchoolYearTermID ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearTerm_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonSchoolYearTermID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID); 
			$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Validate Inputs
			$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
			$sequenceNumber=$_POST["sequenceNumber"] ;
			$name=$_POST["name"] ;
			$nameShort=$_POST["nameShort"] ;
			$firstDay=dateConvert($guid, $_POST["firstDay"]) ;
			$lastDay=dateConvert($guid, $_POST["lastDay"]) ;
			
			if ($gibbonSchoolYearID=="" OR $name=="" OR $nameShort=="" OR $sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR $firstDay=="" OR $lastDay=="") {
			//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$data=array("sequenceNumber"=>$sequenceNumber, "gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID); 
					$sql="SELECT * FROM gibbonSchoolYearTerm WHERE sequenceNumber=:sequenceNumber AND NOT gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "name"=>$name, "nameShort"=>$nameShort, "sequenceNumber"=>$sequenceNumber, "firstDay"=>$firstDay, "lastDay"=>$lastDay, "gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID); 
						$sql="UPDATE gibbonSchoolYearTerm SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
					}
					
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>