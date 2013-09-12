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
$date=$_POST["date"] ;
$type=$_POST["type"] ;
$name=$_POST["name"] ;
$description=$_POST["description"] ;
$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$dateStamp=$_POST["dateStamp"] ;
$gibbonSchoolYearTermID=$_POST["gibbonSchoolYearTermID"] ;
$firstDay=$_POST["firstDay"] ;
$lastDay=$_POST["lastDay"] ;
$schoolOpen=NULL ;
if (is_numeric($_POST["schoolOpenH"]) AND is_numeric($_POST["schoolOpenM"])) {
	$schoolOpen=$_POST["schoolOpenH"] . ":" . $_POST["schoolOpenM"] . ":00" ;
}
$schoolStart=NULL ;
if (is_numeric($_POST["schoolStartH"]) AND is_numeric($_POST["schoolStartM"])) {
	$schoolStart=$_POST["schoolStartH"] . ":" . $_POST["schoolStartM"] . ":00" ;
}
$schoolEnd=NULL ;
if (is_numeric($_POST["schoolEndH"]) AND is_numeric($_POST["schoolEndM"])) {
	$schoolEnd=$_POST["schoolEndH"] . ":" . $_POST["schoolEndM"] . ":00" ;
}
$schoolClose=NULL ;
if (is_numeric($_POST["schoolCloseH"]) AND is_numeric($_POST["schoolCloseM"])) {
	$schoolClose=$_POST["schoolCloseH"] . ":" . $_POST["schoolCloseM"] . ":00" ;
}

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/schoolYearSpecialDay_manage_add.php")==FALSE) {
	//Fail 0
	$URL = $URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($date=="" OR $type=="" OR $name=="" OR $gibbonSchoolYearID=="" OR $dateStamp=="" OR $gibbonSchoolYearTermID=="" OR $firstDay=="" OR $lastDay=="") {
		//Fail 3
		$URL = $URL . "&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("date"=>dateConvert($date)); 
			$sql="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL = $URL . "&addReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($dateStamp<$firstDay OR $dateStamp>$lastDay) {
			//Fail 5
			$URL = $URL . "&addReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			if ($result->rowCount()>0) {
				//Fail 4
				$URL = $URL . "&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {	
				//Write to database
				try {
					$data=array("gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID, "date"=>dateConvert($date), "type"=>$type, "name"=>$name, "description"=>$description, "schoolOpen"=>$schoolOpen, "schoolStart"=>$schoolStart, "schoolEnd"=>$schoolEnd, "schoolClose"=>$schoolClose); 
					$sql="INSERT INTO gibbonSchoolYearSpecialDay SET gibbonSchoolYearTermID=:gibbonSchoolYearTermID, date=:date, type=:type, name=:name, description=:description,schoolOpen=:schoolOpen, schoolStart=:schoolStart, schoolEnd=:schoolEnd, schoolClose=:schoolClose" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				//Success 0
				$URL = $URL . "&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>