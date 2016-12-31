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

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_duplicate.php') == false) {
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Manage Rubrics')."</a> > </div><div class='trailEnd'>".__($guid, 'Duplicate Rubric').'</div>';
            echo '</div>';

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

                    if ($search != '' or $filter2 != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Rubrics/rubrics.php&search=$search&filter2=$filter2'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }
                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rubrics_duplicateProcess.php?gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2" ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
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
									<?php
                                    if ($highestAction == 'Manage Rubrics_viewEditAll') {
                                        ?>
										<select name="scope" id="scope" class="standardWidth">
											<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
											<option value="School"><?php echo __($guid, 'School') ?></option>
											<option value="Learning Area"><?php echo __($guid, 'Learning Area') ?></option>
										</select>
										<script type="text/javascript">
											var scope=new LiveValidation('scope');
											scope.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										</script>
										 <?php

                                    } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                                        ?>
										<input readonly name="scope" id="scope" value="Learning Area" type="text" class="standardWidth">
										<?php

                                    }
                   		 			?>
								</td>
							</tr>

							<?php
                            if ($highestAction == 'Manage Rubrics_viewEditAll') {
                                ?>
								<script type="text/javascript">
									$(document).ready(function(){
										$("#learningAreaRow").css("display","none");

										$("#scope").change(function(){
											if ($('#scope').val()=="Learning Area" ) {
												$("#learningAreaRow").slideDown("fast", $("#learningAreaRow").css("display","table-row"));
												gibbonDepartmentID.enable();
											}
											else {
												$("#learningAreaRow").css("display","none");
												gibbonDepartmentID.disable();
											}
										 });
									});
								</script>
								<?php

                            }
                    		?>
							<tr id='learningAreaRow'>
								<td>
									<b><?php echo __($guid, 'Learning Area') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="gibbonDepartmentID" id="gibbonDepartmentID" class="standardWidth">
										<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
										<?php
                                        try {
                                            if ($highestAction == 'Manage Rubrics_viewEditAll') {
                                                $dataSelect = array();
                                                $sqlSelect = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
                                            } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                                                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlSelect = "SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND type='Learning Area' ORDER BY name";
                                            }
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
									while ($rowSelect = $resultSelect->fetch()) {
										echo "<option value='".$rowSelect['gibbonDepartmentID']."'>".$rowSelect['name'].'</option>';
									}
									?>
									</select>
									<script type="text/javascript">
										var gibbonDepartmentID=new LiveValidation('gibbonDepartmentID');
										gibbonDepartmentID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
										<?php
                                        if ($highestAction == 'Manage Rubrics_viewEditAll') {
                                            echo 'gibbonDepartmentID.disable();';
                                        }
                    					?>
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Name') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="name" id="name" maxlength=50 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var name2=new LiveValidation('name');
										name2.add(Validate.Presence);
									</script>
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
