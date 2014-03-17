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

$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID" ;

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_targets.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonCourseClassID=="") {
		//Fail1
		$URL=$URL . "&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		$count=$_POST["count"] ;
		$partialFail=FALSE ;
		
		for ($i=1;$i<=$count;$i++) {
			$gibbonPersonIDStudent=$_POST["$i-gibbonPersonID"] ;
			$gibbonScaleGradeID=NULL ;
			if ($_POST["$i-gibbonScaleGradeID"]!="") {
				$gibbonScaleGradeID=$_POST["$i-gibbonScaleGradeID"] ;
			}
			
			$selectFail=false ;
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonIDStudent"=>$gibbonPersonIDStudent); 
				$sql="SELECT * FROM gibbonMarkbookTarget WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
				$selectFail=true ;
			}
			if (!($selectFail)) {
				if ($result->rowCount()<1) {
					try {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonIDStudent"=>$gibbonPersonIDStudent, "gibbonScaleGradeID"=>$gibbonScaleGradeID); 
						$sql="INSERT INTO gibbonMarkbookTarget SET gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonScaleGradeID=:gibbonScaleGradeID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print $e->getMessage() ;
						$partialFail=TRUE ;
					}
				}
				else {
					$row=$result->fetch() ;
					//Update
					try {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonIDStudent"=>$gibbonPersonIDStudent, "gibbonScaleGradeID"=>$gibbonScaleGradeID); 
						$sql="UPDATE gibbonMarkbookTarget SET gibbonScaleGradeID=:gibbonScaleGradeID WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
				}
			}
		}
		
		//Return!
		if ($partialFail==TRUE) {
			//Fail 3
			$URL=$URL . "&updateReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			$URL=$URL . "&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>