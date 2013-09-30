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

print "<div class='trail'>" ;
print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > </div><div class='trailEnd'>Stars</div>" ;
print "</div>" ;
print "<p>" ;
print "This page shows you a break down of how your stars have been earned, as well as where your most recent stars in each category have come from." ;
print "</p>" ;

//Count planner likes
try {
	$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
	$sqlLike="SELECT timestamp, surname, preferredName, gibbonRoleIDPrimary, image_75, gibbonRoleIDPrimary, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonPlannerEntryLike JOIN gibbonPlannerEntry ON (gibbonPlannerEntryLike.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=.gibbonPlannerEntryLike.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC" ;
	$resultLike=$connection2->prepare($sqlLike);
	$resultLike->execute($dataLike);
}
catch(PDOException $e) { 
	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
}
if ($resultLike->rowCount()>0) {
	print "<h2>" ;
	print "Planner Stars <span style='font-size: 65%; font-style: italic; font-weight: normal'> x" . $resultLike->rowCount() . "</span>" ;
	print "</h2>" ;
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 90px'>" ;
				print "Photo" ;
			print "</th>" ;
			print "<th style='width: 180px'>" ;
				print "Name" ;
			print "</th>" ;
			print "<th>" ;
				print "Class/Lesson" ;
			print "</th>" ;
			print "<th style='width: 70px'>" ;
				print "Date" ;
			print "</th>" ;
		print "</tr>" ;
		
		$count=0;
		$rowNum="odd" ;
		while ($row=$resultLike->fetch() AND $count<20) {
			if ($count%2==0) {
				$rowNum="even" ;
			}
			else {
				$rowNum="odd" ;
			}
			$count++ ;
			
			//COLOR ROW BY STATUS!
			print "<tr class=$rowNum>" ;
				print "<td>" ;
					printUserPhoto($guid, $row["image_75"], 75) ;
				print "</td>" ;
				print "<td>" ;
					$roleCategory=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
					if ($roleCategory=="Student" AND isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php")) {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], $roleCategory, false) . "</a><br/>" ;
						print "<i>$roleCategory</i>" ;
					}
					else {
						print "<i>$roleCategory</i>" ;
					}
				print "</td>" ;
				print "<td>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&viewBy=class&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
					print $row["name"] ;
				print "</td>" ;
				print "<td>" ;
					print dateConvertBack(substr($row["timestamp"],0,10)) ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}

//Count positive haviour
try {
	$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
	$sqlLike="SELECT descriptor, comment, timestamp, surname, preferredName, gibbonRoleIDPrimary, image_75, gibbonRoleIDPrimary FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonBehaviour.gibbonPersonIDCreator) WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Positive' AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC" ;
	$resultLike=$connection2->prepare($sqlLike);
	$resultLike->execute($dataLike);
}
catch(PDOException $e) { 
	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
}
if ($resultLike->rowCount()>0) {
	print "<h2>" ;
	print "Behaviour Stars <span style='font-size: 65%; font-style: italic; font-weight: normal'> x" . $resultLike->rowCount() . "</span>" ;
	print "</h2>" ;
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 90px'>" ;
				print "Photo" ;
			print "</th>" ;
			print "<th style='width: 180px'>" ;
				print "Name" ;
			print "</th>" ;
			print "<th>" ;
				print "Details" ;
			print "</th>" ;
			print "<th style='width: 70px'>" ;
				print "Date" ;
			print "</th>" ;
		print "</tr>" ;
		
		$count=0;
		$rowNum="odd" ;
		while ($row=$resultLike->fetch() AND $count<10) {
			if ($count%2==0) {
				$rowNum="even" ;
			}
			else {
				$rowNum="odd" ;
			}
			$count++ ;
			
			//COLOR ROW BY STATUS!
			print "<tr class=$rowNum>" ;
				print "<td>" ;
					printUserPhoto($guid, $row["image_75"], 75) ;
				print "</td>" ;
				print "<td>" ;
					$roleCategory=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
					print formatName("", $row["preferredName"], $row["surname"], $roleCategory, false) . "<br/>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>" . $row["descriptor"] . "</b><br/>" ;
					if ($row["comment"]!="") {
						print "<i>\"" . $row["comment"] . "\"</i>" ;
					}
				print "</td>" ;
				print "<td>" ;
					print dateConvertBack(substr($row["timestamp"],0,10)) ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}


//Count crowd assessment likes
try {
	$dataLike=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
	$sqlLike="SELECT gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRoleIDPrimary, image_75, gibbonPlannerEntry.name, gibbonCrowdAssessLike.timestamp FROM gibbonCrowdAssessLike JOIN gibbonPlannerEntryHomework ON (gibbonCrowdAssessLike.gibbonPlannerEntryHomeworkID=gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonPerson ON (gibbonCrowdAssessLike.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC" ;
	$resultLike=$connection2->prepare($sqlLike);
	$resultLike->execute($dataLike);
}
catch(PDOException $e) { 
	print "<div class='error'>" . $e->getMessage() . "</div>" ; 
}
if ($resultLike->rowCount()>0) {
	print "<h2>" ;
	print "Crowd Assessment Stars <span style='font-size: 65%; font-style: italic; font-weight: normal'> x" . $resultLike->rowCount() . "</span>" ;
	print "</h2>" ;
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 90px'>" ;
				print "Photo" ;
			print "</th>" ;
			print "<th style='width: 180px'>" ;
				print "Name" ;
			print "</th>" ;
			print "<th>" ;
				print "Lesson" ;
			print "</th>" ;
			print "<th style='width: 70px'>" ;
				print "Date" ;
			print "</th>" ;
		print "</tr>" ;
		
		$count=0;
		$rowNum="odd" ;
		while ($row=$resultLike->fetch() AND $count<10) {
			if ($count%2==0) {
				$rowNum="even" ;
			}
			else {
				$rowNum="odd" ;
			}
			$count++ ;
			
			//COLOR ROW BY STATUS!
			print "<tr class=$rowNum>" ;
				print "<td>" ;
					printUserPhoto($guid, $row["image_75"], 75) ;
				print "</td>" ;
				print "<td>" ;
					$roleCategory=getRoleCategory($row["gibbonRoleIDPrimary"], $connection2) ;
					if ($roleCategory=="Student" AND isActionAccessible($guid, $connection2, "/modules/Students/student_view_details.php")) {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], $roleCategory, false) . "</a><br/>" ;
						print "<i>$roleCategory</i>" ;
					}
					else {
						
						print "<i>$roleCategory</i>" ;
					}
				print "</td>" ;
				print "<td>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&gibbonPlannerEntryHomeworkID=" . $row["gibbonPlannerEntryHomeworkID"] . "&gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . "'>" . $row["name"] . "</a>" ;
				print "</td>" ;
				print "<td>" ;
					print dateConvertBack(substr($row["timestamp"],0,10)) ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}




?>



