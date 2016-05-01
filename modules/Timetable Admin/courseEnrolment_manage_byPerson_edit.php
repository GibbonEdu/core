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

//Module includes for Timetable module
include './modules/Timetable/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $type = $_GET['type'];
    $allUsers = '';
    if (isset($_GET['allUsers'])) {
        $allUsers = $_GET['allUsers'];
    }
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if ($gibbonPersonID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            if ($allUsers == 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, NULL AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
            } else {
                if ($type == 'Student') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID)";
                } elseif ($type == 'Staff') {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS gibbonYearGroupID, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID) ORDER BY surname, preferredName";
                }
            }
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&allUsers=$allUsers'>".__($guid, 'Enrolment by Person')."</a> > </div><div class='trailEnd'>".$row['preferredName'].' '.$row['surname'].'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson.php&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Back to Search Results').'</a> | ';
            }
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers'>".__($guid, 'View')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
            echo '</div>';

            //INTERFACE TO ADD NEW CLASSES
            echo '<h2>';
            echo __($guid, 'Add Classes');
            echo '</h2>';
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_edit_addProcess.php?type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Classes') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<?php
                                if ($row['type'] == 'Student') {
                                    ?>
									<optgroup label='--<?php echo __($guid, 'Enrolable Classes') ?>--'>
									<?php
                                    try {
                                        $dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupIDList' => '%'.$row['gibbonYearGroupID'].'%');
                                        $sqlSelect = "SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class,
												(SELECT count(*) FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (status='Full' OR status='Expected') AND role='Student') AS studentCount 
											FROM gibbonCourse 
											JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
											WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupIDList LIKE :gibbonYearGroupIDList 
											ORDER BY course, class";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowSelect = $resultSelect->fetch()) {
                                        try {
                                            $dataSelect2 = array('gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                                            $sqlSelect2 = "SELECT surname, preferredName, title FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID";
                                            $resultSelect2 = $connection2->prepare($sqlSelect2);
                                            $resultSelect2->execute($dataSelect2);
                                        } catch (PDOException $e) {
                                        }
                                        $teachers = '';
                                        while ($rowSelect2 = $resultSelect2->fetch()) {
                                            $teachers .= formatName('', $rowSelect2['preferredName'], $rowSelect2['surname'], 'Staff', false).', ';
                                        }
                                        echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']);
                                        if ($teachers != '') {
                                            echo ' - '.substr($teachers, 0, -2);
                                        }
                                        echo ' - '.$rowSelect['studentCount'].' '.__($guid, 'students');
                                        echo '</option>';
                                    }
                                    ?>
									</optgroup>
								<?php

                                }
            ?>
								<optgroup label='--<?php echo __($guid, 'All Classes') ?>--'>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                                    $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' - '.$rowSelect['name'].'</option>';
            }
            ?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option <?php if ($type == 'Student') {
    echo 'selected ';
}
            ?>value="Student"><?php echo __($guid, 'Student') ?></option>
								<option <?php if ($type == 'Staff') {
    echo 'selected ';
}
            ?>value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
								<option value="Assistant"><?php echo __($guid, 'Assistant') ?></option>
								<option value="Technician"><?php echo __($guid, 'Technician') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>

			<?php
            //SHOW CURRENT ENROLMENT	
            echo '<h2>';
            echo __($guid, 'Current Enrolment');
            echo '</h2>';

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role, gibbonCourseClassPerson.reportable FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%left' ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_editProcessBulk.php?allUsers=$allUsers'>";
                echo "<fieldset style='border: none'>";
                echo "<div class='linkTop' style='height: 27px'>";
                ?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
							<option value="Mark as left"><?php echo __($guid, 'Mark as left') ?></option>
							<option value="Delete"><?php echo __($guid, 'Delete') ?></option>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
						</script>
						<?php
                    echo '</div>';

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Class Code');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Course');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Class Role');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Reportable');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '<th>';
                ?>
								<script type="text/javascript">
									$(function () {
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?php
                                echo "<input type='checkbox' class='checkall'>";
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo $row['course'].'.'.$row['class'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['name'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['reportable'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_edit.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_delete.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '<td>';
                    echo "<input name='gibbonCourseClassID-$count' value='".$row['gibbonCourseClassID']."' type='hidden'>";
                    echo "<input name='role-$count' value='".$row['role']."' type='hidden'>";
                    echo "<input type='checkbox' name='check-$count' id='check-$count'>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                echo "<input name='count' value='$count' type='hidden'>";
                echo "<input name='type' value='$type' type='hidden'>";
                echo "<input name='gibbonPersonID' value='$gibbonPersonID' type='hidden'>";
                echo "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>";
                echo "<input name='address' value='".$_GET['q']."' type='hidden'>";
                echo '</fieldset>';
                echo '</form>';
            }

            //SHOW CURRENT TIMETABLE IN EDIT VIEW
            echo "<a name='tt'></a>";
            echo '<h2>';
            echo 'Current Timetable View';
            echo '</h2>';

            $gibbonTTID = null;
            if (isset($_GET['gibbonTTID'])) {
                $gibbonTTID = $_GET['gibbonTTID'];
            }
            $ttDate = null;
            if (isset($_POST['ttDate'])) {
                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
            }

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php', "&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=$gibbonSchoolYearID&type=$type#tt", 'full', true);
            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }

            //SHOW OLD ENROLMENT RECORDS
            echo '<h2>';
            echo 'Old Enrolment';
            echo '</h2>';

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND role LIKE '%left' ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_editProcessBulk.php'>";
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Class Code');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Course');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Class Role');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo $row['course'].'.'.$row['class'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['name'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_edit.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_delete.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                echo "<input name='count' value='$count' type='hidden'>";
                echo "<input name='type' value='$type' type='hidden'>";
                echo "<input name='gibbonPersonID' value='$gibbonPersonID' type='hidden'>";
                echo "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>";
                echo "<input name='address' value='".$_GET['q']."' type='hidden'>";
                echo '</form>';
            }
        }
    }
}
?>