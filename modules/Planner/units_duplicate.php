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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// common variables
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
    ])
    ->add(__('Duplicate Unit'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_duplicate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if courseschool year specified
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $yearName = $row['name'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    if ($gibbonUnitID == '') {
                        echo "<div class='error'>";
                        echo __('You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        try {
                            $data = array();
                            $sql = "SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=$gibbonUnitID AND gibbonUnit.gibbonCourseID=$gibbonCourseID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __('The specified record cannot be found.');
                            echo '</div>';
                        } else {
                            //Let's go!
                            $row = $result->fetch();

                            $step = null;
                            if (isset($_GET['step'])) {
                                $step = $_GET['step'];
                            }
                            if ($step != 1 and $step != 2 and $step != 3) {
                                $step = 1;
                            }

                            //Step 1
                            if ($step == 1) {
                                echo '<h2>';
                                echo __('Step 1');
                                echo '</h2>';
                                ?>
								<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_duplicate.php&step=2&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID" ?>">
									<table class='smallIntBorder fullWidth' cellspacing='0'>	
										<tr class='break'>
											<td colspan=2> 
												<h3><?php echo __('Source') ?></h3>
											</td>
										</tr>
										<tr>
											<td style='width: 275px'> 
												<b><?php echo __('School Year') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<?php
                                                try {
                                                    $dataYear = array('gibbonSchoolYearID' => $row['gibbonSchoolYearID']);
                                                    $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                                                    $resultYear = $connection2->prepare($sqlYear);
                                                    $resultYear->execute($dataYear);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }

												if ($resultYear->rowCount() != 1) {
													echo '<i>'.__('Unknown').'</i>';
												} else {
													$rowYear = $resultYear->fetch();
													echo "<input readonly value='".$rowYear['name']."' type='text' style='width: 300px'>";
												}
												?>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Course') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<?php echo "<input readonly value='".$row['courseName']."' type='text' style='width: 300px'>"; ?>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Unit') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<?php echo "<input readonly value='".$row['name']."' type='text' style='width: 300px'>"; ?>
											</td>
										</tr>
										
										<tr class='break'>
											<td colspan=2> 
												<h3><?php echo __('Target') ?></h3>
											</td>
										</tr>
												
										<tr>
											<td> 
												<b><?php echo __('Year') ?> *</b><br/>
											</td>
											<td class="right">
												<select name="gibbonSchoolYearIDCopyTo" id="gibbonSchoolYearIDCopyTo" class="standardWidth">
													<?php
                                                    echo "<option value='Please select...'>".__('Please select...').'</option>';
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
													gibbonSchoolYearIDCopyTo.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __('Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __('Course') ?> *</b><br/>
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
												<b><?php echo __('Unit') ?> *</b><br/>
												<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
											</td>
											<td class="right">
												<?php echo "<input readonly value='".$row['name']."' type='text' style='width: 300px'>"; ?>
											</td>
										</tr>
										
										<tr>
											<td>
												<span class="emphasis small">* <?php echo __('denotes a required field');?></span>
											</td>
											<td class="right">
												<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
												<input type="submit" value="<?php echo __('Submit'); ?>">
											</td>
										</tr>
									</table>
								</form>
								<?php

                            } elseif ($step == 2) {
                                echo '<h2>';
                                echo __('Step 2');
                                echo '</h2>';

                                $gibbonCourseIDTarget = $_POST['gibbonCourseIDTarget'];
                                if ($gibbonCourseIDTarget == '') {
                                    echo "<div class='error'>";
                                    echo __('You have not specified one or more required parameters.');
                                    echo '</div>';
                                } else {
                                    ?>
									<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_duplicateProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=".$_GET['q'] ?>">
										<table class='smallIntBorder fullWidth' cellspacing='0'>	
											<script type="text/javascript">
												/* Resource 1 Option Control */
												$(document).ready(function(){
													$(".copyLessons").click(function(){
														if ($('input[name=copyLessons]:checked').val()=="Yes" ) {
															$("#sourceClass").slideDown("fast", $("#sourceClass").css("display","table-row")); 
															$("#targetClass").slideDown("fast", $("#targetClass").css("display","table-row")); 
														} else {
															$("#sourceClass").css("display","none");
															$("#targetClass").css("display","none");
														}
													 });
												});
											</script>
											<tr>
												<td style='width: 275px'> 
													<b><?php echo __('Copy Lessons?') ?> *</b>
												</td>
												<td class="right">
													<input checked type="radio" name="copyLessons" value="Yes" class="copyLessons" /> Yes
													<input type="radio" name="copyLessons" value="No" class="copyLessons" /> No
												</td>
											</tr>
											<tr class='break'>
												<td colspan=2> 
													<h3><?php echo __('Source') ?></h3>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php echo __('School Year') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php
                                                    try {
                                                        $dataYear = array('gibbonSchoolYearID' => $row['gibbonSchoolYearID']);
                                                        $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                                                        $resultYear = $connection2->prepare($sqlYear);
                                                        $resultYear->execute($dataYear);
                                                    } catch (PDOException $e) {
                                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                                    }
													if ($resultYear->rowCount() != 1) {
														echo '<i>'.__('Unknown').'</i>';
													} else {
														$rowYear = $resultYear->fetch();
														echo "<input readonly value='".$rowYear['name']."' type='text' style='width: 300px'>";
													}
													?>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php echo __('Course') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php echo "<input readonly value='".$row['courseName']."' type='text' style='width: 300px'>"; ?>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php echo __('Unit') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php echo "<input readonly value='".$row['name']."' type='text' style='width: 300px'>"; ?>
												</td>
											</tr>
											<tr id="sourceClass">
												<td> 
													<b><?php echo __('Source Class') ?> *</b><br/>
												</td>
												<td class="right">
													<select name="gibbonCourseClassIDSource" id="gibbonCourseClassIDSource" class="standardWidth">
														<?php
                                                        echo "<option value='Please select...'>".__('Please select...').'</option>';
														try {
															$dataSelect = array('gibbonCourseID' => $gibbonCourseID);
															$sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID';
															$resultSelect = $connection2->prepare($sqlSelect);
															$resultSelect->execute($dataSelect);
														} catch (PDOException $e) {
														}
														while ($rowSelect = $resultSelect->fetch()) {
															echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
														}
														?>				
													</select>
												</td>
											</tr>
											
											<?php
                                            try {
                                                $dataSelect2 = array('gibbonCourseID' => $gibbonCourseIDTarget);
                                                $sqlSelect2 = 'SELECT gibbonCourse.name AS course, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID';
                                                $resultSelect2 = $connection2->prepare($sqlSelect2);
                                                $resultSelect2->execute($dataSelect2);
                                            } catch (PDOException $e) {
                                            }
											if ($resultSelect2->rowCount() == 1) {
												$rowSelect2 = $resultSelect2->fetch();
												$access = true;
												$course = $rowSelect2['course'];
												$year = $rowSelect2['year'];
											}
											?>
											
											<tr class='break'>
												<td colspan=2> 
													<h3><?php echo __('Target') ?></h3>
												</td>
											</tr>
											
											<tr>
												<td> 
													<b><?php echo __('School Year') ?>*</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php echo "<input readonly value='$year' type='text' style='width: 300px'>"; ?>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php echo __('Course') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php echo "<input readonly value='$course' type='text' style='width: 300px'>"; ?>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php echo __('Unit') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('This value cannot be changed.') ?></span>
												</td>
												<td class="right">
													<?php echo "<input readonly value='".$row['name']."' type='text' style='width: 300px'>"; ?>
												</td>
											</tr>
											<tr id="targetClass">
												<td> 
													<b><?php echo __('Classes') ?> *</b><br/>
													<span class="emphasis small"><?php echo __('Use Control, Command and/or Shift to select multiple.') ?></span>
												</td>
												<td class="right">
													<select name="gibbonCourseClassIDTarget[]" id="gibbonCourseClassIDTarget[]" multiple style="width: 302px; height: 100px">
														<?php
                                                        try {
                                                            $dataSelect = array('gibbonCourseIDTarget' => $gibbonCourseIDTarget);
                                                            $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseIDTarget';
                                                            $resultSelect = $connection2->prepare($sqlSelect);
                                                            $resultSelect->execute($dataSelect);
                                                        } catch (PDOException $e) {
                                                        }
														while ($rowSelect = $resultSelect->fetch()) {
															echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
														}
														?>				
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<span class="emphasis small">* <?php echo __('denotes a required field'); ?></span>
												</td>
												<td class="right">
													<input type="hidden" name="gibbonCourseIDTarget" value="<?php echo $gibbonCourseIDTarget ?>">
													<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
													<input type="submit" value="<?php echo __('Submit'); ?>">
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
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
?>
