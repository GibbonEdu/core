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

$name=$_POST["name"] ;
$nameShort=$_POST["nameShort"] ;
$active=$_POST["active"] ;
$count=$_POST["count"] ;
$gibbonYearGroupIDList="" ;
for ($i=0; $i<$count; $i++) {
	if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
		if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
			$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
		}
	}
}
$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;
$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/tt_add.php&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonSchoolYearID=="" OR $name=="" OR $nameShort=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonTT WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID)" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&addReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("name"=>$name, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "nameShort"=>$nameShort, "active"=>$active, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList); 
				$sql="INSERT INTO gibbonTT SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, active=:active, gibbonYearGroupIDList=:gibbonYearGroupIDList" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
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
?>