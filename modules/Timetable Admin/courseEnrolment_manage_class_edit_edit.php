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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT role, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonCourseClassPerson.reportable FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Enrolment by Class')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage_class_edit.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row['courseNameShort'], $row['name'])."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Participant').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
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
								<option <?php if ($row['role'] == 'Teacher') { echo 'selected '; } ?>value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
								<option <?php if ($row['role'] == 'Teacher - Left') { echo 'selected '; } ?>value="Teacher - Left"><?php echo __($guid, 'Teacher - Left') ?></option>
								<option <?php if ($row['role'] == 'Assistant') { echo 'selected '; } ?>value="Assistant"><?php echo __($guid, 'Assistant') ?></option>
								<option <?php if ($row['role'] == 'Technician') { echo 'selected '; } ?>value="Technician"><?php echo __($guid, 'Technician') ?></option>
								<option <?php if ($row['role'] == 'Parent') { echo 'selected '; } ?>value="Parent"><?php echo __($guid, 'Parent') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Reportable') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="reportable" id="reportable" class="standardWidth">
								<option <?php if ($row['reportable'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo ynExpander($guid, 'Y') ?></option>
								<option <?php if ($row['reportable'] == 'N') { echo 'selected '; } ?>value="N"><?php echo ynExpander($guid, 'N') ?></option>
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