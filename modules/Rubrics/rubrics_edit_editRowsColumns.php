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

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit_editRowsColumns.php') == false) {
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
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Manage Rubrics')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/rubrics_edit.php&gibbonRubricID='.$_GET['gibbonRubricID']."&search=$search&filter2=$filter2'>".__($guid, 'Edit Rubric')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Rubric Rows & Columns').'</div>';
            echo '</div>';

            if ($search != '' or $filter2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Rubrics/rubrics_edit.php&gibbonRubricID='.$_GET['gibbonRubricID']."&search=$search&filter2=$filter2&sidebar=false'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Check if school year specified
            $gibbonRubricID = $_GET['gibbonRubricID'];
            if ($gibbonRubricID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                try {
                    $data = array('gibbonRubricID' => $gibbonRubricID);
                    $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The specified record does not exist.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();
                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_edit_editRowsColumnsProcess.php?gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 760px">	
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Rubric Basics') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php echo __($guid, 'Scope') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<input readonly name="scope" id="scope" value="<?php echo $row['scope'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							
							<?php
                            if ($row['scope'] == 'Learning Area') {
                                try {
                                    $dataLearningArea = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
                                    $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                                    $resultLearningArea = $connection2->prepare($sqlLearningArea);
                                    $resultLearningArea->execute($dataLearningArea);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultLearningArea->rowCount() == 1) {
                                    $rowLearningAreas = $resultLearningArea->fetch();
                                }
                                ?>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Learning Area') ?> *</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<input readonly name="department" id="department" value="<?php echo $rowLearningAreas['name'] ?>" type="text" class="standardWidth" maxlength=20>
										<input name="gibbonDepartmentID" id="gibbonDepartmentID" value="<?php echo $row['gibbonDepartmentID'] ?>" type="hidden" class="standardWidth">
									</td>
								</tr>
								<?php

                            }
                    ?>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input readonly name="name" id="name" maxlength=50 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
								</td>
							</tr>
							
							<?php //ROWS!?>
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Rows') ?></h3>
								</td>
							</tr>
							<?php
                            try {
                                $dataRows = array('gibbonRubricID' => $gibbonRubricID);
                                $sqlRows = 'SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber';
                                $resultRows = $connection2->prepare($sqlRows);
                                $resultRows->execute($dataRows);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                    if ($resultRows->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        $count = 0;
                        while ($rowRows = $resultRows->fetch()) {
                            ?>
									<tr>
										<td> 
											<b><?php echo sprintf(__($guid, 'Row %1$s Title'), ($count + 1)) ?></b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<?php
                                            $outcomeBased = false;
                            if ($rowRows['gibbonOutcomeID'] != '') {
                                $outcomeBased = true;
                            }
                            ?>
											<script type="text/javascript">
												$(document).ready(function(){
													<?php
                                                    if ($outcomeBased == false) {
                                                        ?>
														$("#gibbonOutcomeID-<?php echo $count ?>").css("display","none");
														<?php

                                                    } else {
                                                        ?>
														$("#rowTitle-<?php echo $count ?>").css("display","none");
														<?php

                                                    }
                            ?>
													
													$(".type-<?php echo $count ?>").click(function(){
														if ($('input[name=type-<?php echo $count ?>]:checked').val()=="Standalone" ) {
															$("#gibbonOutcomeID-<?php echo $count ?>").css("display","none");
															$("#rowTitle-<?php echo $count ?>").css("display","block"); 
														}
														else if ($('input[name=type-<?php echo $count ?>]:checked').val()=="Outcome Based" ) {
															$("#rowTitle-<?php echo $count ?>").css("display","none");
															$("#gibbonOutcomeID-<?php echo $count ?>").css("display","block"); 
														}
													});
													
												});
											</script>
											<?php
                                                //Prep filtering base don year groups of rubric
                                                $years = explode(',', $row['gibbonYearGroupIDList']);
                            $dataSelect = array();
                            $filterSelect = '';
                            $count2 = 0;
                            foreach ($years as $year) {
                                $filterSelect .= " AND gibbonYearGroupIDList LIKE :gibbonSchoolYearID$count2";
                                $dataSelect["gibbonSchoolYearID$count2"] = '%'.$year.'%';
                                ++$count2;
                            }
                            ?>
												
											<input <?php if ($outcomeBased == false) {
    echo 'checked';
}
                            ?> type="radio" name="type-<?php echo $count ?>" value="Standalone" class="type-<?php echo $count ?>" /> <?php echo __($guid, 'Standalone') ?> 
											<input <?php if ($outcomeBased == true) {
    echo 'checked';
}
                            ?> type="radio" name="type-<?php echo $count ?>" value="Outcome Based" class="type-<?php echo $count ?>" /> <?php echo __($guid, 'Outcome Based') ?><br/>
											<select name='gibbonOutcomeID[]' id='gibbonOutcomeID-<?php echo $count ?>' style='width: 304px'>
												<option><option>
												<optgroup label='--<?php echo __($guid, 'School Outcomes') ?>--'>
													<?php
                                                    try {
                                                        $sqlSelect = "SELECT * FROM gibbonOutcome WHERE scope='School' AND active='Y' $filterSelect ORDER BY category, name";
                                                        $resultSelect = $connection2->prepare($sqlSelect);
                                                        $resultSelect->execute($dataSelect);
                                                    } catch (PDOException $e) {
                                                    }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $label = '';
                                if ($rowSelect['category'] == '') {
                                    $label = $rowSelect['name'];
                                } else {
                                    $label = $rowSelect['category'].' - '.$rowSelect['name'];
                                }
                                $selected = '';
                                if ($rowSelect['gibbonOutcomeID'] == $rowRows['gibbonOutcomeID']) {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".$rowSelect['gibbonOutcomeID']."'>$label</option>";
                            }
                            ?>
												</optgroup>
												<?php
                                                if ($row['scope'] == 'Learning Area') {
                                                    ?>
													<optgroup label='--<?php echo __($guid, 'Learning Area Outcomes') ?>--'>
														<?php
                                                        try {
                                                            $dataSelect['gibbonDepartmentID'] = $row['gibbonDepartmentID'];
                                                            $sqlSelect = "SELECT * FROM gibbonOutcome WHERE scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID AND active='Y' $filterSelect ORDER BY category, name";
                                                            $resultSelect = $connection2->prepare($sqlSelect);
                                                            $resultSelect->execute($dataSelect);
                                                        } catch (PDOException $e) {
                                                        }
                                                    while ($rowSelect = $resultSelect->fetch()) {
                                                        $label = '';
                                                        if ($rowSelect['category'] == '') {
                                                            $label = $rowSelect['name'];
                                                        } else {
                                                            $label = $rowSelect['category'].' - '.$rowSelect['name'];
                                                        }
                                                        $selected = '';
                                                        if ($rowSelect['gibbonOutcomeID'] == $rowRows['gibbonOutcomeID']) {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='".$rowSelect['gibbonOutcomeID']."'>$label</option>";
                                                    }
                                                    ?>
													</optgroup>
													<?php

                                                }
                            ?>
											</select>
											<input name="rowTitle[]" id="rowTitle-<?php echo $count ?>" value="<?php echo $rowRows['title'] ?>" type="text" class="standardWidth" maxlength=40>
											<input name="gibbonRubricRowID[]" id="gibbonRubricRowID[]" value="<?php echo $rowRows['gibbonRubricRowID'] ?>" type="hidden">
										</td>
									</tr>
									<?php
                                    ++$count;
                        }
                    }
                    ?>
							
							<?php //COLUMNS!?>
							<tr class='break'>
								<td colspan=2>
									<h3><?php echo __($guid, 'Columns') ?></h3>
								</td>
							</tr>
							<?php
                            try {
                                $dataColumns = array('gibbonRubricID' => $gibbonRubricID);
                                $sqlColumns = 'SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber';
                                $resultColumns = $connection2->prepare($sqlColumns);
                                $resultColumns->execute($dataColumns);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                    if ($resultColumns->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        //If no grade scale specified
                                if ($row['gibbonScaleID'] == '') {
                                    $count = 0;
                                    while ($rowColumns = $resultColumns->fetch()) {
                                        ?>
										<tr>
											<td> 
												<b><?php echo sprintf(__($guid, 'Column %1$s Title'), ($count + 1)) ?></b><br/>
												<span class="emphasis small"></span>
											</td>
											<td class="right">
												<input name="columnTitle[]" id="columnTitle[]" value="<?php echo $rowColumns['title'] ?>" type="text" class="standardWidth" maxlength=20>
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<?php echo $rowColumns['gibbonRubricColumnID'] ?>" type="hidden">
											</td>
										</tr>
										<?php
                                        ++$count;
                                    }
                                }
                                //If scale specified	
                                else {
                                    $count = 0;
                                    while ($rowColumns = $resultColumns->fetch()) {
                                        ?>
										<tr>
											<td> 
												<b><?php echo sprintf(__($guid, 'Column %1$s Grade'), ($count + 1)) ?></b><br/>
												<span class="emphasis small"></span>
											</td>
											<td class="right">
												<?php
                                                echo "<select name='gibbonScaleGradeID[]' id='gibbonScaleGradeID[]' style='width:304px'>";
                                        try {
                                            $dataSelect = array('gibbonScaleID' => $row['gibbonScaleID']);
                                            $sqlSelect = "SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND NOT value='Incomplete' ORDER BY sequenceNumber";
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
                                        while ($rowSelect = $resultSelect->fetch()) {
                                            if ($rowColumns['gibbonScaleGradeID'] == $rowSelect['gibbonScaleGradeID']) {
                                                echo "<option selected value='".$rowSelect['gibbonScaleGradeID']."'>".htmlPrep(__($guid, $rowSelect['value'])).' - '.htmlPrep(__($guid, $rowSelect['descriptor'])).'</option>';
                                            } else {
                                                echo "<option value='".$rowSelect['gibbonScaleGradeID']."'>".htmlPrep(__($guid, $rowSelect['value'])).' - '.htmlPrep(__($guid, $rowSelect['descriptor'])).'</option>';
                                            }
                                        }
                                        echo '</select>';
                                        ?>
												<input name="gibbonRubricColumnID[]" id="gibbonRubricColumnID[]" value="<?php echo $rowColumns['gibbonRubricColumnID'] ?>" type="hidden">
											</td>
										</tr>
										<?php
                                        ++$count;
                                    }
                                }
                    }
                    ?>
							
							
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

                }
            }
        }
    }
}
?>