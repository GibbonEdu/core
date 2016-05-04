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

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_edit.php') == false) {
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
        if ($highestAction != 'Manage Outcomes_viewEditAll' and $highestAction != 'Manage Outcomes_viewAllEditLearningArea') {
            echo "<div class='error'>";
            echo __($guid, 'You do not have access to this action.');
            echo '</div>';
        } else {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/outcomes.php'>".__($guid, 'Manage Outcomes')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Outcome').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $filter2 = '';
            if (isset($_GET['filter2'])) {
                $filter2 = $_GET['filter2'];
            }

            if ($filter2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/outcomes.php&filter2='.$filter2."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            //Check if school year specified
            $gibbonOutcomeID = $_GET['gibbonOutcomeID'];
            if ($gibbonOutcomeID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                try {
                    if ($highestAction == 'Manage Outcomes_viewEditAll') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID);
                        $sql = 'SELECT * FROM gibbonOutcome WHERE gibbonOutcomeID=:gibbonOutcomeID';
                    } elseif ($highestAction == 'Manage Outcomes_viewAllEditLearningArea') {
                        $data = array('gibbonOutcomeID' => $gibbonOutcomeID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonOutcome.* FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonOutcome.gibbonDepartmentID IS NULL WHERE gibbonOutcomeID=:gibbonOutcomeID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
                    }
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
                    $row = $result->fetch(); ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/outcomes_editProcess.php?gibbonOutcomeID=$gibbonOutcomeID&filter2=".$filter2 ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
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
										<input readonly name="gibbonDepartment" id="gibbonDepartment" value="<?php echo $rowLearningAreas['name'] ?>" type="text" class="standardWidth">
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
									<input name="name" id="name" maxlength=100 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var name2=new LiveValidation('name');
										name2.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="nameShort" id="nameShort" maxlength=14 value="<?php echo $row['nameShort'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var nameShort=new LiveValidation('nameShort');
										nameShort.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Active') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="active" id="active" class="standardWidth">
										<option <?php if ($row['active'] == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
										<option <?php if ($row['active'] == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
									</select>
								</td>
							</tr>
							
							<tr>
								<td> 
									<b><?php echo __($guid, 'Category') ?></b><br/>
								</td>
								<td class="right">
									<input name="category" id="category" maxlength=100 value="<?php echo $row['category'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										$(function() {
											var availableTags=[
												<?php
                                                try {
                                                    $dataAuto = array();
                                                    $sqlAuto = 'SELECT DISTINCT category FROM gibbonOutcome ORDER BY category';
                                                    $resultAuto = $connection2->prepare($sqlAuto);
                                                    $resultAuto->execute($dataAuto);
                                                } catch (PDOException $e) {
                                                }
											while ($rowAuto = $resultAuto->fetch()) {
												echo '"'.$rowAuto['category'].'", ';
											}
											?>
											];
											$( "#category" ).autocomplete({source: availableTags});
										});
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Description') ?></b><br/>
								</td>
								<td class="right">
									<textarea name='description' id='description' rows=5 style='width: 300px'><?php echo $row['description'] ?></textarea>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Year Groups') ?></b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Relevant student year groups') ?><br/></span>
								</td>
								<td class="right">
									<?php 
                                    $yearGroups = getYearGroups($connection2);
									if ($yearGroups == '') {
										echo '<i>'.__($guid, 'No year groups available.').'</i>';
									} else {
										for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
											$checked = '';
											if (is_numeric(strpos($row['gibbonYearGroupIDList'], $yearGroups[$i]))) {
												$checked = 'checked ';
											}
											echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
											echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
										}
									}
									?>
									<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
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

                }
            }
        }
    }
}
?>