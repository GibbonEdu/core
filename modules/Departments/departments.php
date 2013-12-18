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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Departments/departments.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>View All</div>" ;
	print "</div>" ;
	
	$departments=FALSE ;
	
	//LEARNING AREAS
	try {
		$dataLA=array(); 
		$sqlLA="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
		$resultLA=$connection2->prepare($sqlLA);
		$resultLA->execute($dataLA);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}	
	if ($resultLA->rowCount()>0) {
		$departments=TRUE ;
		print "<h2>" ;
			print "Learning Areas" ;
		print "</h2>" ;
		print "<table class='blank' cellspacing='0' style='width:100%; margin-top: 20px'>" ;
		$count=0 ;
		$columns=3 ;
		
		while ($rowLA=$resultLA->fetch()) {
			if ($count%$columns==0) {
				print "<tr>" ;
			}
			print "<td style='width:33%; text-align: center; vertical-align: top'>" ;
				if ($rowLA["logo"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowLA["logo"])==FALSE) {    
					print "<img style='height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
				}
				else {
					print "<img style='height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowLA["logo"] ."'/><br/>" ;
				}
				print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Departments/department.php&gibbonDepartmentID=" . $rowLA["gibbonDepartmentID"] . "'>" . $rowLA["name"] . "</a><br/><br/></div>" ;
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
	
	//ADMINISTRATION
	try {
		$dataLA=array(); 
		$sqlLA="SELECT * FROM gibbonDepartment WHERE type='Administration' ORDER BY name" ;
		$resultLA=$connection2->prepare($sqlLA);
		$resultLA->execute($dataLA);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}	
	if ($resultLA->rowCount()>0) {
		$departments=TRUE ;
		print "<h2>" ;
			print "Administration" ;
		print "</h2>" ;
		print "<table class='blank' cellspacing='0' style='width:100%; margin-top: 20px'>" ;
		$count=0 ;
		$columns=3 ;
		
		while ($rowLA=$resultLA->fetch()) {
			if ($count%$columns==0) {
				print "<tr>" ;
			}
			print "<td style='width:33%; text-align: center; vertical-align: top'>" ;
				if ($rowLA["logo"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowLA["logo"])==FALSE) {    
					print "<img style='height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
				}
				else {
					print "<img style='height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowLA["logo"] ."'/><br/>" ;
				}
				print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Departments/department.php&gibbonDepartmentID=" . $rowLA["gibbonDepartmentID"] . "'>" . $rowLA["name"] . "</a><br/><br/></div>" ;
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
	
	if ($departments==FALSE) {
		print "<div class='warning'>" ;
			print "There are no departments in this school." ;
		print "</div>" ;
	}
	
	
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]="" ; 
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($result->rowCount()>0) {
		$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<h2 class='sidebar'>" ;
		$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "My Classes" ;
		$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
		
		$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ul>" ;
		while ($row=$result->fetch()) {
			$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a></li>" ;
		}
		$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ul>" ;
	}
}
?>