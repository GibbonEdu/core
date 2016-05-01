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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID']."'>".__($guid, 'Edit Unit')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit_working.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID'].'&gibbonCourseClassID='.$_GET['gibbonCourseClassID'].'&gibbonUnitClassID='.$_GET['gibbonUnitClassID']."'>".__($guid, 'Edit Working Copy')."</a> > </div><div class='trailEnd'>".__($guid, 'Copy Back Block').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if courseschool year specified
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        $gibbonCourseID = $_GET['gibbonCourseID'];
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonUnitID = $_GET['gibbonUnitID'];
        $gibbonUnitBlockID = $_GET['gibbonUnitBlockID'];
        $gibbonUnitClassBlockID = $_GET['gibbonUnitClassBlockID'];
        $gibbonUnitClassID = $_GET['gibbonUnitClassID'];
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.nameShort";
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
                $row = $result->fetch();
                $year = $row['year'];
                $course = $row['course'];
                $class = $row['class'];

                //Check if unit specified
                if ($gibbonUnitID == '' or $gibbonUnitBlockID == '' or $gibbonUnitClassBlockID == '') {
                    echo "<div class='error'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'gibbonUnitClassBlockID' => $gibbonUnitClassBlockID);
                        $sql = 'SELECT gibbonUnitClassBlock.title AS block, gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonUnitBlock ON (gibbonUnitBlock.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID AND gibbonUnitBlock.gibbonUnitBlockID=:gibbonUnitBlockID AND gibbonUnit.gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
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

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'School Year').'</span><br/>';
                        echo '<i>'.$year.'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Class').'</span><br/>';
                        echo '<i>'.$course.'.'.$class.'</i>';
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Unit').'</span><br/>';
                        echo '<i>'.$row['name'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=3>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Block Title').'</span><br/>';
                        echo '<i>'.$row['block'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h3>';
                        echo __($guid, 'Options');
                        echo '</h3>';
                        echo '<p>';
                        echo __($guid, 'This action will use the selected block to replace the equivalent block in the master unit. The option below also lets you replace the equivalent block in all other working units within the unit.');
                        echo '</p>';

                        ?>
						<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL']."/modules/Planner/units_edit_working_copybackProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=$gibbonUnitID&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=$gibbonUnitClassID";
                        ?>">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr>
									<td style='width: 275px'> 
										<b><?php echo __($guid, 'Include Working Units?') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<select class="standardWidth" name="working">
											<?php
                                            echo "<option value='N'>".__($guid, 'No').'</option>';
                        echo "<option value='Y'>".__($guid, 'Yes').'</option>';
                        ?>				
										</select>
									</td>
								</tr>
								<tr>
									<td>
										<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
                        ?></span>
									</td>
									<td class="right">
										<input name="gibbonTTID" id="gibbonTTID" value="<?php echo $_GET['gibbonTTID'] ?>" type="hidden">
										<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $_GET['gibbonSchoolYearID'] ?>" type="hidden">
										<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
										<input type="submit" value="<?php echo __($guid, 'Submit');
                        ?>">
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
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
?>