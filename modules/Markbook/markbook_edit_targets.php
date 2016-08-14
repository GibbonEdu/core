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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_targets.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        if ($gibbonCourseClassID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();

                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Set Personalised Attainment Targets').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/markbook_edit_targetsProcess.php?gibbonCourseClassID=$gibbonCourseClassID&address=".$_SESSION[$guid]['address']."'>";
				echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
					echo "<tr class='head'>";
						echo '<th>';
						echo __($guid, 'Student');
						echo '</th>';
						echo "<th style='width:302px'>";
						echo __($guid, 'Attainment Target');
						echo '</th>';
						echo '</tr>';

						$count = 0;
						$rowNum = 'odd';
						try {
							$dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
							$sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
							$resultStudents = $connection2->prepare($sqlStudents);
							$resultStudents->execute($dataStudents);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}

						if ($resultStudents->rowCount() < 1) {
							echo '<tr>';
							echo '<td colspan=2>';
							echo '<i>'.__($guid, 'There are no records to display.').'</i>';
							echo '</td>';
							echo '</tr>';
						} else {
							$PAS = getSettingByScope($connection2, 'System', 'defaultAssessmentScale');

							while ($rowStudents = $resultStudents->fetch()) {
								if ($count % 2 == 0) {
									$rowNum = 'even';
								} else {
									$rowNum = 'odd';
								}
								++$count;

								//COLOR ROW BY STATUS!
								echo "<tr class=$rowNum>";
								echo '<td>';
								echo "<div style='padding: 2px 0px'>".($count).") <b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Markbook#'.$gibbonCourseClassID."'>".formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true).'</a><br/></div>';
								echo "<input name='$count-gibbonPersonID' id='$count-gibbonPersonID' value='".$rowStudents['gibbonPersonID']."' type='hidden'>";
								echo '</td>';

								try {
									$dataEntry = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
									$sqlEntry = 'SELECT * FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
									$resultEntry = $connection2->prepare($sqlEntry);
									$resultEntry->execute($dataEntry);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								$rowEntry = null;
								if ($resultEntry->rowCount() == 1) {
									$rowEntry = $resultEntry->fetch();
								}

								echo '<td>';
								//Create attainment grade select
								echo "<select name='$count-gibbonScaleGradeID' id='$count-gibbonScaleGradeID' style='width:302px'>";
									try {
										$dataSelect = array('gibbonScaleID' => $PAS);
										$sqlSelect = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
										$resultSelect = $connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									} catch (PDOException $e) {
									}
									echo "<option value=''></option>";
									$sequence = '';
									$descriptor = '';
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if (!(is_null($rowEntry))) {
											if ($rowEntry['value'] == $rowSelect['value']) {
												$selected = 'selected';
											}
										}
									echo "<option $selected value='".$rowSelect['gibbonScaleGradeID']."'>".htmlPrep(__($guid, $rowSelect['value'])).'</option>';
									}
								echo '</select>';
								echo '</td>';
							}
						}
					?>
					<tr>
						<td colspan=2 class="right">
							<input name="count" id="count" value="<?php echo $count ?>" type="hidden">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
					<?php
                echo '</table>';
                echo '</form>';
            }
        }
    }
}
?>
