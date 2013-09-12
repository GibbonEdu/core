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

//Mode may be "print" (return table of messages), "count" (return message count) or "result" (return database query result) 
function getMessages($guid, $connection2, $mode="", $date="") {
	$return="" ;
	$dataPosts=array() ;
	
	if ($date=="") {
		$date=date("Y-m-d") ;
	}
	if ($mode!="print" AND $mode!="count" AND $mode!="result") {
		$mode="print" ;
	}
	
	//Work out all role categories this user has, ignoring "Other"
	$roles=$_SESSION[$guid]["gibbonRoleIDAll"] ;
	$roleCategory="" ;
	$staff=FALSE ;
	$student=FALSE ;
	$parent=FALSE ;
	for ($i=0; $i<count($roles); $i++) {
		$roleCategory=getRoleCategory($roles[$i][0], $connection2) ;
		if ($roleCategory=="Staff") {
			$staff=TRUE ;
		}
		else if ($roleCategory=="Student") {
			$student=TRUE ;
		}
		else if ($roleCategory=="Parent") {
			$parent=TRUE ;
		}
	}
	
	//If parent get a list of student IDs
	if ($parent) {
		$children="(" ;
		try {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		while ($row=$result->fetch()) {
			try {
				$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { }
			while ($rowChild=$resultChild->fetch()) {
				$children.="gibbonPersonID=" . $rowChild["gibbonPersonID"] . " OR " ;
			}
		}
		if ($children!="(") {
			$children=substr($children,0,-4) . ")" ;
		}
		else {
			$children=FALSE ;
		}
	}
	
	
	//My roles
	$roles=$_SESSION[$guid]["gibbonRoleIDAll"] ;
	$sqlWhere="(" ;
	if (count($roles)>0) {
		for ($i=0; $i<count($roles); $i++) {
			$dataPosts["role" . $roles[$i][0]]=$roles[$i][0] ;
			$sqlWhere.="id=:role" . $roles[$i][0] . " OR " ;
		}
		$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
	}
	if ($sqlWhere!="(") {
		$sqlPosts="(SELECT gibbonMessenger.*, title, surname, preferredName, authorRole.category AS category, image_75, concat('Role: ', gibbonRole.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole AS authorRole ON (gibbonPerson.gibbonRoleIDPrimary=authorRole.gibbonRoleID) JOIN gibbonRole ON (gibbonMessengerTarget.id=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Role' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere)" ;
	}
	
	//My year groups
	if ($staff) {
		$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, 'Year Groups' AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Year Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND staff='Y')" ;
	}
	if ($student) {
		$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Year Group ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonStudentEnrolment.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND gibbonMessengerTarget.type='Year Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND gibbonStudentEnrolment.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " AND students='Y')" ;
	}
	if ($parent AND $children!=FALSE) {
		$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Year Group: ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE " . preg_replace("/gibbonPersonID/", "gibbonStudentEnrolment.gibbonPersonID", $children) . " AND gibbonMessengerTarget.type='Year Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND gibbonStudentEnrolment.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " AND parents='Y')" ;
	}
	
	//My roll groups
	if ($staff) {
		$sqlWhere="(" ;
		try {
			$dataRollGroup=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonIDTutor"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor3"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlRollGroup="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)" ;
			$resultRollGroup=$connection2->prepare($sqlRollGroup);
			$resultRollGroup->execute($dataRollGroup);
		}
		catch(PDOException $e) { }
		if ($resultRollGroup->rowCount()>0) {
			while ($rowRollGroup=$resultRollGroup->fetch()) {
				$dataPosts["roll" . $rowRollGroup["gibbonRollGroupID"]]=$rowRollGroup["gibbonRollGroupID"] ;
				$sqlWhere.="id=:roll" . $rowRollGroup["gibbonRollGroupID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonRollGroup ON (gibbonMessengerTarget.id=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND staff='Y')" ;
			}
		}
	}
	if ($student) {
		$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonRollGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND gibbonStudentEnrolment.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " AND gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND students='Y')" ;
	}
	if ($parent AND $children!=FALSE) {
		$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonRollGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE " . preg_replace("/gibbonPersonID/", "gibbonStudentEnrolment.gibbonPersonID", $children) . " AND gibbonStudentEnrolment.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " AND gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND parents='Y')" ;
	}
	
	//My courses
	//First check for any course, then do specific parent check
	try {
		$dataClasses=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlClasses="SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'" ;
		$resultClasses=$connection2->prepare($sqlClasses);
		$resultClasses->execute($dataClasses);
	}
	catch(PDOException $e) { }
	$sqlWhere="(" ;
	if ($resultClasses->rowCount()>0) {
		while ($rowClasses=$resultClasses->fetch()) {
			$dataPosts["course" . $rowClasses["gibbonCourseID"]]=$rowClasses["gibbonCourseID"] ;
			$sqlWhere.="id=:course" . $rowClasses["gibbonCourseID"] . " OR " ;
		}
		$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
		if ($sqlWhere!="(") {
			if ($staff) {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND staff='Y')" ;
			}
			if ($student) {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND students='Y')" ;
			}
		}
	}
	if ($parent AND $children!=FALSE) {
				
		try {
			$dataClasses=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlClasses="SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND " . preg_replace("/gibbonPersonID/", "gibbonCourseClassPerson.gibbonPersonID", $children) . " AND NOT role LIKE '%- Left'" ;
			$resultClasses=$connection2->prepare($sqlClasses);
			$resultClasses->execute($dataClasses);
		}
		catch(PDOException $e) { }
		$sqlWhere="(" ;
		if ($resultClasses->rowCount()>0) {
			while ($rowClasses=$resultClasses->fetch()) {
				$dataPosts["course" . $rowClasses["gibbonCourseID"]]=$rowClasses["gibbonCourseID"] ;
				$sqlWhere.="id=:course" . $rowClasses["gibbonCourseID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND parents='Y')" ;
			}
		}
	}
	
	
	//My classes
	//First check for any role, then do specific parent check
	try {
		$dataClasses=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlClasses="SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'" ;
		$resultClasses=$connection2->prepare($sqlClasses);
		$resultClasses->execute($dataClasses);
	}
	catch(PDOException $e) { }
	$sqlWhere="(" ;
	if ($resultClasses->rowCount()>0) {
		while ($rowClasses=$resultClasses->fetch()) {
			$dataPosts["class" . $rowClasses["gibbonCourseClassID"]]=$rowClasses["gibbonCourseClassID"] ;
			$sqlWhere.="id=:class" . $rowClasses["gibbonCourseClassID"] . " OR " ;
		}
		$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
		if ($sqlWhere!="(") {
			if ($staff) {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND staff='Y')" ;
			}
			if ($student) {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND students='Y')" ;
			}
		}
	}
	if ($parent AND $children!=FALSE) {
		try {
			$dataClasses=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlClasses="SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND " . preg_replace("/gibbonPersonID/", "gibbonCourseClassPerson.gibbonPersonID", $children) . " AND NOT role LIKE '%- Left'" ;
			$resultClasses=$connection2->prepare($sqlClasses);
			$resultClasses->execute($dataClasses);
		}
		catch(PDOException $e) { }
		$sqlWhere="(" ;
		if ($resultClasses->rowCount()>0) {
			while ($rowClasses=$resultClasses->fetch()) {
				$dataPosts["class" . $rowClasses["gibbonCourseClassID"]]=$rowClasses["gibbonCourseClassID"] ;
				$sqlWhere.="id=:class" . $rowClasses["gibbonCourseClassID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				 $sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND parents='Y')" ;
			}
		}
	}
	
	//My activities
	if ($staff) {
		try {
			$dataActivities=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlActivities="SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID" ;
			$resultActivities=$connection2->prepare($sqlActivities);
			$resultActivities->execute($dataActivities);
		}
		catch(PDOException $e) { }
		$sqlWhere="(" ;
		if ($resultActivities->rowCount()>0) {
			while ($rowActivities=$resultActivities->fetch()) {
				$dataPosts["activity" . $rowActivities["gibbonActivityID"]]=$rowActivities["gibbonActivityID"] ;
				$sqlWhere.="id=:activity" . $rowActivities["gibbonActivityID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND staff='Y')" ;
			}
		}
	}
	if ($student) {
		try {
			$dataActivities=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlActivities="SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND status='Accepted'" ;
			$resultActivities=$connection2->prepare($sqlActivities);
			$resultActivities->execute($dataActivities);
		}
		catch(PDOException $e) { }
		$sqlWhere="(" ;
		if ($resultActivities->rowCount()>0) {
			while ($rowActivities=$resultActivities->fetch()) {
				$dataPosts["activity" . $rowActivities["gibbonActivityID"]]=$rowActivities["gibbonActivityID"] ;
				$sqlWhere.="id=:activity" . $rowActivities["gibbonActivityID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND students='Y')" ;
			}
		}
	}
	if ($parent AND $children!=FALSE) {
		try {
			$dataActivities=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlActivities="SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND " . preg_replace("/gibbonPersonID/", "gibbonActivityStudent.gibbonPersonID", $children) . " AND status='Accepted'" ;
			$resultActivities=$connection2->prepare($sqlActivities);
			$resultActivities->execute($dataActivities);
		}
		catch(PDOException $e) { }
		$sqlWhere="(" ;
		if ($resultActivities->rowCount()>0) {
			while ($rowActivities=$resultActivities->fetch()) {
				$dataPosts["activity" . $rowActivities["gibbonActivityID"]]=$rowActivities["gibbonActivityID"] ;
				$sqlWhere.="id=:activity" . $rowActivities["gibbonActivityID"] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-3) . ")" ;
			if ($sqlWhere!="(") {
				$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_75, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND $sqlWhere AND parents='Y')" ;
			}
		}
	}
	
	//Houses
	$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_75, concat('Houses: ', gibbonHouse.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS inHouse ON (gibbonMessengerTarget.id=inHouse.gibbonHouseID) JOIN gibbonHouse ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID)WHERE gibbonMessengerTarget.type='Houses' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND inHouse.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . ")" ;
	
	//Individuals
	$sqlPosts=$sqlPosts . " UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_75, 'Individual: You' AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS individual ON (gibbonMessengerTarget.id=individual.gibbonPersonID) WHERE gibbonMessengerTarget.type='Individuals' AND (messageWall_date1='$date' OR messageWall_date2='$date' OR messageWall_date3='$date') AND individual.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . ")" ;
	
	
	//SPIT OUT RESULTS
	if ($mode=="result") {
		$resultReturn=array() ;
		$resultReturn[0]=$dataPosts ;
		$resultReturn[1]=$sqlPosts . " ORDER BY subject, gibbonMessengerID, source" ; 
		return serialize($resultReturn) ;
	}	
	else {
		$count=0 ;
		try {
			$sqlPosts=$sqlPosts . " ORDER BY subject, gibbonMessengerID, source" ;
			$resultPosts=$connection2->prepare($sqlPosts);
			$resultPosts->execute($dataPosts);  
		}
		catch(PDOException $e) { }	
		if ($resultPosts->rowCount()<1) {
			$return=$return. "<div class='warning'>" ;
				if ($date==date("Y-m-d")) {
					$return=$return. "There are no messages for you today." ;
				}
				else {
					$return=$return. "There are no messages for you on the specified date." ;
				}
			$return=$return. "</div>" ;
		}
		else {
			$output=array() ;
			$last="" ;
			while ($rowPosts=$resultPosts->fetch()) {
				if ($last==$rowPosts["gibbonMessengerID"]) {
					$output[($count-1)]["source"]=$output[($count-1)]["source"] . "<br/>" .$rowPosts["source"] ;
				}
				else {
					$output[$count]["photo"]=$rowPosts["image_75"] ;
					$output[$count]["subject"]=$rowPosts["subject"] ;
					$output[$count]["details"]=$rowPosts["body"] ;
					$output[$count]["author"]=formatName($rowPosts["title"], $rowPosts["preferredName"], $rowPosts["surname"], $rowPosts["category"]) ;
					$output[$count]["source"]=$rowPosts["source"] ;
			
					$count++ ;
					$last=$rowPosts["gibbonMessengerID"] ;
				}	
			}
	
			$return=$return. "<table>" ;
				$rowCount=0;
				$rowNum="odd" ;
				for ($i=0; $i<count($output); $i++) {
			
					if ($rowCount%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$rowCount++ ;
																
					$return=$return. "<tr class=$rowNum>" ;
						$return=$return. "<td style='vertical-align: top; padding-bottom: 10px; padding-top: 10px; border-top: 1px solid #666; width: 100px'>" ;
							$return=$return . getUserPhoto($guid, $output[$i]["photo"], 75) ;
						$return=$return. "</td>" ;
						$return=$return. "<td style='vertical-align: top; padding-bottom: 10px; padding-top: 10px; border-top: 1px solid #666; width: 640px'>" ;
							$return=$return. "<h3 style='margin-top: 0px; border: none'>" ;
								$return=$return. $output[$i]["subject"] ;
							$return=$return. "</h3>" ;
							$return=$return. $output[$i]["details"] ;
						$return=$return. "</td>" ;
						$return=$return. "<td style='vertical-align: top; padding-bottom: 10px; padding-top: 10px; border-top: 1px solid #666; width: 220px'>" ;
							$return=$return. "<p style='margin-top: 12px; text-align: right'>" ;
								$return=$return. "<b><u>Posted By</b></u><br/>" ;
								$return=$return. $output[$i]["author"] . "<br/><br/>" ;
							
								$return=$return. "<b><u>Shared Via</b></u><br/>" ;
								$return=$return. $output[$i]["source"] . "<br/><br/>" ;
							
							$return=$return. "</p>" ;
						$return=$return. "</td>" ;
					$return=$return. "</tr>" ;
				}
			$return=$return. "</table>" ;
		}
		if ($mode=="print") {
			return $return ;
		}
		else {
			return $count ;
		}
	}
}

?>
