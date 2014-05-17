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

if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	if ($gibbonDepartmentID=="" OR $gibbonCourseID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT gibbonDepartment.name AS department, gibbonCourse.name, gibbonCourse.description, gibbonSchoolYear.name AS year, gibbonCourse.gibbonSchoolYearID FROM gibbonDepartment JOIN gibbonCourse ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonCourseID=:gibbonCourseID" ;
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
			$role=getRole($_SESSION[$guid]["gibbonPersonID"], $gibbonDepartmentID, $connection2 ) ;
			
			$extra="" ;
			if (($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)" OR $role=="Teacher") AND $row["gibbonSchoolYearID"]!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
				$extra=" " . $row["year"];
			}
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>" . _('View All') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "'>" . $row["department"] . "</a> > </div><div class='trailEnd'>" . $row["name"] . "$extra</div>" ;
			print "</div>" ;
			
			
			//Print overview
			if ($row["description"]!="" OR $role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)") {
				print "<h2>" ;
				print _("Overview") ;
				if ($role=="Coordinator" OR $role=="Assistant Coordinator" OR $role=="Teacher (Curriculum)") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_course_edit.php&gibbonCourseID=$gibbonCourseID&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
				}
				print "</h2>" ;
				print "<p>" ;
				print $row["description"] ;
				print "</p>" ;
			}
			
			//Print Units
			$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
			print "<h2>" ;
			print _("Units") ;
			print "</h2>" ;
			
			try {
				$dataUnit=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlUnit="SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnit.gibbonCourseID=:gibbonCourseID ORDER BY name" ;
				$resultUnit=$connection2->prepare($sqlUnit);
				$resultUnit->execute($dataUnit);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			while ($rowUnit=$resultUnit->fetch()) {
				print "<h4>" ;
				print $rowUnit["name"] ;
				print "</h4>" ;
				print "<p>" ;
				print $rowUnit["description"] ;
				if ($rowUnit["attachment"]!="") {
					print "<br/><br/><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowUnit["attachment"] . "'>" . _('Download Unit Outline') . "</a></li>" ;
				}
				print "</p>" ;
			}
			
			try {
				$dataHooks=array(); 
				$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit'" ;
				$resultHooks=$connection2->prepare($sqlHooks);
				$resultHooks->execute($dataHooks);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			while ($rowHooks=$resultHooks->fetch()) {
				$hookOptions=unserialize($rowHooks["options"]) ;
				if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
					try {
						$dataHookUnits=array("gibbonCourseID"=>$gibbonCourseID); 
						$sqlHookUnits="SELECT DISTINCT " . $hookOptions["unitTable"] . "." . $hookOptions["unitNameField"] . ", " . $hookOptions["unitTable"] . "." . $hookOptions["unitDescriptionField"] . " FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["classLinkTable"] . "." . $hookOptions["unitCourseIDField"] . "=:gibbonCourseID ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
						$resultHookUnits=$connection2->prepare($sqlHookUnits);
						$resultHookUnits->execute($dataHookUnits);
					}
					catch(PDOException $e) {
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					while ($rowHookUnits=$resultHookUnits->fetch()) {
						print "<h4>" ;
						print $rowHookUnits[$hookOptions["unitNameField"]] ;
						if ($rowHooks["name"]!="") {
							print "<br/><span style='font-size: 75%; font-style: italic; font-weight: normal'>" . $rowHooks["name"] . " Unit</span>" ;
						}
						print "</h4>" ;
						print "<p>" ;
						print $rowHookUnits[$hookOptions["unitDescriptionField"]] ;
						print "</p>" ;
						
						
					}
				}
			}
				
			//Print sidebar
			$_SESSION[$guid]["sidebarExtra"]="" ; 
			
			//Print class list
			try {
				$dataCourse=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlCourse="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID ORDER BY class" ;
				$resultCourse=$connection2->prepare($sqlCourse);
				$resultCourse->execute($dataCourse);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultCourse->rowCount()>0) {
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2>" ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Class List") ;
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
			
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
				while ($rowCourse=$resultCourse->fetch()) {
					$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=" . $rowCourse["gibbonCourseClassID"] . "'>" . $rowCourse["course"] . "." . $rowCourse["class"] . "</a></li>" ;
				}
				$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;	
			}
		}
	}
}
?>