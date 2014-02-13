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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activities_manage_add.php&search=" . $_GET["search"] ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_add.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$name=$_POST["name"] ;
	$active=$_POST["active"] ;
	$dateType=$_POST["dateType"] ;
	if ($dateType=="Term") {
		$terms=$_POST["gibbonSchoolYearTermID"] ;
		$gibbonSchoolYearTermIDList="" ;
		for ($i=0; $i<count($terms); $i++) {
			$gibbonSchoolYearTermIDList=$gibbonSchoolYearTermIDList . $terms[$i] . "," ;
		}
		$gibbonSchoolYearTermIDList=substr($gibbonSchoolYearTermIDList,0, -1) ;
	}
	else if ($dateType=="Date") {
		$listingStart=dateConvert($guid, $_POST["listingStart"]) ;
		$listingEnd=dateConvert($guid, $_POST["listingEnd"]) ;
		$programStart=dateConvert($guid, $_POST["programStart"]) ;
		$programEnd=dateConvert($guid, $_POST["programEnd"]) ;
	}
	$gibbonYearGroupIDList="" ;
	for ($i=0; $i<$_POST["count"]; $i++) {
		if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
			if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
				$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
			}
		}
	}
	$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;
	$maxParticipants=$_POST["maxParticipants"] ;
	$payment=$_POST["payment"] ;
	$description=$_POST["description"] ;
	
	//Lock activities table
	try {
		$data=array(); 
		$sql="LOCK TABLES gibbonActivity WRITE, gibbonActivitySlot WRITE, gibbonActivityStaff WRITE" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL=$URL . "&addReturn=fail2" ;
		header("Location: {$URL}");
		break ;
	}
	
	//Get next autoincrement
	try {
		$dataAI=array(); 
		$sqlAI="SHOW TABLE STATUS LIKE 'gibbonActivity'";
		$resultAI=$connection2->prepare($sqlAI);
		$resultAI->execute($dataAI);
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL=$URL . "&addReturn=fail2" ;
		header("Location: {$URL}");
		break ;
	}
	
	if ($resultAI->rowCount()==1) {
		$rowAI=$resultAI->fetch();
		$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
		
		if ($dateType=="" OR $name=="" OR $active=="" OR $maxParticipants=="" OR $payment=="" OR ($dateType=="Date" AND ($listingStart=="" OR $listingEnd=="" OR $programStart=="" OR $programEnd=="")) OR ($dateType=="Term" AND $gibbonSchoolYearTermIDList=="")) {
			//Fail 3
			$URL=$URL . "&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Scan through slots
			$partialFail=FALSE ;
			for ($i=1; $i<3; $i++) {
				$gibbonDaysOfWeekID=$_POST["gibbonDaysOfWeekID$i"] ;
				$timeStart=$_POST["timeStart$i"] ;
				$timeEnd=$_POST["timeEnd$i"] ;
				$type=$_POST["type"] ;
				$gibbonSpaceID=NULL ;
				if ($type=="Internal") {
					if ($_POST["gibbonSpaceID$i"]!="") {
						$gibbonSpaceID=$_POST["gibbonSpaceID$i"] ;
					}
					else {
						$gibbonSpaceID=NULL ;
					}
					$locationExternal="" ;
				}
				else {
					$locationExternal=$_POST["location" . $i . "External"] ;
				}
				
				if ($gibbonDaysOfWeekID!="" AND $timeStart!="" AND $timeEnd!="") {
					try {
						$data=array("AI"=>$AI, "gibbonDaysOfWeekID"=>$gibbonDaysOfWeekID, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "gibbonSpaceID"=>$gibbonSpaceID, "locationExternal"=>$locationExternal); 
						$sql="INSERT INTO gibbonActivitySlot SET gibbonActivityID=:AI, gibbonDaysOfWeekID=:gibbonDaysOfWeekID, timeStart=:timeStart, timeEnd=:timeEnd, gibbonSpaceID=:gibbonSpaceID, locationExternal=:locationExternal" ;
						$result=$connection2->prepare($sql);
						$result->execute($data); 
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
				}
			}
			
			//Scan through staff
			$staff=NULL ;
			if (isset($_POST["staff"])) {
				$staff=$_POST["staff"] ;
			}
			$role=$_POST["role"] ;
			if ($role=="") {
				$role="Other" ;
			}
			if (count($staff)>0) {
				foreach ($staff as $t) {
					//Check to see if person is already registered in this activity
					try {
						$dataGuest=array("gibbonPersonID"=>$t, "gibbonActivityID"=>$AI); 
						$sqlGuest="SELECT * FROM gibbonActivityStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID" ;
						$resultGuest=$connection2->prepare($sqlGuest);
						$resultGuest->execute($dataGuest);
					}
					catch(PDOException $e) {
						$partialFail=TRUE ;
					}

					if ($resultGuest->rowCount()==0) {
						try {
							$data=array("gibbonPersonID"=>$t, "gibbonActivityID"=>$AI, "role"=>$role); 
							$sql="INSERT INTO gibbonActivityStaff SET gibbonPersonID=:gibbonPersonID, gibbonActivityID=:gibbonActivityID, role=:role" ;
							$result=$connection2->prepare($sql);
							$result->execute($data); 
						}
						catch(PDOException $e) { 
							print "here<div class='error'>" . $e->getMessage() . "</div>" ; 
							$partialFail=TRUE ;
						}
					}
				}
			}
			
		
			//Write to database
			$type=$_POST["type"] ;
			
			try {
				if ($dateType=="Date") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$AI, "name"=>$name, "type"=>$type, "active"=>$active, "listingStart"=>$listingStart, "listingEnd"=>$listingEnd, "programStart"=>$programStart, "programEnd"=>$programEnd, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "maxParticipants"=>$maxParticipants, "payment"=>$payment, "description"=>$description); 
					$sql="INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonActivityID=:gibbonActivityID, name=:name, type=:type, active=:active, gibbonSchoolYearTermIDList='', listingStart=:listingStart, listingEnd=:listingEnd, programStart=:programStart, programEnd=:programEnd, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, description=:description" ;
				}
				else {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$AI, "name"=>$name, "type"=>$type, "active"=>$active, "gibbonSchoolYearTermIDList"=>$gibbonSchoolYearTermIDList, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "maxParticipants"=>$maxParticipants, "payment"=>$payment, "description"=>$description); 
					$sql="INSERT INTO gibbonActivity SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonActivityID=:gibbonActivityID, name=:name, type=:type, active=:active, gibbonSchoolYearTermIDList=:gibbonSchoolYearTermIDList, listingStart=NULL, listingEnd=NULL, programStart=NULL, programEnd=NULL, gibbonYearGroupIDList=:gibbonYearGroupIDList, maxParticipants=:maxParticipants, payment=:payment, description=:description" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&addReturn=fail2" ;
				header("Location: {$URL}");
				break ; 
			}

			//Unlock module table
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { }			
			
			if ($partialFail==TRUE) {
				//Fail 5
				$URL=$URL . "&addReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL=$URL . "&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>