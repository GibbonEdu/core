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

require getcwd() . "/../config.php" ;
require getcwd() . "/../functions.php" ;
require getcwd() . "/../lib/PHPMailer/class.phpmailer.php";
						
//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

getSystemSettings($guid, $connection2) ;

setCurrentSchoolYear($guid, $connection2) ;

//Set up for i18n via gettext
if (isset($_SESSION[$guid]["i18n"]["code"])) {
	if ($_SESSION[$guid]["i18n"]["code"]!=NULL) {
		putenv("LC_ALL=" . $_SESSION[$guid]["i18n"]["code"]);
		setlocale(LC_ALL, $_SESSION[$guid]["i18n"]["code"]);
		bindtextdomain("gibbon", getcwd() . "/../i18n");
		textdomain("gibbon");
	}
}

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name()!="cli") { 
	print __($guid, "This script cannot be run from a browser, only via CLI.") . "\n\n" ;
}
else {
	$currentDate=date("Y-m-d") ;
	
	if (isSchoolOpen($guid, $currentDate, $connection2, TRUE)) {
		$ids=array() ;
		$report="" ;
		$reportInner="" ;
	
		//Produce array of attendance data
		try {
			$data=array("date"=>$currentDate); 
			$sql="SELECT gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$report=__($guid, "Your request failed due to a database error.") ; 
		}
	
		$log=array() ;
		while ($row=$result->fetch()) {
			$log[$row["gibbonRollGroupID"]]=TRUE ;
		}

		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"] ); 
			$sql="SELECT gibbonRollGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$report=__($guid, "Your request failed due to a database error.") ;
		}
	
		if ($result->rowCount()<1) {
			$report=__($guid, "There are no records to display.") ;
		}
		else {
			$count=0 ;
			$countInner=0 ;
			while ($row=$result->fetch()) {
				if (isset($log[$row["gibbonRollGroupID"]])==FALSE) {
					$count++ ;
					$reportInner.=$row["name"] ."<br/>" ;
					if ($row["gibbonPersonIDTutor"]!="") {
						$ids[$countInner][0]=$row["gibbonRollGroupID"] ;
						$ids[$countInner][1]=$row["gibbonPersonIDTutor"] ;
						$countInner++ ;
					}
					if ($row["gibbonPersonIDTutor2"]!="") {
						$ids[$countInner][0]=$row["gibbonRollGroupID"] ;
						$ids[$countInner][1]=$row["gibbonPersonIDTutor2"] ;
						$countInner++ ;
					}
					if ($row["gibbonPersonIDTutor3"]!="") {
						$ids[$countInner][0]=$row["gibbonRollGroupID"] ;
						$ids[$countInner][1]=$row["gibbonPersonIDTutor3"] ;
						$countInner++ ;
					}
				}
			}
		}
		if (isset($count)) {
			if ($count==0) {
				$report=sprintf(__($guid, 'All form groups have been registered today (%1$s).'), dateConvertBack($guid, $currentDate)) ;
			}
			else {
				$report=sprintf(__($guid, '%1$s form groups have not been registered today  (%2$s).'), $count, dateConvertBack($guid, $currentDate)) . "<br/><br/>" . $reportInner ;
			}
		}
		
		print $report ;
	
		//Notify non-completing tutors
		foreach ($ids AS $id) {
			$notificationText=__($guid, 'You have not taken attendance yet today. Please do so as soon as possible.') ;
			setNotification($connection2, $guid, $id[1], $notificationText, "Attendance", "/index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $id[0] . "&currentDate=" . dateConvertBack($guid, date('Y-m-d'))) ;
		}
		
		//Notify admin {
		$notificationText=__($guid, 'An Attendance CLI script has run.') . " " . $report ;
		setNotification($connection2, $guid, $_SESSION[$guid]["organisationAdministrator"], $notificationText, "Attendance", "/index.php?q=/modules/Attendance/report_rollGroupsNotRegistered_byDate.php") ;
	}
}

?>