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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
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
            $sql = 'SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourseClass, gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Enrolment by Class')."</a> > </div><div class='trailEnd'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row['courseNameShort'], $row['name']).'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo '<h2>';
            echo __($guid, 'Add Participants');
            echo '</h2>'; ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Participants') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple class='standardWidth' style="height: 150px">
								<optgroup label='--<?php echo __($guid, 'Enrolable Students') ?>--'>
								<?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlSelectWhere = '';
                                    if ($row['gibbonYearGroupIDList'] != '') {
                                        $years = explode(',', $row['gibbonYearGroupIDList']);
                                        for ($i = 0; $i < count($years); ++$i) {
                                            if ($i == 0) {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere = $sqlSelectWhere.'AND (gibbonYearGroupID=:'.$years[$i];
                                            } else {
                                                $dataSelect[$years[$i]] = $years[$i];
                                                $sqlSelectWhere = $sqlSelectWhere.' OR gibbonYearGroupID=:'.$years[$i];
                                            }

                                            if ($i == (count($years) - 1)) {
                                                $sqlSelectWhere = $sqlSelectWhere.')';
                                            }
                                        }
                                    } else {
                                        $sqlSelectWhere = ' FALSE';
                                    }
                                    $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY name, surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
								}
								?>
								</optgroup>
								<optgroup label='--<?php echo __($guid, 'All Users') ?>--'>
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									$expected = '';
									if ($rowSelect['status'] == 'Expected') {
										$expected = ' (Expected)';
									}
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')'.$expected.'</option>';
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
								<option value="Student"><?php echo __($guid, 'Student') ?></option>
								<option value="Teacher"><?php echo __($guid, 'Teacher') ?></option>
								<option value="Assistant"><?php echo __($guid, 'Assistant') ?></option>
								<option value="Technician"><?php echo __($guid, 'Technician') ?></option>
								<option value="Parent"><?php echo __($guid, 'Parent') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>

			<?php
            echo '<h2>';
            echo __($guid, 'Current Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND NOT role LIKE '%left' ORDER BY role DESC, surname, preferredName";
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
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_editProcessBulk.php'>";
                echo "<fieldset style='border: none'>";
                echo "<div class='linkTop' style='height: 27px'>"; ?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>

                        <div id="courseClassRow" style="display: none;">

                            <select style="width: 182px" name="gibbonCourseClassIDCopyTo">
                                <?php
                                print "<option value=''>" . _('Please select...') . "</option>" ;

                                try {
                                    $dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
                                    $sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort" ;
                                    $resultSelect=$connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                }
                                catch(PDOException $e) {
                                    print "<div class='error'>" . $e->getMessage() . "</div>" ;
                                }

                                while ($rowSelect=$resultSelect->fetch()) {
                                    if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
                                        print "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
                                    }
                                    else {
                                        print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
                                    }
                                }

                                ?>
                            </select>

                        </div>

						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
                            <option value="Copy to class"><?php echo __($guid, 'Copy to class') ?></option>
							<option value="Mark as left"><?php echo __($guid, 'Mark as left') ?></option>
							<option value="Delete"><?php echo __($guid, 'Delete') ?></option>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['<?php echo __($guid, 'Select action') ?>'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});

                            $(document).ready(function(){
                                $('#action').change(function () {
                                    if ($(this).val() == 'Copy to class') {
                                        $("#courseClassRow").slideDown("fast", $("#courseClassRow").css("display","block"));
                                    } else {
                                        $("#courseClassRow").css("display","none");
                                    }
                                });
                            });

                        </script>
						<?php
                echo '</div>';

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Email');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Role');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Reportable');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '<th style=\'text-align: center\'>'; ?>
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
                    if ($row['role'] == 'Student') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&subpage=Timetable'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</a>';
                    } else {
                        echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['email'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['reportable'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '<td style=\'text-align: center\'>';
                    echo "<input name='gibbonPersonID-$count' value='".$row['gibbonPersonID']."' type='hidden'>";
                    echo "<input name='role-$count' value='".$row['role']."' type='hidden'>";
                    echo "<input type='checkbox' name='check-$count' id='check-$count'>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                echo "<input name='count' value='$count' type='hidden'>";
                echo "<input name='gibbonCourseClassID' value='$gibbonCourseClassID' type='hidden'>";
                echo "<input name='gibbonCourseID' value='$gibbonCourseID' type='hidden'>";
                echo "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>";
                echo "<input name='address' value='".$_GET['q']."' type='hidden'>";
                echo '</fieldset>';
                echo '</form>';
            }

            echo '<h2>';
            echo __($guid, 'Former Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role LIKE '%left' ORDER BY role DESC, surname, preferredName";
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
                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_editProcessBulk.php'>";
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Email');
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
                    if ($row['role'] == 'Student - Left') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&subpage=Timetable'>".formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</a>';
                    } else {
                        echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['email'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</form>';
            }
        }
    }
}
?>
