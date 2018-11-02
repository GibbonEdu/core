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

use Gibbon\Module\Attendance\AttendanceView;

//Gibbon system-wide includes
require __DIR__ . '/../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php' ;

$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$currentDate=$_POST["currentDate"] ;
$today=date("Y-m-d");

$moduleName = getModuleName($_POST["address"]);

if ($moduleName == "Planner") {
	$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $moduleName . "/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&gibbonCourseClassID=$gibbonCourseClassID&date=" . $currentDate ;
} else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $moduleName . "/attendance_take_byCourseClass.php&gibbonCourseClassID=$gibbonCourseClassID&currentDate=" . dateConvertBack($guid, $currentDate) ;
}

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
	die();
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonCourseClassID=="" AND $currentDate=="") {
		//Fail1
		$URL.="&return=error1" ;
		header("Location: {$URL}");
		die();
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
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			die();
		}

		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&return=error1" ;
			header("Location: {$URL}");
			die();
		}
		else {
			//Check that date is not in the future
			if ($currentDate>$today) {
				//Fail 4
				$URL.="&return=error3" ;
				header("Location: {$URL}");
				die();
			}
			else {
				//Check that date is a school day
				if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
					//Fail 5
					$URL.="&return=error3" ;
					header("Location: {$URL}");
					die();
				}
				else {
					//Write to database
					require_once __DIR__ . '/src/AttendanceView.php';
					$attendance = new AttendanceView($gibbon, $pdo);

					try {
						$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate);
						$sql="SELECT gibbonAttendanceLogCourseClassID FROM gibbonAttendanceLogCourseClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY gibbonAttendanceLogCourseClassID DESC" ;
						$resultLog=$connection2->prepare($sql);
						$resultLog->execute($data);
					}
					catch(PDOException $e) {
						//Fail 2
						$URL.="&return=error2" ;
						header("Location: {$URL}");
						die();
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
						$URL.="&return=error2" ;
						header("Location: {$URL}");
						die();
					}

					$count=$_POST["count"] ;
					$partialFail=FALSE ;

					for ($i=0; $i<$count; $i++) {
						$gibbonPersonID=$_POST[$i . "-gibbonPersonID"] ;

						$type=$_POST[$i . "-type"] ;
						$reason=$_POST[$i . "-reason"] ;
						$comment=$_POST[$i . "-comment"] ;

						$attendanceCode = $attendance->getAttendanceCodeByType($type);
						$direction = $attendanceCode['direction'];

						//Check for last record on same day
						try {
							$data=array("gibbonPersonID"=>$gibbonPersonID, "date"=>$currentDate . "%");
							$sql="SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) {
							//Fail 2
							$URL.="&return=error2" ;
							header("Location: {$URL}");
							die();
						}

                        //Check context, gibbonCourseClassID and type, updating only if not a match
                        $existing = false ;
                        $gibbonAttendanceLogPersonID = '';
                        if ($result->rowCount()>0) {
                            $row=$result->fetch() ;
                            if ($row['context'] == 'Class' && $row['gibbonCourseClassID'] == $gibbonCourseClassID && $row['type'] == $type) {
                                $existing = true ;
                                $gibbonAttendanceLogPersonID = $row['gibbonAttendanceLogPersonID'];
                            }
                        }

						if (!$existing) {
							//If no records then create one
							try {
								$dataUpdate=array("gibbonPersonID"=>$gibbonPersonID, "direction"=>$direction, "type"=>$type, "reason"=>$reason, "comment"=>$comment, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s"));
								$sqlUpdate="INSERT INTO gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context='Class', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken" ;
								$resultUpdate=$connection2->prepare($sqlUpdate);
								$resultUpdate->execute($dataUpdate);
							}
							catch(PDOException $e) {
								$partialFail=TRUE ;
							}
						}
						else {
							try {
								$dataUpdate=array("gibbonAttendanceLogPersonID"=>$gibbonAttendanceLogPersonID, "gibbonPersonID"=>$gibbonPersonID, "direction"=>$direction, "type"=>$type, "reason"=>$reason, "comment"=>$comment, "gibbonPersonIDTaker"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate, "timestampTaken"=>date("Y-m-d H:i:s"));
								$sqlUpdate="UPDATE gibbonAttendanceLogPerson SET gibbonAttendanceCodeID=(SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name=:type), gibbonPersonID=:gibbonPersonID, direction=:direction, type=:type, context='Class', reason=:reason, comment=:comment, gibbonPersonIDTaker=:gibbonPersonIDTaker, gibbonCourseClassID=:gibbonCourseClassID, date=:date, timestampTaken=:timestampTaken WHERE gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID" ;
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
						$URL.="&return=warning1" ;
						header("Location: {$URL}");
						die();
					}
					else {
						//Success 0
						$URL.="&return=success0&time=" . date("H-i-s") ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
