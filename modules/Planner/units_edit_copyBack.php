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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_copyBack.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . _('Unit Planner') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_edit.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "'>" . _('Edit Unit') . "</a> > </div><div class='trailEnd'>" . _('Copy Unit Back') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["copyReturn"])) { $copyReturn=$_GET["copyReturn"] ; } else { $copyReturn="" ; }
		$copyReturnMessage="" ;
		$class="error" ;
		if (!($copyReturn=="")) {
			if ($copyReturn=="fail0") {
				$copyReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($copyReturn=="fail2") {
				$copyReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($copyReturn=="fail3") {
				$copyReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($copyReturn=="fail6") {
				$copyReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
			}
			print "<div class='$class'>" ;
				print $copyReturnMessage;
			print "</div>" ;
		} 
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="" OR $gibbonCourseClassID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Unit Planner_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID" ;
				}
				else if ($highestAction=="Unit Planner_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$year=$row["year"] ;
				$course=$row["course"] ;
				$class=$row["class"] ;
				
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print _("You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					try {
						$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($result->rowCount()!=1) {
						print "<div class='error'>" ;
							print _("The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						//Let's go!
						$row=$result->fetch() ;
						
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('School Year') . "</span><br/>" ;
									print "<i>" . $year . "</i>" ;
									print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Class') . "</span><br/>" ;
									print "<i>" . $course . "." . $class . "</i>" ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Unit') . "</span><br/>" ;
									print "<i>" . $row["name"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						
						print "<p style='margin-top: 20px'>" ;
						print sprintf(_('This function allows you to take all of the blocks from the selected working unit (%1$s in %2$s) and use them to replace the blocks in the master unit. In this way you can use your refined and improved unit as your master next time you deploy.'), $row["name"], "$course.$class") ;
						print "</p>" ;
						
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_edit_copyBackProcess.php?gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
							<table cellspacing='0' style="width: 100%">	
								<tr>
									<td> 
										<b><?php print _('Are you sure you want to proceed with this request?') ; ?></b><br/>
										<span style="font-size: 90%; color: #cc0000"><i><?php print _('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></i></span>
									</td>
									<td class="right">
										
									</td>
								</tr>
								<tr>
									<td>
										<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<?php print $gibbonCourseClassID ?>" type="hidden">
										<input name="gibbonCourseID" id="gibbonCourseID" value="<?php print $gibbonCourseID ?>" type="hidden">
										<input name="gibbonUnitID" id="gibbonUnitID" value="<?php print $gibbonUnitID ?>" type="hidden">
										<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
										<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
										<input type="submit" value="<?php print _('Yes') ; ?>">
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