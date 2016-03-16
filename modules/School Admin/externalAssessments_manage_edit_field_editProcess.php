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

$gibbonExternalAssessmentFieldID=$_GET["gibbonExternalAssessmentFieldID"] ;
$gibbonExternalAssessmentID=$_GET["gibbonExternalAssessmentID"] ;
if ($gibbonExternalAssessmentID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID&gibbonExternalAssessmentFieldID=$gibbonExternalAssessmentFieldID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_edit_field_edit.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if tt specified
		if ($gibbonExternalAssessmentFieldID=="") {
			//Fail1
			$URL.="&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonExternalAssessmentFieldID"=>$gibbonExternalAssessmentFieldID); 
				$sql="SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
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
				$name=$_POST["name"] ;
				$category=$_POST["category"] ;
				$order=$_POST["order"] ;
				$gibbonScaleID=$_POST["gibbonScaleID"] ;
				$gibbonYearGroupIDList="" ;
				for ($i=0; $i<$_POST["count"]; $i++) {
					if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
						if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
							$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
						}
					}
				}
				$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;

				if ($gibbonExternalAssessmentID=="" OR $name=="" OR $category=="" OR $order=="" OR $gibbonScaleID=="") {
					//Fail 3
					$URL.="&updateReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("name"=>$name, "category"=>$category, "order"=>$order, "gibbonScaleID"=>$gibbonScaleID, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "gibbonExternalAssessmentFieldID"=>$gibbonExternalAssessmentFieldID); 
						$sql="UPDATE gibbonExternalAssessmentField SET name=:name, category=:category, `order`=:order, gibbonScaleID=:gibbonScaleID, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "Here" ;
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
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