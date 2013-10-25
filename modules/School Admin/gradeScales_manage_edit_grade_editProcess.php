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

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonScaleGradeID=$_GET["gibbonScaleGradeID"] ;
$gibbonScaleID=$_GET["gibbonScaleID"] ;

if ($gibbonScaleID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/gradeScales_manage_edit_grade_edit.php&gibbonScaleID=$gibbonScaleID&gibbonScaleGradeID=$gibbonScaleGradeID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_edit_grade_edit.php")==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if tt specified
		if ($gibbonScaleGradeID=="") {
			//Fail1
			$URL=$URL . "&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonScaleGradeID"=>$gibbonScaleGradeID); 
				$sql="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&deleteReturn=fail2" ;
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
				$value=$_POST["value"] ;
				$descriptor=$_POST["descriptor"] ;
				$sequenceNumber=$_POST["sequenceNumber"] ;

				if ($value=="" OR $descriptor=="" OR $sequenceNumber=="") {
					//Fail 3
					$URL=$URL . "&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					//Check unique inputs for uniquness
					try {
						$data=array("value"=>$value, "sequenceNumber"=>$sequenceNumber, "gibbonScaleID"=>$gibbonScaleID, "gibbonScaleGradeID"=>$gibbonScaleGradeID); 
						$sql="SELECT * FROM gibbonScaleGrade WHERE (value=:value OR sequenceNumber=:sequenceNumber) AND gibbonScaleID=:gibbonScaleID AND NOT gibbonScaleGradeID=:gibbonScaleGradeID" ;
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
							$data=array("value"=>$value, "descriptor"=>$descriptor, "sequenceNumber"=>$sequenceNumber, "gibbonScaleGradeID"=>$gibbonScaleGradeID); 
							$sql="UPDATE gibbonScaleGrade SET value=:value, descriptor=:descriptor, sequenceNumber=:sequenceNumber WHERE gibbonScaleGradeID=:gibbonScaleGradeID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "Here" ;
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
}
?>