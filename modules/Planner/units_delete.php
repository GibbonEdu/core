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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . __($guid, 'Unit Planner') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Delete Unit') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Unit Planner_all") {
					$dataCourse=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sqlCourse="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Unit Planner_learningAreas") {
					$dataCourse=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlCourse="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$resultCourse=$connection2->prepare($sqlCourse);
				$resultCourse->execute($dataCourse);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($resultCourse->rowCount()!=1) {
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print __($guid, "You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					try {
						$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseID=:gibbonCourseID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($result->rowCount()!=1) {
						print "<div class='error'>" ;
							print __($guid, "The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						//Let's go!
						$row=$result->Fetch() ;
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_deleteProcess.php?gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr>
									<td> 
										<b><?php print __($guid, 'Are you sure you want to delete this record?') ; ?></b><br/>
										<span style="font-size: 90%; color: #cc0000"><i><?php print __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></span>
									</td>
									<td class="right">
										
									</td>
								</tr>
								<tr>
									<td> 
										<input name="gibbonCourseID" id="gibbonCourseID" value="<?php print $gibbonCourseID ?>" type="hidden">
										<input name="gibbonUnitID" id="gibbonUnitID" value="<?php print $gibbonUnitID ?>" type="hidden">
										<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
										<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<?php print __($guid, 'Yes') ; ?>">
									</td>
									<td class="right">
										
									</td>
								</tr>
							</table>
						</form>
						<?php
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>