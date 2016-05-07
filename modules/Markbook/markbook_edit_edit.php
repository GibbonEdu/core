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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Get alternative header names
$enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
$enableRawAttainment = getSettingByScope($connection2, 'Markbook', 'enableRawAttainment');
$enableGroupByTerm = getSettingByScope($connection2, 'Markbook', 'enableGroupByTerm');
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_edit.php') == false) {
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
        $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
        if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID2' => $gibbonCourseClassID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql = "(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
					UNION
					(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonMarkbookColumn.gibbonPersonIDCreator=:gibbonPersonID2 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID2 AND gibbonMarkbookColumnID=:gibbonMarkbookColumnID)
					ORDER BY course, class";
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
                try {
                    $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql2 = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();
                    $row2 = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Column').'</div>';
                    echo '</div>';

                    if ($row2['groupingID'] != '' and $row2['gibbonPersonIDCreator'] != $_SESSION[$guid]['gibbonPersonID']) {
                        echo "<div class='error'>";
                        echo __($guid, 'This column is part of a set of columns, which you did not create, and so cannot be individually edited.');
                        echo '</div>';
                    } else {
                        $returns = array();
                        $returns['error6'] = __($guid, 'Your request failed because you already have one "End of Year" column for this class.');
                        $returns['success1'] = __($guid, 'Planner was successfully added: you opted to add a linked Markbook column, and you can now do so below.');
                        if (isset($_GET['return'])) {
                            returnProcess($guid, $_GET['return'], null, $returns);
                        }

                        echo "<div class='linkTop'>";
                        if ($row2['gibbonPlannerEntryID'] != '') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=".$row2['gibbonPlannerEntryID']."'>".__($guid, 'View Linked Lesson')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'View Linked Lesson')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> | ";
                        }
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID'>".__($guid, 'Enter Data')."<img style='margin: 0 0 0px 5px' title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                        echo '</div>';
                        ?>
						<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/markbook_edit_editProcess.php?gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
							<table class='smallIntBorder fullWidth' cellspacing='0'>	
								<tr class='break'>
									<td colspan=2> 
										<h3><?php echo __($guid, 'Basic Information') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'> 
										<b><?php echo __($guid, 'Class') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php echo htmlPrep($row['course']).'.'.htmlPrep($row['class']) ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Unit') ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonUnitID" id="gibbonUnitID" class="standardWidth">
											<?php
                                            try {
                                                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                                $sqlSelect = "SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY name";
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }


											$lastType = '';
											$currentType = '';
											echo "<option value=''></option>";
											while ($rowSelect = $resultSelect->fetch()) {
												$selected = '';
												if ($rowSelect['gibbonUnitID'] == $row2['gibbonUnitID'] and $rowSelect['gibbonCourseClassID'] == $row2['gibbonCourseClassID']) {
													$selected = 'selected';
												}
												$currentType = (isset($rowSelect['type']))? $rowSelect['type'] : '';
												if ($currentType != $lastType) {
													echo "<optgroup label='--".$currentType."--'>";
												}
												echo "<option $selected class='".$rowSelect['gibbonCourseClassID']."' value='".$rowSelect['gibbonUnitID']."'>".htmlPrep($rowSelect['name']).'</option>';
												$lastType = $currentType;
											}

                                            //List any hooked units
                                            $lastType = '';
											$currentType = '';
											try {
												$dataHooks = array();
												$sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name";
												$resultHooks = $connection2->prepare($sqlHooks);
												$resultHooks->execute($dataHooks);
											} catch (PDOException $e) {
												echo "<div class='error'>".$e->getMessage().'</div>';
											}

											while ($rowHooks = $resultHooks->fetch()) {
												$hookOptions = unserialize($rowHooks['options']);
												if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
													try {
														$dataHookUnits = array('gibbonCourseClassID' => $gibbonCourseClassID);
														$sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') WHERE '.$hookOptions['classLinkJoinFieldClass'].'=:gibbonCourseClassID ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
														$resultHookUnits = $connection2->prepare($sqlHookUnits);
														$resultHookUnits->execute($dataHookUnits);
													} catch (PDOException $e) {
													}
													while ($rowHookUnits = $resultHookUnits->fetch()) {
														$selected = '';
														if ($rowHookUnits[$hookOptions['unitIDField']] == $row2['gibbonUnitID'] and $rowHooks['gibbonHookID'] == $row2['gibbonHookID'] and $rowHookUnits[$hookOptions['classLinkJoinFieldClass']] == $row2['gibbonCourseClassID']) {
															$selected = 'selected';
														}
														$currentType = $rowHooks['name'];
														if ($currentType != $lastType) {
															echo "<optgroup label='--".$currentType."--'>";
														}
														echo "<option $selected class='".$rowHookUnits[$hookOptions['classLinkIDField']]."' value='".$rowHookUnits[$hookOptions['unitIDField']].'-'.$rowHooks['gibbonHookID']."'>".htmlPrep($rowHookUnits[$hookOptions['unitNameField']]).'</option>';
														$lastType = $currentType;
													}
												}
											}

											?>
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Lesson') ?></b><br/>
									</td>
									<td class="right">
										<select name="gibbonPlannerEntryID" id="gibbonPlannerEntryID" class="standardWidth">
											<?php
                                            try {
                                                $dataSelect = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                                                $sqlSelect = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY name';
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }
											echo "<option value=''></option>";
											while ($rowSelect = $resultSelect->fetch()) {
												$selected = '';
												if ($rowSelect['gibbonPlannerEntryID'] == $row2['gibbonPlannerEntryID']) {
													$selected = 'selected ';
												}
												if ($rowSelect['gibbonHookID'] == '') {
													echo "<option $selected class='".$rowSelect['gibbonUnitID']."' value='".$rowSelect['gibbonPlannerEntryID']."'>".htmlPrep($rowSelect['name']).'</option>';
												} else {
													echo "<option $selected class='".$rowSelect['gibbonUnitID'].'-'.$rowSelect['gibbonHookID']."' value='".$rowSelect['gibbonPlannerEntryID']."'>".htmlPrep($rowSelect['name']).'</option>';
												}
											}
											?>				
										</select>
										<script type="text/javascript">
											$("#gibbonPlannerEntryID").chainedTo("#gibbonUnitID");
										</script>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Name') ?> *</b><br/>
									</td>
									<td class="right">
										<input name="name" id="name" maxlength=20 value="<?php echo htmlPrep($row2['name']) ?>" type="text" class="standardWidth">
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
										<input name="description" id="description" maxlength=1000 value="<?php echo htmlPrep($row2['description']) ?>" type="text" class="standardWidth">
										<script type="text/javascript">
											var description=new LiveValidation('description');
											description.add(Validate.Presence);
										</script>
									</td>
								</tr>
								<?php
								$types = getSettingByScope($connection2, 'Markbook', 'markbookType');
								if ($types != false) {
									$types = explode(',', $types);
									?>
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
                                                    $selected = '';
                                                    if ($row2['type'] == $types[$i]) {
                                                        $selected = 'selected';
                                                    }
                                                    ?>
													<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
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
										<?php if ($row2['attachment'] != '') { ?>
										<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
										<?php 
										}
                        			?>
									</td>
									<td class="right">
										<?php
                                        if ($row2['attachment'] != '') {
                                            echo __($guid, 'Current attachment:')." <a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row2['attachment']."'>".$row2['attachment'].'</a><br/><br/>';
                                        }
                        			?><input type="file" name="file" id="file"><br/><br/>
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

										    $timestamp = (isset($row2['date']))? strtotime($row2['date']) : time();

										    while ($rowTerm = $resultTerms->fetch()) {

										    	if ( isset($row2['gibbonSchoolYearTermID']) ) {
										    		$selected = ($rowTerm['gibbonSchoolYearTermID'] == $row2['gibbonSchoolYearTermID'])? 'selected' : '';
										    	} else {
									            	$selected = ($timestamp >= $rowTerm['firstTime'] && $timestamp < $rowTerm['lastTime'])? 'selected' : '';
									        	}

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
	                                        <input name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $row2['date']); ?>" type="text" class="standardWidth">
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

                                	<input type="hidden" name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $row2['date']); ?>" >

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
												$("#gibbonRubricIDAttainmentRow").slideDown("fast", $("#gibbonRubricIDAttainmentRow").css("display","table-row")); 
												
											} else {
												$("#gibbonScaleIDAttainmentRow").css("display","none");
												$("#attainmentWeightingRow").css("display","none");
												$("#attainmentRawMaxRow").css("display","none");
												$("#gibbonRubricIDAttainmentRow").css("display","none");
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
										<input <?php if ($row2['attainment'] == 'Y') { echo 'checked'; } ?> type="radio" name="attainment" value="Y" class="attainment" /> <?php echo __($guid, 'Yes') ?>
										<input <?php if ($row2['attainment'] == 'N') { echo 'checked'; } ?> type="radio" name="attainment" value="N" class="attainment" /> <?php echo __($guid, 'No') ?>
									</td>
								</tr>
								<tr id='gibbonScaleIDAttainmentRow' <?php if ($row2['attainment'] == 'N') { echo "style='display: none'"; } ?>>
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
												if ($row2['gibbonScaleIDAttainment'] == $rowSelect['gibbonScaleID']) {
													echo "<option selected value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												} else {
													echo "<option value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												}
											}
											?>				
										</select>
									</td>
								</tr>
								<?php

								if ($enableRawAttainment == 'Y') {
                                    ?>
									<tr id="attainmentRawMaxRow" <?php if ($row2['attainment'] == 'N') { echo "style='display: none'"; } ?>>
										<td> 
											<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Weighting');
											} else {
												echo __($guid, 'Attainment Total Mark');
											}
                                    		?></b><br/>
                                    		<span class="emphasis small"><?php echo __($guid, 'Leave blank to omit raw marks.') ?></span>
										</td>
										<td class="right">
											<input name="attainmentRawMax" id="attainmentRawMax" maxlength=4 value="<?php echo $row2['attainmentRawMax'] ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var attainmentRawMax=new LiveValidation('attainmentRawMax');
												attainmentRawMax.add(Validate.Numericality);
											</script>
										</td>
									</tr>
									<?php
                                } else {
                                	?>
                                	<input type="hidden" name="attainmentRawMax" id="attainmentRawMax" maxlength=4 value="<?php echo $row2['attainmentRawMax'] ?>" >
                                	<?php
                                }

                                if ($enableColumnWeighting == 'Y') {
                                    ?>
									<tr id="attainmentWeightingRow" <?php if ($row2['attainment'] == 'N') { echo "style='display: none'"; } ?>>
										<td> 
											<b><?php if ($attainmentAlternativeName != '') { echo $attainmentAlternativeName.' '.__($guid, 'Weighting');
											} else {
												echo __($guid, 'Attainment Weighting');
											}
                                    		?></b><br/>
										</td>
										<td class="right">
											<input name="attainmentWeighting" id="attainmentWeighting" maxlength=3 value="<?php echo floatval($row2['attainmentWeighting']); ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var attainmentWeighting=new LiveValidation('attainmentWeighting');
												attainmentWeighting.add(Validate.Numericality);
											</script>
										</td>
									</tr>
									<?php
                                }

                        		?>
								<tr id='gibbonRubricIDAttainmentRow' <?php if ($row2['attainment'] == 'N') { echo "style='display: none'"; } ?>>
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
												$selected = '';
												if ($row2['gibbonRubricIDAttainment'] == $rowSelect['gibbonRubricID']) {
													$selected = 'selected';
												}
												echo "<option $selected value='".$rowSelect['gibbonRubricID']."'>$label</option>";
											}
											if ($row['gibbonDepartmentID'] != '') {
												?>
												<optgroup label='--<?php echo __($guid, 'Learning Area Rubrics') ?>--'>
												<?php
                                                try {
                                                    $dataSelect = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
                                                    $sqlSelectWhere = '';
                                                    $years = explode(',', $row['gibbonYearGroupIDList']);
                                                    foreach ($years as $year) {
                                                        $dataSelect[$year] = "%$year%";
                                                        $sqlSelectWhere .= " AND gibbonYearGroupIDList LIKE :$year";
                                                    }
                                                    $sqlSelect = "SELECT * FROM gibbonRubric WHERE active='Y' AND scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID $sqlSelectWhere ORDER BY category, name";
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
													if ($row2['gibbonRubricIDAttainment'] == $rowSelect['gibbonRubricID']) {
														$selected = 'selected';
													}
													echo "<option $selected value='".$rowSelect['gibbonRubricID']."'>$label</option>";
												}
											}
											?>				
										</select>
									</td>
								</tr>
								
								<script type="text/javascript">
									/* Homework Control */
									$(document).ready(function(){
										 $(".effort").click(function(){
											if ($('input[name=effort]:checked').val()=="Y" ) {
												$("#gibbonScaleIDEffortRow").slideDown("fast", $("#gibbonScaleIDEffortRow").css("display","table-row")); 
												$("#gibbonRubricIDEffortRow").slideDown("fast", $("#gibbonRubricIDEffortRow").css("display","table-row")); 

											} else {
												$("#gibbonScaleIDEffortRow").css("display","none");
												$("#gibbonRubricIDEffortRow").css("display","none");
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
										<input <?php if ($row2['effort'] == 'Y') { echo 'checked'; } ?> type="radio" name="effort" value="Y" class="effort" /> <?php echo __($guid, 'Yes') ?>
										<input <?php if ($row2['effort'] == 'N') { echo 'checked'; } ?> type="radio" name="effort" value="N" class="effort" /> <?php echo __($guid, 'No') ?>
									</td>
								</tr>
								<tr id='gibbonScaleIDEffortRow' <?php if ($row2['effort'] == 'N') { echo "style='display: none'"; } ?>>
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
												if ($row2['gibbonScaleIDEffort'] == $rowSelect['gibbonScaleID']) {
													echo "<option selected value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												} else {
													echo "<option value='".$rowSelect['gibbonScaleID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
												}
											}
											?>				
										</select>
									</td>
								</tr>
								<tr id='gibbonRubricIDEffortRow' <?php if ($row2['effort'] == 'N') { echo "style='display: none'"; } ?>>
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
												$selected = '';
												if ($row2['gibbonRubricIDEffort'] == $rowSelect['gibbonRubricID']) {
													$selected = 'selected';
												}
												echo "<option $selected value='".$rowSelect['gibbonRubricID']."'>$label</option>";
											}
											if ($row['gibbonDepartmentID'] != '') {
												?>
												<optgroup label='--<?php echo __($guid, 'Learning Area Rubrics') ?>--'>
												<?php
                                                try {
                                                    $dataSelect = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
                                                    $sqlSelectWhere = '';
                                                    $years = explode(',', $row['gibbonYearGroupIDList']);
                                                    foreach ($years as $year) {
                                                        $dataSelect[$year] = "%$year%";
                                                        $sqlSelectWhere .= " AND gibbonYearGroupIDList LIKE :$year";
                                                    }
                                                    $sqlSelect = "SELECT * FROM gibbonRubric WHERE active='Y' AND scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID $sqlSelectWhere ORDER BY category, name";
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
													if ($row2['gibbonRubricIDEffort'] == $rowSelect['gibbonRubricID']) {
														$selected = 'selected';
													}
													echo "<option $selected value='".$rowSelect['gibbonRubricID']."'>$label</option>";
												}
											}
											?>				
										</select>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Include Comment?') ?> *</b><br/>
									</td>
									<td class="right">
										<input <?php if ($row2['comment'] == 'Y') { echo 'checked'; } ?> type="radio" name="comment" value="Y" class="comment" /> <?php echo __($guid, 'Yes') ?>
										<input <?php if ($row2['comment'] == 'N') { echo 'checked'; } ?> type="radio" name="comment" value="N" class="comment" /> <?php echo __($guid, 'No') ?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Include Uploaded Response?') ?> *</b><br/>
									</td>
									<td class="right">
										<input <?php if ($row2['uploadedResponse'] == 'Y') { echo 'checked'; } ?> type="radio" name="uploadedResponse" value="Y" class="uploadedResponse" /> <?php echo __($guid, 'Yes') ?>
										<input <?php if ($row2['uploadedResponse'] == 'N') { echo 'checked'; } ?> type="radio" name="uploadedResponse" value="N" class="uploadedResponse" /> <?php echo __($guid, 'No') ?>
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
											<option <?php if ($row2['viewableStudents'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
											<option <?php if ($row2['viewableStudents'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
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
											<option <?php if ($row2['viewableParents'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
											<option <?php if ($row2['viewableParents'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
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
										<input name="completeDate" id="completeDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row2['completeDate']) ?>" type="text" class="standardWidth">
										<script type="text/javascript">
											var completeDate=new LiveValidation('completeDate');
											completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
    }
}
?>