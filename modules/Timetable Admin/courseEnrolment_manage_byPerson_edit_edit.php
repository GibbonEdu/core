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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$type=$_GET["type"] ;
	$allUsers=$_GET["allUsers"] ;
	$search=$_GET["search"] ;
		
	if ($gibbonPersonID=="" OR $gibbonCourseClassID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT role, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonCourseClassPerson.reportable FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')" ;
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&allUsers=$allUsers'>" . __($guid, 'Enrolment by Person') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/courseEnrolment_manage_byPerson_edit.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "&type=" . $_GET["type"] . "&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&allUsers=$allUsers'>" . $row["preferredName"] . " " . $row["surname"] . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Participant') . "</div>" ; 
			print "</div>" ;
			
			if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
			
			print "<div class='linkTop'>" ;
				if ($search!="") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID&type=$type'>" . __($guid, 'Back') . "</a>" ;
				}
			print "</div>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search" ?>">
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
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this course.') ?></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option <?php if ($row["role"]=="Student") { print "selected ";} ?>value="Student"><?php print __($guid, 'Student') ?></option>
								<option <?php if ($row["role"]=="Student - Left") { print "selected ";} ?>value="Student - Left"><?php print __($guid, 'Student - Left') ?></option>
								<option <?php if ($row["role"]=="Teacher") { print "selected ";} ?>value="Teacher"><?php print __($guid, 'Teacher') ?></option>
								<option <?php if ($row["role"]=="Teacher - Left") { print "selected ";} ?>value="Teacher - Left"><?php print __($guid, 'Teacher - Left') ?></option>
								<option <?php if ($row["role"]=="Assistant") { print "selected ";} ?>value="Assistant"><?php print __($guid, 'Assistant') ?></option>
								<option <?php if ($row["role"]=="Technician") { print "selected ";} ?>value="Technician"><?php print __($guid, 'Technician') ?></option>
								<option <?php if ($row["role"]=="Parent") { print "selected ";} ?>value="Parent"><?php print __($guid, 'Parent') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Reportable') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="reportable" id="reportable" class="standardWidth">
								<option <?php if ($row["reportable"]=="Y") {print "selected ";}?>value="Y"><?php print ynExpander($guid, 'Y') ?></option>
								<option <?php if ($row["reportable"]=="N") {print "selected ";}?>value="N"><?php print ynExpander($guid, 'N') ?></option>
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