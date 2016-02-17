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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

$makeDepartmentsPublic=getSettingByScope($connection2, "Departments", "makeDepartmentsPublic") ;
if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course_class.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=NULL ;
	if (isset($_GET["gibbonCourseID"])) {
		$gibbonCourseID=$_GET["gibbonCourseID"] ;
	}
	$gibbonDepartmentID=NULL ;
	if (isset($_GET["gibbonDepartmentID"])) {
		$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
	}
	if ($gibbonCourseClassID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		$proceed=false; 
		if ($gibbonDepartmentID!="") {
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.gibbonSchoolYearID,gibbonDepartment.name AS department, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The specified record does not exist.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$proceed=true ;
			}
		}
		else {
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.gibbonSchoolYearID, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The specified record does not exist.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$proceed=true ;
			}
		}
		
		if ($proceed==true) {
			//Get role within learning area
			$role=NULL ;
			if ($gibbonDepartmentID!="" AND isset($_SESSION[$guid]["username"])) {
				$role=getRole($_SESSION[$guid]["gibbonPersonID"], $gibbonDepartmentID, $connection2 ) ;
			}
			
			$extra="" ;
			if (($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Teacher") AND $row["gibbonSchoolYearID"]!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
				$extra=" " . $row["year"];
			}
			print "<div class='trail'>" ;
			if ($gibbonDepartmentID!="") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/departments.php'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>" . _('View All') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "'>" . $row["department"] . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_course.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . $row["courseLong"] . "$extra</a> ></div><div class='trailEnd'>" . $row["course"] . "." . $row["class"] . "</div>" ;
			}
			else {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/departments.php'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>" . _('View All') . "</a> > Class ></div><div class='trailEnd'>" . $row["course"] . "." . $row["class"] . "</div>" ;
			}
			print "</div>" ;
			
			$subpage=NULL ;
			if (isset($_GET["subpage"])) {
				$subpage=$_GET["subpage"] ;
			}
			if ($subpage=="" OR isset($_SESSION[$guid]["username"])==FALSE) {
				$subpage=_("Home") ;
			}
			
			print "<h2>" ;
				print $row["course"] . "." . $row["class"] . " " . _($subpage) ;
			print "</h2>" ;
			
			if ($subpage=="Home") {
				//CHECK & STORE WHAT TO DISPLAY
				$menu=array() ;
				$menuCount=0 ;
				
				//Participants
				$menu[$menuCount][0]="Participants" ;
				$menu[$menuCount][1]="<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&subpage=Participants'><img style='margin-bottom: 10px' title='" . _('Participants') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance_large.png'/><br/><b>" . _('Participants') . "</b></a>" ;
				$menuCount++ ;
				
				//Planner
				if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
					$menu[$menuCount][0]="Planner" ;
					$menu[$menuCount][1]="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=$gibbonCourseClassID&viewBy=class'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='" . _('Planner') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner_large.png'/><br/><b>" . _('Planner') . "</b></a>" ;
					$menuCount++ ;
					
				}
				//Markbook
				if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
					$menu[$menuCount][0]="Markbook" ;
					$menu[$menuCount][1]="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='" . _('Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook_large.png'/><br/><b>" . _('Markbook') . "</b></a>" ;
					$menuCount++ ;
				}
				
				//Homework
				if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_deadlines.php")) {
					$menu[$menuCount][0]="Homework" ;
					$menu[$menuCount][1]="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='" . _('Markbook') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/homework_large.png'/><br/><b>" . _('Homework') . "</b></a>" ;
					$menuCount++ ;
				}
				
				//Internal Assessment
				if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_write.php")) {
					$menu[$menuCount][0]="Internal Assessment" ;
					$menu[$menuCount][1]="<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='" . _('Internal Assessment') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/internalAssessment_large.png'/><br/><b>" . _('Internal Assessment') . "</b></a>" ;
					$menuCount++ ;
				}
				
				if ($menuCount<1) {
					print "<div class='error'>" ;
					print _("There are no records to display.") ;
					print "</div>" ;
				}
				else {
					print "<table class='smallIntBorder' cellspacing='0' style='width:100%'>" ;
						$count=0 ;
						$columns=3 ;

						foreach ($menu AS $menuEntry) {
							if ($count%$columns==0) {
								print "<tr>" ;
							}
							print "<td style='padding-top: 15px!important; padding-bottom: 15px!important; width:30%; text-align: center; vertical-align: top'>" ;
									print $menuEntry[1] ;
							print "</td>" ;

							if ($count%$columns==($columns-1)) {
								print "</tr>" ;
							}
							$count++ ;
						}

						if ($count%$columns!=0) {
							for ($i=0;$i<$columns-($count%$columns);$i++) {
								print "<td></td>" ;
							}
							print "</tr>" ;
						}

					print "</table>" ;	
				
				}
			}
			else if ($subpage=="Participants") {
				print "<div class='linkTop'>" ;
				print "<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&subpage=Home'>" . $row["course"] . "." . $row["class"] . " " . _('Home') . "</b></a>" ;
				if (getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2)=="View Student Profile_full") {
					print " | " ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_course_classExport.php?gibbonCourseClassID=$gibbonCourseClassID&address=" . $_GET["q"] . "'>" . _("Export") . " <img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
				}
				print "</div>" ;
				
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
						print _("The specified record does not exist.") ;
					print "</div>" ;
				}
				else {
					printClassGroupTable($guid, $gibbonCourseClassID, 4, $connection2) ;
				}
			}
				
			//Print sidebar
			if (isset($_SESSION[$guid]["username"])) {
				$_SESSION[$guid]["sidebarExtra"]="" ;
			
				//Print related class list
				try {
					$dataCourse=array("gibbonCourseID"=>$row["gibbonCourseID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlCourse="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY class" ;
					$resultCourse=$connection2->prepare($sqlCourse);
					$resultCourse->execute($dataCourse);
				}
				catch(PDOException $e) { 
					$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
			
				if ($resultCourse->rowCount()>0) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Related Classes") ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
					while ($rowCourse=$resultCourse->fetch()) {
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=" . $row["gibbonCourseID"] . "&gibbonCourseClassID=" . $rowCourse["gibbonCourseClassID"] . "'>" . $rowCourse["course"] . "." . $rowCourse["class"] . "</a></li>" ;
					}
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
				}
			
				//Print list of all classes
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Current Classes") ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<tr>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<td class='right'>" ;
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<select style='width:160px; float: none' name='gibbonCourseClassID'>" ;
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.gibbonCourseID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { 
										$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
								
								
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value=''></option>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
											$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["courseName"]) . "." . htmlPrep($rowSelect["className"]) . "</option>" ;
										}
										else {
											$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["courseName"]) . "." . htmlPrep($rowSelect["className"]) . "</option>" ;
										}
									}
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</select>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</td>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<td class='right'>" ;
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='hidden' name='q' value='/modules/" . $_SESSION[$guid]["module"] . "/department_course_class.php'>" ;
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='submit' value='" . _('Go') . "'>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</td>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</tr>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</table>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</form>" ;
			}
		}
	}
}
?>