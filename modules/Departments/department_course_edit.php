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

if (isActionAccessible($guid, $connection2, "/modules/Departments/department_course_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if courseschool year specified
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"];
	$gibbonCourseID=$_GET["gibbonCourseID"];
	
	if ($gibbonDepartmentID=="" OR $gibbonCourseID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT gibbonSchoolYear.name AS year, gibbonDepartment.name AS department, gibbonCourse.name AS course, description, gibbonCourse.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/departments.php'>" . __($guid, 'View All') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "'>" . $row["department"] . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_course.php&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . $row["course"] . "$extra</a> ></div><div class='trailEnd'>" . __($guid, 'Edit Course') . "</div>" ;
			print "</div>" ;
			
			if (isset($_GET["return"])) { returnProcess($_GET["return"], null, null); }

			if ($role!="Coordinator" AND $role!="Assistant Coordinator" AND $role!="Teacher (Curriculum)") {
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else{
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_course_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&address=" . $_GET["q"] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr class='break'>
							<td colspan=2>
								<h3><?php print __($guid, 'Overview') ?></h3> 
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<?php print getEditor($guid,  TRUE, "description", $row["description"], 20 ) ?>
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	}
}
?>