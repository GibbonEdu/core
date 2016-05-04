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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_copyForward.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units_edit.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID'].'&gibbonUnitID='.$_GET['gibbonUnitID']."'>".__($guid, 'Edit Unit')."</a> > </div><div class='trailEnd'>".__($guid, 'Copy Unit Forward').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if courseschool year specified
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        $gibbonCourseID = $_GET['gibbonCourseID'];
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonUnitID = $_GET['gibbonUnitID'];
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '') {
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
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __($guid, 'You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
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

                        echo '<p>';
                        echo sprintf(__($guid, 'This function allows you to take the selected working unit (%1$s in %2$s) and use its blocks, and the master unit details, to create a new unit. In this way you can use your refined and improved unit as a new master unit whilst leaving your existing master unit untouched.'), $row['name'], "$course.$class");
                        echo '</p>';

                        ?>
						<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_copyForwardProcess.php?gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr class='break'>
									<td colspan=2> 
										<h3><?php echo __($guid, 'Source') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'> 
										<b><?php echo __($guid, 'School Year') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<?php
                                        echo "<input readonly value='".$year."' type='text' style='width: 300px'>";
                        				?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Class') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<?php echo "<input readonly value='".$course.'.'.$class."' type='text' style='width: 300px'>";
                       				 	?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Unit') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<?php echo "<input readonly value='".$row['name']."' type='text' style='width: 300px'>";
                        				?>
									</td>
								</tr>
								
								<tr class='break'>
									<td colspan=2> 
										<h3><?php echo __($guid, 'Target') ?></h3>
									</td>
								</tr>
										
								<tr>
									<td> 
										<b><?php echo __($guid, 'Year') ?> *</b><br/>
									</td>
									<td class="right">
										<select name="gibbonSchoolYearIDCopyTo" id="gibbonSchoolYearIDCopyTo" class="standardWidth">
											<?php
                                            echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
											try {
												$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
												$sqlSelect = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
												$resultSelect = $connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											} catch (PDOException $e) {
											}
											if ($resultSelect->rowCount() == 1) {
												$rowSelect = $resultSelect->fetch();
												try {
													$dataSelect2 = array('sequenceNumber' => $rowSelect['sequenceNumber']);
													$sqlSelect2 = 'SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>=:sequenceNumber ORDER BY sequenceNumber ASC';
													$resultSelect2 = $connection2->prepare($sqlSelect2);
													$resultSelect2->execute($dataSelect2);
												} catch (PDOException $e) {
												}
												while ($rowSelect2 = $resultSelect2->fetch()) {
													echo "<option value='".$rowSelect2['gibbonSchoolYearID']."'>".htmlPrep($rowSelect2['name']).'</option>';
												}
											}
											?>				
										</select>
										<script type="text/javascript">
											var gibbonSchoolYearIDCopyTo=new LiveValidation('gibbonSchoolYearIDCopyTo');
											gibbonSchoolYearIDCopyTo.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Course') ?> *</b><br/>
									</td>
									<td class="right">
										<select name="gibbonCourseIDTarget" id="gibbonCourseIDTarget" class="standardWidth">
											<?php
                                            try {
                                                if ($highestAction == 'Unit Planner_all') {
                                                    $dataSelect = array();
                                                    $sqlSelect = 'SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY nameShort';
                                                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                                                    $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                    $sqlSelect = "SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonCourse.nameShort";
                                                }
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }
											while ($rowSelect = $resultSelect->fetch()) {
												echo "<option class='".$rowSelect['gibbonSchoolYearID']."' value='".$rowSelect['gibbonCourseID']."'>".htmlPrep($rowSelect['course']).'</option>';
											}
											?>				
										</select>
										<script type="text/javascript">
											$("#gibbonCourseIDTarget").chainedTo("#gibbonSchoolYearIDCopyTo");
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'New Unit Name') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<?php echo "<input name='nameTarget' id='nameTarget' value='".$row['name']."' type='text' style='width: 300px'>"; ?>
										<script type="text/javascript">
											var nameTarget=new LiveValidation('nameTarget');
											nameTarget.add(Validate.Presence);
										</script>
									</td>
								</tr>
								
								<tr>
									<td>
										<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
									</td>
									<td class="right">
										<input name="gibbonCourseClassID" id="gibbonCourseClassID" value="<?php echo $gibbonCourseClassID ?>" type="hidden">
										<input name="gibbonCourseID" id="gibbonCourseID" value="<?php echo $gibbonCourseID ?>" type="hidden">
										<input name="gibbonUnitID" id="gibbonUnitID" value="<?php echo $gibbonUnitID ?>" type="hidden">
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
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
?>