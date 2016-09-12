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

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//Module includes
include "./moduleFunctions.php" ;

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

$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$currentDate=$_POST["currentDate"] ;
$today=date("Y-m-d");
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/attendance_take_byCourseClass.php&gibbonCourseClassID=$gibbonCourseClassID&currentDate=" . dateConvertBack($guid, $currentDate) ;

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonCourseClassID=="" AND $currentDate=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT * FROM gibbonCourseClass WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {	
			//Check that date is not in the future
			if ($currentDate>$today) {
				//Fail 4
				$URL.="&updateReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Check that date is a school day
				if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
					//Fail 5
					$URL.="&updateReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database

					try {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate); 
						$sql="SELECT gibbonAttendanceLogCourseClassID FROM gibbonAttendanceLogCourseClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY gibbonAttendanceLogCourseClassID DESC" ;
						$resultLog=$connection2->prepare($sql);
						$resultLog->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}

					if ($resultLog->rowCount()<1) {
						$data=array("gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s")); 
						$sql="INSERT INTO gibbonAttendanceLogCourseClass SET gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken" ;
						
					} else {
						$resultUpdate=$resultLog->fetch() ;
						$data=array("gibbonAttendanceLogCourseClassID" => $resultUpdate['gibbonAttendanceLogCourseClassID'], "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s")); 
						$sql="UPDATE gibbonAttendanceLogCourseClass SET gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken WHERE gibbonAttendanceLogCourseClassID=:gibbonAttendanceLogCourseClassID" ;
					}

					try {
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}

					$count=$_POST["count"] ;
					$partialFail=FALSE ;
					
					for ($i=0; $i<$count; $i++) {
						$gibbonPersonID=$_POST[$i . "-gibbonPersonID"] ;
						$direction="In" ;
						if ($_POST[$i . "-type"]=="Absent" OR $_POST[$i . "-type"]=="Left" OR $_POST[$i . "-type"]=="Left - Early") {
							$direction="Out" ;
						}
						$type=$_POST[$i . "-type"] ;
						$reason=$_POST[$i . "-reason"] ;
						$comment=$_POST[$i . "-comment"] ;
						
						//Check for last record on same day
						try {
							$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$gibbonPersonID, "date"=>$currentDate . "%"); 
							$sql="SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ; 
						}
						
						if ($result->rowCount()<1) {
							//If no records then create one
							try {
								$dataUpdate=array("gibbonPersonID"=>$gibbonPersonID, "direction"=>$direction, "type"=>$type, "reason"=>$reason, "comment"=>$comment, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s")); 
								$sqlUpdate="INSERT INTO gibbonAttendanceLogPerson SET gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken" ;
								$resultUpdate=$connection2->prepare($sqlUpdate);
								$resultUpdate->execute($dataUpdate);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
						else {
							$row=$result->fetch() ;
							try {
								$dataUpdate=array("gibbonAttendanceLogPersonID"=>$row["gibbonAttendanceLogPersonID"], "gibbonPersonID"=>$gibbonPersonID, "direction"=>$direction, "type"=>$type, "reason"=>$reason, "comment"=>$comment, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s")); 
								$sqlUpdate="UPDATE gibbonAttendanceLogPerson SET gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken WHERE gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID" ;
								$resultUpdate=$connection2->prepare($sqlUpdate);
								$resultUpdate->execute($dataUpdate);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				
					if ($partialFail==TRUE) {
						//Fail 3
						$URL.="&updateReturn=fail3" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL.="&updateReturn=success0&time=" . date("H-i-s") ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>