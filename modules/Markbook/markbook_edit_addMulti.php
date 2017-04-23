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

//Get settings
$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
$enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
$enableRawAttainment = getSettingByScope($connection2, 'Markbook', 'enableRawAttainment');
$enableGroupByTerm = getSettingByScope($connection2, 'Markbook', 'enableGroupByTerm');
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_addMulti.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false or ($highestAction != 'Edit Markbook_multipleClassesAcrossSchool' and $highestAction != 'Edit Markbook_multipleClassesInDepartment' and $highestAction != 'Edit Markbook_everything')) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        if ($gibbonCourseClassID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
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

                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Multiple Columns').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                ?>

				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/markbook_edit_addMultiProcess.php?gibbonCourseClassID=$gibbonCourseClassID&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Basic Information') ?></h3>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Class') ?> *</b><br/>
								<span class="emphasis small">
									<?php
                                    echo __($guid, 'Use Control, Command and/or Shift to select multiple.');
									echo ' ';
									echo sprintf(__($guid, 'The current class (%1$s.%2$s) has already been selected.'), $row['course'], $row['class']);
									?>
								</span>
							</td>
							<td class="right">
								<?php
                                echo "<select multiple name='gibbonCourseClassIDMulti[]' id='gibbonCourseClassIDMulti[]' style='width:300px; height:150px'>";
								try {
									if ($highestAction == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction == 'Edit Markbook_everything') {
										$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
										$sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
									} elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
										$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
										$sqlSelect = "SELECT DISTINCT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class";
									}
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
								}
								echo '</select>'; ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Name') ?> *</b><br/>
							</td>
							<td class="right">
								<input name="name" id="name" maxlength=20 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var name2=new LiveValidation('name');
									name2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Description') ?> *</b><br/>
							</td>
							<td class="right">
								<input name="description" id="description" maxlength=1000 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var description=new LiveValidation('description');
									description.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<?php
                        $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
						if ($types != false) {
							$types = explode(',', $types); ?>
							<tr>
								<td>
									<b><?php echo __($guid, 'Type') ?> *</b><br/>
									<span class="emphasis small"></span>
								</td>
								<td class="right">
									<select name="type" id="type" class="standardWidth">
										<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
										<?php
                                        for ($i = 0; $i < count($types); ++$i) {
                                            ?>
											<option value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
										<?php

                                        }
                    					?>
									</select>
									<script type="text/javascript">
										var type=new LiveValidation('type');
										type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
						<?php
		                }
		                ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Attachment') ?></b><br/>
							</td>
							<td class="right">
								<input type="file" name="file" id="file"><br/><br/>
								<?php

                                //Get list of acceptable file extensions
                                try {
                                    $dataExt = array();
                                    $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                    $resultExt = $connection2->prepare($sqlExt);
                                    $resultExt->execute($dataExt);
                                } catch (PDOException $e) {
                                }
								$ext = '';
								while ($rowExt = $resultExt->fetch()) {
									$ext = $ext."'.".$rowExt['extension']."',";
								}
								?>

								<script type="text/javascript">
									var file=new LiveValidation('file');
									file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>

						<?php if ($enableGroupByTerm == 'Y') : ?>

							<tr class='break'>
								<td colspan=2>
									<h3>
										<?php echo __($guid, 'Term Date')  ?>
									</h3>
								</td>
							</tr>

							<tr>
								<td>
									<b><?php echo __($guid, 'Term') ?> *</b><br/>
								</td>
								<td class="right">
									<select name="gibbonSchoolYearTermID" id="gibbonSchoolYearTermID" class="standardWidth">
									<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>

								<?php
									try {
								        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'] );
								        $sql = 'SELECT gibbonSchoolYearTermID, name, UNIX_TIMESTAMP(firstDay) AS firstTime, UNIX_TIMESTAMP(lastDay) AS lastTime FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
								        $resultTerms = $connection2->prepare($sql);
								        $resultTerms->execute($data);
								    } catch (PDOException $e) {
								    }

								    while ($rowTerm = $resultTerms->fetch()) {

							            $selected = (time() >= $rowTerm['firstTime'] && time() < $rowTerm['lastTime'])? 'selected' : '';

							            print "<option $selected value='".$rowTerm['gibbonSchoolYearTermID']."'>".htmlPrep($rowTerm['name']).'</option>';
							        }

								 ?>
									</select>
									<script type="text/javascript">
										var term=new LiveValidation('gibbonSchoolYearTermID');
										term.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>

							<tr>
                                <td>
                                    <b><?php echo __($guid, 'Date') ?>  *</b><br/>
                                    <span class="emphasis small"><?php echo __($guid, '1. Format') ?>
                                    <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                                            echo 'dd/mm/yyyy';
                                        } else {
                                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                                        }
                                    ?></span>
                                </td>
                                <td class="right">
                                    <input name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, (isset($_GET['date'])? $_GET['date'] : date('Y-m-d')) ); ?>" type="text" class="standardWidth">
                                    <script type="text/javascript">
                                        var date=new LiveValidation('date');
                                        date.add(Validate.Presence);
                                        date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                                        } else {
                                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                                        }
                                        ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                                            echo 'dd/mm/yyyy';
                                        } else {
                                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                                        }
                                        ?>." } );
                                    </script>
                                     <script type="text/javascript">
                                        $(function() {
                                            $( "#date" ).datepicker();
                                        });
                                    </script>
                                </td>
                            </tr>


                        <?php else: ?>

                        	<input type="hidden" name="date" id="date" maxlength=10 value="<?php echo (isset($_GET['date']))? $_GET['date'] : dateConvertBack($guid, date('Y-m-d')); ?>" >

						<?php endif; ?>

						<tr class='break'>
							<td colspan=2>
								<h3>
									<?php echo __($guid, 'Assessment')  ?>
								</h3>
							</td>
						</tr>
						<script type="text/javascript">
							/* Homework Control */
							$(document).ready(function(){
								 $(".attainment").click(function(){
									if ($('input[name=attainment]:checked').val()=="Y" ) {
										$("#gibbonScaleIDAttainmentRow").slideDown("fast", $("#gibbonScaleIDAttainmentRow").css("display","table-row"));
										$("#attainmentWeightingRow").slideDown("fast", $("#attainmentWeightingRow").css("display","table-row"));
										$("#attainmentRawMaxRow").slideDown("fast", $("#attainmentRawMaxRow").css("display","table-row"));
										<?php if ($enableRubrics == 'Y') { ?>
                                            $("#gibbonRubricIDAttainmentRow").slideDown("fast", $("#gibbonRubricIDAttainmentRow").css("display","table-row"));
                                        <?php } ?>

									} else {
										$("#gibbonScaleIDAttainmentRow").css("display","none");
										$("#attainmentWeightingRow").css("display","none");
										$("#attainmentRawMaxRow").css("display","none");
                                        <?php if ($enableRubrics == 'Y') { ?>
                                            $("#gibbonRubricIDAttainmentRow").css("display","none");
                                        <?php } ?>
									}

								 });
							});
						</script>
						<tr>
							<td>
								<b><?php if ($attainmentAlternativeName != '') { echo sprintf(__($guid, 'Assess %1$s?'), $attainmentAlternativeName);
								} else {
									echo __($guid, 'Assess Attainment?');
								}
                				?> *</b><br/>
							</td>
							<td class="right">
								<input checked type="radio" name="attainment" value="Y" class="attainment" /> <?php echo __($guid, 'Yes') ?>
								<input type="radio" name="attainment" value="N" class="attainment" /> <?php echo __($guid, 'No') ?>
							</td>
						</tr>
						<tr id="gibbonScaleIDAttainmentRow">
							<td>
								<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Scale');
								} else {
									echo __($guid, 'Attainment Scale');
								}
                				?></b><br/>
							</td>
							<td class="right">
								<select name="gibbonScaleIDAttainment" id="gibbonScaleIDAttainment" class="standardWidth">
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = "SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
									echo "<option value=''></option>";
									while ($rowSelect = $resultSelect->fetch()) {
										$selected = '';
										if ($rowSelect['gibbonScaleID'] == $_SESSION[$guid]['defaultAssessmentScale']) {
											$selected = 'selected';
										}
										echo "<option $selected value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<?php

						if ($enableRawAttainment == 'Y') {
                            ?>
							<tr id="attainmentRawMaxRow">
								<td>
									<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Weighting');
									} else {
										echo __($guid, 'Attainment Total Mark');
									}
                            		?></b><br/>
                            		<span class="emphasis small"><?php echo __($guid, 'Leave blank to omit raw marks.') ?></span>
								</td>
								<td class="right">
									<input name="attainmentRawMax" id="attainmentRawMax" maxlength=4 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var attainmentRawMax=new LiveValidation('attainmentRawMax');
										attainmentRawMax.add(Validate.Numericality);
									</script>
								</td>
							</tr>
							<?php
                        }

                        if ($enableColumnWeighting == 'Y') {
                            ?>
							<tr id="attainmentWeightingRow">
								<td>
									<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Weighting');
									} else {
										echo __($guid, 'Attainment Weighting');
									}
                            		?></b><br/>
								</td>
								<td class="right">
									<input name="attainmentWeighting" id="attainmentWeighting" maxlength=3 value="1" type="text" class="standardWidth">
									<script type="text/javascript">
										var attainmentWeighting=new LiveValidation('attainmentWeighting');
										attainmentWeighting.add(Validate.Numericality);
									</script>
								</td>
							</tr>
							<?php

                        }

                        if ($enableRubrics == 'Y') { ?>
    						<tr id="gibbonRubricIDAttainmentRow">
    							<td>
    								<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Rubric');
    								} else {
    									echo __($guid, 'Attainment Rubric');
    								}
                    				?></b><br/>
    								<span class="emphasis small"><?php echo __($guid, 'Choose predefined rubric, if desired.') ?></span>
    							</td>
    							<td class="right">
    								<select name="gibbonRubricIDAttainment" id="gibbonRubricIDAttainment" class="standardWidth">
    									<option><option>
    									<optgroup label='--<?php echo __($guid, 'School Rubrics') ?> --'>
    									<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelectWhere = '';
                                            $years = explode(',', $row['gibbonYearGroupIDList']);
                                            foreach ($years as $year) {
                                                $dataSelect[$year] = "%$year%";
                                                $sqlSelectWhere .= " AND gibbonYearGroupIDList LIKE :$year";
                                            }
                                            $sqlSelect = "SELECT * FROM gibbonRubric WHERE active='Y' AND scope='School' $sqlSelectWhere ORDER BY category, name";
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
    										echo "<option value='".$rowSelect['gibbonRubricID']."'>$label</option>";
    									}
    									?>
    								</select>
    							</td>
    						</tr>
                        <?php } ?>

                        <?php if ($enableEffort == 'Y') { ?>
    						<script type="text/javascript">
    							/* Homework Control */
    							$(document).ready(function(){
    								 $(".effort").click(function(){
    									if ($('input[name=effort]:checked').val()=="Y" ) {
    										$("#gibbonScaleIDEffortRow").slideDown("fast", $("#gibbonScaleIDEffortRow").css("display","table-row"));
    										<?php if ($enableRubrics == 'Y') { ?>
                                                $("#gibbonRubricIDEffortRow").slideDown("fast", $("#gibbonRubricIDEffortRow").css("display","table-row"));
                                            <?php } ?>

    									} else {
    										$("#gibbonScaleIDEffortRow").css("display","none");
                                            <?php if ($enableRubrics == 'Y') { ?>
                                                $("#gibbonRubricIDEffortRow").css("display","none");
                                            <?php } ?>
    									}
    								 });
    							});
    						</script>
    						<tr>
    							<td>
    								<b><?php if ($effortAlternativeName != '') { echo sprintf(__($guid, 'Assess %1$s?'), $effortAlternativeName);
    								} else {
    									echo __($guid, 'Assess Effort?');
    								}
                    				?> *</b><br/>
    							</td>
    							<td class="right">
    								<input checked type="radio" name="effort" value="Y" class="effort" /> <?php echo __($guid, 'Yes') ?>
    								<input type="radio" name="effort" value="N" class="effort" /> <?php echo __($guid, 'No') ?>
    							</td>
    						</tr>
    						<tr id="gibbonScaleIDEffortRow">
    							<td>
    								<b><?php if ($effortAlternativeName != '') { echo $effortAlternativeName.' '.__($guid, 'Scale');
    								} else {
    									echo __($guid, 'Effort Scale');
    								}
                    				?></b><br/>
    							</td>
    							<td class="right">
    								<select name="gibbonScaleIDEffort" id="gibbonScaleIDEffort" class="standardWidth">
    									<?php
                                        try {
                                            $dataSelect = array();
                                            $sqlSelect = "SELECT * FROM gibbonScale WHERE (active='Y') ORDER BY name";
                                            $resultSelect = $connection2->prepare($sqlSelect);
                                            $resultSelect->execute($dataSelect);
                                        } catch (PDOException $e) {
                                        }
    									echo "<option value=''></option>";
    									while ($rowSelect = $resultSelect->fetch()) {
    										$selected = '';
    										if ($rowSelect['gibbonScaleID'] == $_SESSION[$guid]['defaultAssessmentScale']) {
    											$selected = 'selected';
    										}
    										echo "<option $selected value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
    									}
    									?>
    								</select>
    							</td>
    						</tr>
                            <?php if ($enableRubrics == 'Y') { ?>
        						<tr id="gibbonRubricIDEffortRow">
        							<td>
        								<b><?php if ($effortAlternativeName != '') { echo $effortAlternativeName.' '.__($guid, 'Rubric');
        								} else {
        									echo __($guid, 'Effort Rubric');
        								}
                        				?></b><br/>
        								<span class="emphasis small"><?php echo __($guid, 'Choose predefined rubric, if desired.') ?></span>
        							</td>
        							<td class="right">
        								<select name="gibbonRubricIDEffort" id="gibbonRubricIDEffort" class="standardWidth">
        									<option><option>
        									<optgroup label='--<?php echo __($guid, 'School Rubrics') ?>--'>
        									<?php
                                            try {
                                                $dataSelect = array();
                                                $sqlSelectWhere = '';
                                                $years = explode(',', $row['gibbonYearGroupIDList']);
                                                foreach ($years as $year) {
                                                    $dataSelect[$year] = "%$year%";
                                                    $sqlSelectWhere .= " AND gibbonYearGroupIDList LIKE :$year";
                                                }
                                                $sqlSelect = "SELECT * FROM gibbonRubric WHERE active='Y' AND scope='School' $sqlSelectWhere ORDER BY category, name";
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
        										echo "<option value='".$rowSelect['gibbonRubricID']."'>$label</option>";
        									}
        									?>
        								</select>
        							</td>
        						</tr>
                                <?php
                                }
                            } ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Include Comment?') ?> *</b><br/>
							</td>
							<td class="right">
								<input checked type="radio" name="comment" value="Y" class="comment" /> <?php echo __($guid, 'Yes') ?>
								<input type="radio" name="comment" value="N" class="comment" /> <?php echo __($guid, 'No') ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Include Uploaded Response?') ?> *</b><br/>
							</td>
							<td class="right">
								<input checked type="radio" name="uploadedResponse" value="Y" class="uploadedResponse" /> <?php echo __($guid, 'Yes') ?>
								<input type="radio" name="uploadedResponse" value="N" class="uploadedResponse" /> <?php echo __($guid, 'No') ?>
							</td>
						</tr>


						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Access') ?></h3>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Viewable to Students') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="viewableStudents" id="viewableStudents" class="standardWidth">
									<option value="Y"><?php echo __($guid, 'Yes') ?></option>
									<option value="N"><?php echo __($guid, 'No') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Viewable to Parents') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="viewableParents" id="viewableParents" class="standardWidth">
									<option value="Y"><?php echo __($guid, 'Yes') ?></option>
									<option value="N"><?php echo __($guid, 'No') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Go Live Date') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, '1. Format') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
                				?><br/><?php echo __($guid, '2. Column is hidden until date is reached.') ?></span>
							</td>
							<td class="right">
								<input name="completeDate" id="completeDate" maxlength=10 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var completeDate=new LiveValidation('completeDate');
									completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
									}
									?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormat'];
									}
									?>." } );
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#completeDate" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?><br/>
								<?php echo getMaxUpload($guid); ?>
								</span>
							</td>
							<td class="right">
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
?>
