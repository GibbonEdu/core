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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/course_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Courses & Classes')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/course_manage_edit.php&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Edit Course & Classes')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Class').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, reportable, attendance FROM gibbonCourseClass, gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
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
            $row = $result->fetch(); ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/course_manage_class_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
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
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this course.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=10 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this course.') ?></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=5 value="<?php echo htmlPrep($row['nameShort']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Reportable?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Should this class show in reports?') ?></span>
						</td>
						<td class="right">
							<select name="reportable" id="reportable" class="standardWidth">
								<option <?php if ($row['reportable'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['reportable'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<?php if ( isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") ) : ?>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Track Attendance?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Should this class allow attendance to be taken?') ?></span>
						</td>
						<td class="right">
							<select name="attendance" id="attendance" class="standardWidth">
								<option <?php if ($row['attendance'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($row['attendance'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonCourseID" id="gibbonCourseID" value="<?php echo $gibbonCourseID ?>" type="hidden">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
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