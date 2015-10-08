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
if (isActionAccessible($guid, $connection2, "/modules/Departments/department.php")==FALSE AND $makeDepartmentsPublic!="Y") {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
	if ($gibbonDepartmentID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
			$sql="SELECT * FROM gibbonDepartment WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID" ;
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
			
			//Get role within learning area
			$role=NULL ;
			if (isset($_SESSION[$guid]["username"])) {
				$role=getRole($_SESSION[$guid]["gibbonPersonID"], $gibbonDepartmentID, $connection2 ) ;
			}
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/departments.php'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>" . _('View All') . "</a> > </div><div class='trailEnd'>" . $row["name"] . "</div>" ;
			print "</div>" ;
			
			//Print overview
			if ($row["blurb"]!="" OR $role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Director" OR $role=="Manager") {
				print "<h2>" ;
				print _("Overview") ;
				if ($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Director" OR $role=="Manager") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
				}
				print "</h2>" ;
				print "<p>" ;
				print $row["blurb"] ;
				print "</p>" ;
			}
			
			//Print staff
			try {
				$dataStaff=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
				$sqlStaff="SELECT gibbonPerson.gibbonPersonID, gibbonDepartmentStaff.role, title, surname, preferredName, image_240, gibbonStaff.jobTitle FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonDepartmentID=:gibbonDepartmentID ORDER BY role, surname, preferredName" ;
				$resultStaff=$connection2->prepare($sqlStaff);
				$resultStaff->execute($dataStaff);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultStaff->rowCount()>0) {
				print "<h2>" ;
				print _("Staff") ;
				print "</h2>" ;
				print "<table class='noIntBorder' cellspacing='0' style='width:100%; margin-top: 20px'>" ;
				$count=0 ;
				$columns=5 ;
				
				while ($rowStaff=$resultStaff->fetch()) {
					if ($count%$columns==0) {
						print "<tr>" ;
					}
					print "<td style='width:20%; text-align: center; vertical-align: top'>" ;
						if ($rowStaff["image_240"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowStaff["image_240"])==FALSE) {    
							print "<img style='height: 100px; width: 75px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_75.jpg'/><br/>" ;
						}
						else {
							print "<img style='height: 100px; width: 75px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowStaff["image_240"] ."'/><br/>" ;
						}
						if (isActionAccessible($guid, $connection2, "/modules/Staff/staff_view_details.php")) {
							print "<div style='padding-top: 5px'><b><a href='" .  $_SESSION[$guid]["absoluteURL"]. "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $rowStaff["gibbonPersonID"] . "'>" . formatName($rowStaff["title"], $rowStaff["preferredName"], $rowStaff["surname"], "Staff") . "</a></b><br/><i>" ;
						}
						else {
							print "<div style='padding-top: 5px'><b>" . formatName($rowStaff["title"], $rowStaff["preferredName"], $rowStaff["surname"], "Staff") . "</b><br/><i>" ;
						}
						if ($rowStaff["jobTitle"]!="") {
							print $rowStaff["jobTitle"] ;
						}
						else {
							print $rowStaff["role"] ;
						}
						print "</i><br/></div>" ;
					print "</td>" ;
					
					if ($count%$columns==($columns-1)) {
						print "</tr>" ;
					}
					$count++ ;
				}
				
				for ($i=0;$i<$columns-($count%$columns);$i++) {
					print "<td></td>" ;
				}
				
				if ($count%$columns!=0) {
					print "</tr>" ;
				}
				
				print "</table>" ;
			}
				
			//Print sidebar
			$_SESSION[$guid]["sidebarExtra"]="" ; 
			
			//Print subject list
			if ($row["subjectListing"]!="") {
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Subject List") ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
				
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
				$subjects=explode(",", $row["subjectListing"]) ;
				for ($i=0;$i<count($subjects);$i++) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li>" . $subjects[$i] . "</li>" ;
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
			}
			
			
			//Print current course list
			try {
				$dataCourse=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
				$sqlCourse="SELECT * FROM gibbonCourse WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY nameShort, name" ;
				$resultCourse=$connection2->prepare($sqlCourse);
				$resultCourse->execute($dataCourse);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultCourse->rowCount()>0) {
				if ($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)") {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "Current Courses" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
				}
				else {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Course List") ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
				}	
				
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
				while ($rowCourse=$resultCourse->fetch()) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=" . $rowCourse["gibbonCourseID"] . "'>" . $rowCourse["nameShort"] . "</a> <span style='font-size: 85%; font-style: italic'>" . $rowCourse["name"] . "</span></li>" ;
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
			}
			
			//Print other courses
			if ($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Teacher") {
				try {
					$dataSelect=array("gibbonDepartmentID"=>$gibbonDepartmentID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sqlSelect="SELECT gibbonCourse.name AS course, gibbonSchoolYear.name AS year, gibbonCourseID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonDepartmentID=:gibbonDepartmentID AND NOT gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber, nameShort, course" ;
					$resultSelect=$connection2->prepare($sqlSelect);
					$resultSelect->execute($dataSelect);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultSelect->rowCount()>0) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Non-Current Courses") ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<table class='smallIntBorder' cellspacing=0 style='width: 100%; margin-top: 0px'>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<tr>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<td>" ;
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>" ;
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<select style='width:170px; float: none' name='gibbonCourseID'>" ;
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value=''></option>" ;
										$year="" ;
										while ($rowSelect=$resultSelect->fetch()) {
											if ($year!=$rowSelect["year"]) {
												$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<optgroup label='" . $rowSelect["year"] . "'>" ;
											}
											$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<option value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["course"]) . "</option>" ;
											$year=$rowSelect["year"] ;
										}
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</select>" ;
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='hidden' name='gibbonDepartmentID' value='$gibbonDepartmentID'>" ;
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input type='hidden' name='q' value='/modules/" . $_SESSION[$guid]["module"] . "/department_course.php'>" ;
									$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<input style='margin-top: 0px; float: right' type='submit' value='" . _('Go') . "'>" ;
								$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</td>" ;
							$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</tr>" ;
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</table>" ;
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</form>" ;
				}			
			}
			
			//Print useful reading
			try {
				$dataReading=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
				$sqlReading="SELECT * FROM gibbonDepartmentResource WHERE gibbonDepartmentID=:gibbonDepartmentID ORDER BY name" ;
				$resultReading=$connection2->prepare($sqlReading);
				$resultReading->execute($dataReading);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultReading->rowCount()>0 OR $role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Director" OR $role=="Manager") {
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Useful Reading") ;
				if ($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Director" OR $role=="Manager") {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
				while ($rowReading=$resultReading->fetch()) {
					if ($rowReading["type"]=="Link") {
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a target='_blank' href='" . $rowReading["url"] . "'>" . $rowReading["name"] . "</a></li>" ;
					}
					else {
						$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowReading["url"] . "'>" . $rowReading["name"] . "</a></li>" ;
					}
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
			}
		}
	}
}
?>