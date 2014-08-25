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

function getLessons($guid, $connection2, $and="" ) {
	$today=date("Y-m-d") ;
	$now=date("Y-m-d H:i:s") ;
	
	$fields="gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDetails, date, gibbonPlannerEntry.gibbonCourseClassID, homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead" ;
	//Get my classes (student, teacher, classmates)
	$data=array("today1"=>$today, "gibbonPersonID1"=>$_SESSION[$guid]["gibbonPersonID"], "now1"=>$now, "gibbonSchoolYearID1"=>$_SESSION[$guid]["gibbonSchoolYearID"]) ;
	$sql="(SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND (role='Teacher' OR role='Student') AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now1 AND gibbonSchoolYearID=:gibbonSchoolYearID1 $and)" ; 
	
	//Get other classes if teacher
	try {
		$dataTeacher=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlTeacher="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID AND type='Teaching'" ;
		$resultTeacher=$connection2->prepare($sqlTeacher);
		$resultTeacher->execute($dataTeacher);
	}
	catch(PDOException $e) { }
	if ($resultTeacher->rowCount()==1) {
		$data["today2"]=$today ;
		$data["gibbonSchoolYearID2"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$data["now2"]=$now;
		$sql=$sql . " UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today2 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now2 AND gibbonSchoolYearID=:gibbonSchoolYearID2 AND homeworkCrowdAssessOtherTeachersRead='Y' $and)" ; 	
	}
	
	//Get other classes if student
	try {
		$dataStudent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlStudent="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$resultStudent=$connection2->prepare($sqlStudent);
		$resultStudent->execute($dataStudent);
	}
	catch(PDOException $e) { }
	if ($resultStudent->rowCount()==1) {
		$data["today3"]=$today ;
		$data["gibbonSchoolYearID3"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$data["now3"]=$now ;
		$sql=$sql . " UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today3 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now3 AND gibbonSchoolYearID=:gibbonSchoolYearID3 AND homeworkCrowdAssessOtherStudentsRead='Y' $and)" ; 	
	}
	
	//Get classes if parent
	try {
		$dataParent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlParent="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
		$resultParent=$connection2->prepare($sqlParent);
		$resultParent->execute($dataParent);
	}
	catch(PDOException $e) { }
	
	if ($resultParent->rowCount()>0) {
		//Get child list for family
		$childCount=0 ;
		while ($rowParent=$resultParent->fetch()) {
			try {
				$dataChild=array("gibbonFamilyID"=>$rowParent["gibbonFamilyID"]); 
				$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_75, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { }
			while ($rowChild=$resultChild->fetch()) {
				//submitters+classmates parents
				$data["today4" . $childCount]=$today ;
				$data["gibbonSchoolYearID4" . $childCount]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$data["now4" . $childCount]=$now ;
				$data["gibbonPersonID4" . $childCount]=$rowChild["gibbonPersonID"] ;
				$sql=$sql . " UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today4$childCount AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID4$childCount AND role='Student' AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now4$childCount AND gibbonSchoolYearID=:gibbonSchoolYearID4$childCount AND (homeworkCrowdAssessSubmitterParentsRead='Y' OR homeworkCrowdAssessClassmatesParentsRead='Y') $and)" ; 
				$childCount++ ;
			}
		}
		//Other classes
		$data["today5"]=$today ;
		$data["gibbonSchoolYearID5"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$data["now5"]=$now ;
		$sql=$sql . " UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today5 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now5 AND gibbonSchoolYearID=:gibbonSchoolYearID5 AND homeworkCrowdAssessOtherParentsRead='Y' $and)" ; 	
	}
	
	return array($data, $sql) ;
}

function getCARole($guid, $connection2, $gibbonCourseClassID) {
	$role="" ;
	//Determine roll
	$highestAction=getHighestGroupedAction($guid, "/modules/Students/student_view.php", $connection2) ;
	if ($highestAction=="View Student Profile_myChildren") {
		$role="Parent" ;
		$childInClass=FALSE ;
		
		//Is child of this perosn in this class?
		$count=0 ;
		$children=array() ;
		
		try {
			$dataParent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"] ); 
			$sqlParent="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
			$resultParent=$connection2->prepare($sqlParent);
			$resultParent->execute($dataParent);
		}
		catch(PDOException $e) { }

		if ($resultParent->rowCount()>0) {
			//Get child list for family
			while ($rowParent=$resultParent->fetch()) {
				try {
					$dataChild=array("gibbonFamilyID"=>$rowParent["gibbonFamilyID"]); 
					$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_75, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
					$resultChild=$connection2->prepare($sqlChild);
					$resultChild->execute($dataChild);
				}
				catch(PDOException $e) { }
				while ($rowChild=$resultChild->fetch()) {
					try {
						$dataInClass=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$rowChild["gibbonPersonID"]); 
						$sqlInClass="SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Student'" ;
						$resultInClass=$connection2->prepare($sqlInClass);
						$resultInClass->execute($dataInClass);
					}
					catch(PDOException $e) { }
					if ($resultInClass->rowCount()==1) {
						$childInClass=TRUE ;
						$rowInClass=$resultInClass->fetch() ;
						$children[$count]=$rowInClass["gibbonPersonID"] ;
						$count++ ;
					}
				}
			}
		}
		if ($childInClass==TRUE) {
			$role="Parent - Child In Class" ;
		}
	}
	else {
		//Check if in staff table as teacher
		try {
			$dataTeacher=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlTeacher="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID AND type='Teaching'" ;
			$resultTeacher=$connection2->prepare($sqlTeacher);
			$resultTeacher->execute($dataTeacher);
		}
		catch(PDOException $e) { }

		if ($resultTeacher->rowCount()==1) {
			$role="Teacher" ;
			try {
				$dataRole=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlRole="SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'" ;
				$resultRole=$connection2->prepare($sqlRole);
				$resultRole->execute($dataRole);
			}
			catch(PDOException $e) { }
			if ($resultRole->rowCount()==1) {
				$role="Teacher - In Class" ;
			}
		}
		
		//Check if student
		try {
			$dataStudent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlStudent="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$resultStudent=$connection2->prepare($sqlStudent);
			$resultStudent->execute($dataStudent);
		}
		catch(PDOException $e) { }

		if ($resultStudent->rowCount()==1) {
			$role="Student" ;
			try {
				$dataRole=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlRole="SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Student'" ;
				$resultRole=$connection2->prepare($sqlRole);
				$resultRole->execute($dataRole);
			}
			catch(PDOException $e) { }
			if ($resultRole->rowCount()==1) {
				$role="Student - In Class" ;
			}
		}
	}
	return $role ;
}

function getStudents($guid, $connection2, $role, $gibbonCourseClassID, $homeworkCrowdAssessOtherTeachersRead, $homeworkCrowdAssessOtherParentsRead, $homeworkCrowdAssessSubmitterParentsRead, $homeworkCrowdAssessClassmatesParentsRead, $homeworkCrowdAssessOtherStudentsRead, $homeworkCrowdAssessClassmatesRead, $and="") {
	
	//Fetch and display assessible submissions
	$sqlList="" ;
	if (($role=="Teacher" AND $homeworkCrowdAssessOtherTeachersRead=="Y") OR ($role=="Teacher - In Class")) {
		//Get All students in class
		$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
		$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $and ORDER BY surname, preferredName" ;
	}
	else if ($role=="Parent" AND $homeworkCrowdAssessOtherParentsRead=="Y") {
		//Get all students in class
		$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
		$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $and ORDER BY surname, preferredName" ;
	}
	else if ($role=="Parent - Child In Class") {
		//Get array of children
		$count=0 ;
		$children=array() ;
		try {
			$dataParent=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sqlParent="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
			$resultParent=$connection2->prepare($sqlParent);
			$resultParent->execute($dataParent);
		}
		catch(PDOException $e) { }
		if ($resultParent->rowCount()>0) {
			//Get child list for family
			$childCount=0 ;
			while ($rowParent=$resultParent->fetch()) {
				try {
					$dataChild=array("gibbonFamilyID"=>$rowParent["gibbonFamilyID"]); 
					$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_75, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
					$resultChild=$connection2->prepare($sqlChild);
					$resultChild->execute($dataChild);
				}
				catch(PDOException $e) { }
				while ($rowChild=$resultChild->fetch()) {
					$children[$count]=$rowChild["gibbonPersonID"] ;
					$count++ ;
				}
			}
		}
	
		if ($homeworkCrowdAssessSubmitterParentsRead=="Y" AND $homeworkCrowdAssessClassmatesParentsRead=="Y") {
			//Get all students in class
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
			$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $and ORDER BY surname, preferredName" ;
		}
		else if ($homeworkCrowdAssessSubmitterParentsRead=="Y") {
			//Get only parent's children
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
			$sqlListWhere="AND (" ;
			for ($i=0; $i<$count; $i++) {
				$data[$children[$i]]=$children[$i] ;
				$sqlListWhere.="gibbonCourseClassPerson.gibbonPersonID=:" . $children[$i] . " OR " ;
			}
			if ($sqlListWhere=="AND (") {
				$sqlListWhere="" ;
			}
			else {
				$sqlListWhere=substr($sqlListWhere, 0, -4) . ")" ;
			}
			$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $sqlListWhere $and ORDER BY surname, preferredName" ;
		}
		else if ($homeworkCrowdAssessClassmatesParentsRead=="Y") {
			//Get all children except parent's children
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
			$sqlListWhere="" ;
			for ($i=0; $i<$count; $i++) {
				$data[$children[$i]]=$children[$i] ;
				$sqlListWhere.=" AND NOT gibbonCourseClassPerson.gibbonPersonID=:" . $children[$i] ;
			}
			$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $sqlListWhere $and ORDER BY surname, preferredName" ;
		}
	}
	else if (($role=="Student" AND $homeworkCrowdAssessOtherStudentsRead=="Y") OR ($role=="Student - In Class" AND $homeworkCrowdAssessClassmatesRead=="Y")) {
		$data=array("gibbonCourseClassID"=>$gibbonCourseClassID) ;
		$sqlList="SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') $and ORDER BY surname, preferredName" ;
	}
	
	return array($data, $sqlList) ;
}

function getThread($guid, $connection2, $gibbonPlannerEntryHomeworkID, $parent, $level, $self, $gibbonPersonID, $gibbonPlannerEntryID) {
	$output="" ;
	
	try {
		if ($parent==NULL) {
			$dataDiscuss=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID); 
			$sqlDiscuss="SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID AND gibbonCrowdAssessDiscussIDReplyTo IS NULL ORDER BY timestamp" ;
		}
		else {
			$dataDiscuss=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "parent"=>$parent, "self"=>$self); 
			$sqlDiscuss="SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID AND gibbonCrowdAssessDiscussIDReplyTo=:parent AND gibbonCrowdAssessDiscussID=:self ORDER BY timestamp" ;
		}
		$resultDiscuss=$connection2->prepare($sqlDiscuss);
		$resultDiscuss->execute($dataDiscuss);
	}
	catch(PDOException $e) { 
		$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($level==0 AND $resultDiscuss->rowCount()==0) {
		$output.="<div class='error'>" ;
			$output.=_("This conversation has not yet begun!") ;
		$output.="</div>" ;
	}
	else {
		 while ($rowDiscuss=$resultDiscuss->fetch()) {
			if ($level==0) {
				$border="2px solid #333" ;
				$margintop="25px" ; 
			}
			else {
				$border="2px solid #333" ;
				$margintop="0px" ;
			}
			$output.="<a name='" . $rowDiscuss["gibbonCrowdAssessDiscussID"] . "'></a>" ; 
			$output.="<table class='noIntBorder' cellspacing='0' style='width: " . (760-($level*15)) . "px ; padding: 1px 3px; margin-bottom: -2px; margin-top: $margintop; margin-left: " . ($level*15) . "px; border: $border ; background-color: #f9f9f9'>" ;
				$output.="<tr>" ;
					$output.="<td style='color: #777'><i>". formatName($rowDiscuss["title"], $rowDiscuss["preferredName"], $rowDiscuss["surname"], $rowDiscuss["category"]) ." said</i>:</td>" ;
					$output.="<td style='color: #777; text-align: right'><i>Posted at <b>" . substr($rowDiscuss["timestamp"],11,5) . "</b> on <b>" . dateConvertBack($guid, substr($rowDiscuss["timestamp"],0,10)) . "</b></i></td>" ;
				$output.="</tr>" ;
				$output.="<tr>" ;
					$borderleft="4px solid #1B9F13" ;
					if ($rowDiscuss["timestamp"]>=$_SESSION[$guid]["lastTimestamp"]) {
						$borderleft="4px solid #c00" ;
					}
					$output.="<td style='padding: 1px 4px; border-left: $borderleft' colspan=2><b>" . $rowDiscuss["comment"] . "</b></td>" ;
				$output.="</tr>" ;
				$output.="<tr>" ;
					$output.="<td style='text-align: right' colspan=2><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/crowdAssess_view_discuss_post.php&gibbonPersonID=$gibbonPersonID&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&replyTo=" . $rowDiscuss["gibbonCrowdAssessDiscussID"] . "'>Reply</a></td>" ;
				$output.="</tr>" ;
				
				
			$output.="</table>" ; 
			
			//Get any replies
			try {
				$dataReplies=array("gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID, "gibbonCrowdAssessDiscussID"=>$rowDiscuss["gibbonCrowdAssessDiscussID"]); 
				$sqlReplies="SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID AND gibbonCrowdAssessDiscussIDReplyTo=:gibbonCrowdAssessDiscussID ORDER BY timestamp" ;
				$resultReplies=$connection2->prepare($sqlReplies);
				$resultReplies->execute($dataReplies);
			}
			catch(PDOException $e) { 
				$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowReplies=$resultReplies->fetch()) {
				$output.=getThread($guid, $connection2, $gibbonPlannerEntryHomeworkID, $rowDiscuss["gibbonCrowdAssessDiscussID"], ($level+1), $rowReplies["gibbonCrowdAssessDiscussID"], $gibbonPersonID, $gibbonPlannerEntryID) ;
			}
		}
	}
	
	return $output ;
}
?>
