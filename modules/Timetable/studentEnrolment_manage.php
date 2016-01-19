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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable/studentEnrolment_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Student Enrolment') . "</div>" ;
	print "</div>" ;
	
	print "<p>" ;
	print _('This page allows departmental Coordinators and Assistant Coordinators to manage student enolment within their department.') ;
	print "</p>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT gibbonCourse.* FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort" ; 	
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		while ($row=$result->fetch()) {
			print "<h3>" ;
				print $row["nameShort"] . " (" . $row["name"] . ")" ;
			print "</h3>" ;
			
			try {
				$dataClass=array("gibbonCourseID"=>$row["gibbonCourseID"]); 
				$sqlClass="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name" ; 
				$resultClass=$connection2->prepare($sqlClass);
				$resultClass->execute($dataClass);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultClass->rowCount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Short Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Participants") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Active') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print "Participants<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Expected') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print "Participants<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Total') . "</span>" ;
						print "</th>" ;
						print "<th style='width: 55px'>" ;
							print _("Actions") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($rowClass=$resultClass->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $rowClass["name"] ;
							print "</td>" ;
							print "<td>" ;
								print $rowClass["nameShort"] ;
							print "</td>" ;
							print "<td>" ;
								$total=0 ;
								$active=0 ;
								$expected=0 ;
								try {
									$dataClasses=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"]); 
									$sqlClasses="SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')" ;
									$resultClasses=$connection2->prepare($sqlClasses);
									$resultClasses->execute($dataClasses);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClasses->rowCount()>=0) {
									$active=$resultClasses->rowCount() ;
								}
								
								try {
									$dataClasses=array("gibbonCourseClassID"=>$rowClass["gibbonCourseClassID"]); 
									$sqlClasses="SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Expected' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')" ;
									$resultClasses=$connection2->prepare($sqlClasses);
									$resultClasses->execute($dataClasses);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClasses->rowCount()>=0) {
									$expected=$resultClasses->rowCount() ;
								}
								print $active ;
							print "</td>" ;
							print "<td>" ;
								print $expected ;
							print "</td>" ;
							print "<td>" ;
								print "<b>" . ($active+$expected) . "<b/> " ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_edit.php&gibbonCourseClassID=" . $rowClass["gibbonCourseClassID"] . "&gibbonCourseID=" . $row["gibbonCourseID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
						
						$count++ ;
					}
				print "</table>" ;
			}
		}
	}
}
?>