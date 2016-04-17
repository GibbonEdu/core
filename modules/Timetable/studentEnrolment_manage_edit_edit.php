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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/studentEnrolment_manage_edit_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonCourseID=="" OR $gibbonPersonID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID2"=>$gibbonPersonID); 
			$sql="SELECT surname, preferredName, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClassPerson.role, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonDepartmentStaff.role='Coordinator' OR gibbonDepartmentStaff.role='Assistant Coordinator') AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID2" ; 	
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
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/studentEnrolment_manage.php'>" . __($guid, 'Manage Student Enrolment') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/studentEnrolment_manage_edit.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row["courseNameShort"], $row["name"]) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Participant') . "</div>" ; 
			print "</div>" ;
			
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail4") {
					$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_edit_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print htmlPrep($row["yearName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var yearName=new LiveValidation('yearName');
								yearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Course') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="courseName" id="courseName" maxlength=20 value="<?php print htmlPrep($row["courseName"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var courseName=new LiveValidation('courseName');
								coursename2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Class') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=10 value="<?php print htmlPrep($row["name"]) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Participant') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="participant" id="participant" maxlength=200 value="<?php print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student") ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var participant=new LiveValidation('participant');
								participant.add(Validate.Presence);
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option <?php if ($row["role"]=="Student") { print "selected ";} ?>value="Student"><?php print __($guid, 'Student') ?></option>
								<option <?php if ($row["role"]=="Student - Left") { print "selected ";} ?>value="Student - Left"><?php print __($guid, 'Student - Left') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<?php print $gibbonPersonID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>