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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage_edit_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID2' => $gibbonPersonID);
            $sql = "SELECT surname, preferredName, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClassPerson.role, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonDepartmentStaff.role='Coordinator' OR gibbonDepartmentStaff.role='Assistant Coordinator') AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID2";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/studentEnrolment_manage.php'>".__($guid, 'Manage Student Enrolment')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/studentEnrolment_manage_edit.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row['courseNameShort'], $row['name'])."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Participant').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_edit_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="yearName" id="yearName" maxlength=20 value="<?php echo htmlPrep($row['yearName']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var yearName=new LiveValidation('yearName');
								yearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Course') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="courseName" id="courseName" maxlength=20 value="<?php echo htmlPrep($row['courseName']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var courseName=new LiveValidation('courseName');
								coursename2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Class') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=10 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Participant') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="participant" id="participant" maxlength=200 value="<?php echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student') ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var participant=new LiveValidation('participant');
								participant.add(Validate.Presence);
							</script>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php echo __($guid, 'Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option <?php if ($row['role'] == 'Student') { echo 'selected '; } ?>value="Student"><?php echo __($guid, 'Student') ?></option>
								<option <?php if ($row['role'] == 'Student - Left') { echo 'selected '; } ?>value="Student - Left"><?php echo __($guid, 'Student - Left') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<?php echo $gibbonPersonID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>