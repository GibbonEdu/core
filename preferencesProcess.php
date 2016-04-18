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

include "./functions.php" ;
include "./config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Start session
@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check to see if academic year id variables are set, if not set them 
if (isset($_SESSION[$guid]["gibbonAcademicYearID"])==FALSE OR isset($_SESSION[$guid]["gibbonSchoolYearName"])==FALSE) {
	setCurrentSchoolYear($guid, $connection2) ;
}

$calendarFeedPersonal=$_POST["calendarFeedPersonal"] ;
$personalBackground="" ;
if (isset($_POST["personalBackground"])) {
	$personalBackground=$_POST["personalBackground"] ;
}
$gibbonThemeIDPersonal=$_POST["gibbonThemeIDPersonal"] ;
if ($gibbonThemeIDPersonal=="") {
	$gibbonThemeIDPersonal=NULL ;
}
$gibboni18nIDPersonal=$_POST["gibboni18nIDPersonal"] ;
if ($gibboni18nIDPersonal=="") {
	$gibboni18nIDPersonal=NULL ;
}
$receiveNotificationEmails=$_POST["receiveNotificationEmails"] ;
if ($receiveNotificationEmails=="") {
	$receiveNotificationEmails=NULL ;
}

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=preferences.php" ;

try {
	$data=array("calendarFeedPersonal"=>$calendarFeedPersonal, "personalBackground"=>$personalBackground, "gibbonThemeIDPersonal"=>$gibbonThemeIDPersonal, "gibboni18nIDPersonal"=>$gibboni18nIDPersonal, "receiveNotificationEmails"=>$receiveNotificationEmails, "username"=>$_SESSION[$guid]["username"]); 
	$sql="UPDATE gibbonPerson SET calendarFeedPersonal=:calendarFeedPersonal, personalBackground=:personalBackground, gibbonThemeIDPersonal=:gibbonThemeIDPersonal, gibboni18nIDPersonal=:gibboni18nIDPersonal, receiveNotificationEmails=:receiveNotificationEmails WHERE (username=:username)" ;
	$result=$connection2->prepare($sql);
	$result->execute($data);
}
catch(PDOException $e) { 
	$URL.="&editReturn=fail1" ;
	header("Location: {$URL}");
	exit() ;
}

//Update personal preferences in session
$_SESSION[$guid]["calendarFeedPersonal"]=$calendarFeedPersonal ;
$_SESSION[$guid]["personalBackground"]=$personalBackground ;
$_SESSION[$guid]["gibbonThemeIDPersonal"]=$gibbonThemeIDPersonal ;
$_SESSION[$guid]["gibboni18nIDPersonal"]=$gibboni18nIDPersonal ;
$_SESSION[$guid]["receiveNotificationEmails"]=$receiveNotificationEmails ;

//Update language settings in session (to personal preference if set, or system default if not)
if (!is_null($gibboni18nIDPersonal)) {
	try {
		$data=array("gibboni18nID"=>$gibboni18nIDPersonal); 
		$sql="SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 	}
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		setLanguageSession($guid, $row) ;
	}
}
else {
	try {
		$data=array(); 
		$sql="SELECT * FROM gibboni18n WHERE systemDefault='Y'" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		setLanguageSession($guid, $row) ;
	}
}


$_SESSION[$guid]["pageLoads"]=NULL ;
$URL.="&editReturn=success0" ;
header("Location: {$URL}");
?>