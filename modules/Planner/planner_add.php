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
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_add.php') == false) {
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
        //Set variables
        $today = date('Y-m-d');

        //Proceed!
        //Get viewBy, date and class variables
        $params = '';
        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'];
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'];
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'];
            if (isset($_GET['dateHuman']) == true) {
                $date = dateConvert($guid, $_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
            $params = "&viewBy=date&date=$date";
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
            $params = "&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView";
        }

        list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
        $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);

        $proceed = true;
        $extra = '';
        if ($viewBy == 'class') {
            if ($gibbonCourseClassID == '') {
                $proceed = false;
            } else {
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    $proceed = false;
                } else {
                    $row = $result->fetch();
                    $extra = $row['course'].'.'.$row['class'];
                    $gibbonDepartmentID = $row['gibbonDepartmentID'];
                    $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'];
                }
            }
        } else {
            $extra = dateConvertBack($guid, $date);
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because you do not have access to this action.');
            echo '</div>';
        } else {
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner.php$params'>".__($guid, 'Planner')." $extra</a> > </div><div class='trailEnd'".__($guid, '>Add Lesson Plan').'</div>';
            echo '</div>';

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_edit.php&gibbonPlannerEntryID='.$_GET['editID'].$params;
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            ?>

			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_addProcess.php?viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address'] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Class') ?> *</b><br/>
						</td>
						<td class="right">
							<?php
                            if ($viewBy == 'class') {
                                ?>
								<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<?php echo $row['course'].'.'.$row['class'] ?>" type="text" class="standardWidth">
								<input name="gibbonCourseClassID" id="gibbonCourseClassID" maxlength=20 value="<?php echo $row['gibbonCourseClassID'] ?>" type="hidden" class="standardWidth">
								<?php

                            } else {
                                ?>
								<select name="gibbonCourseClassID" id="gibbonCourseClassID" class="standardWidth">
									<?php
                                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                try {
                                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                                        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                                    } else {
                                        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class';
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
                                ?>
								</select>
								<script type="text/javascript">
									var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
									gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
								<?php

                            }
            				?>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'Unit') ?></b><br/>
						</td>
						<td class="right">
							<?php
                            if ($viewBy == 'class') {
                                ?>
								<select name="gibbonUnitID" id="gibbonUnitID" class="standardWidth">
									<?php
                                    //List gibbon units
                                    try {
                                        $dataSelect = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                                        $sqlSelect = "SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND active='Y' AND running='Y' ORDER BY name";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                                    $lastType = '';
                                    $currentType = '';
                                    echo "<option value=''></option>";
                                    echo "<optgroup label='--".__($guid, 'Gibbon Units')."--'>";
                                    while ($rowSelect = $resultSelect->fetch()) {
                                        echo "<option value='".$rowSelect['gibbonUnitID']."'>".htmlPrep($rowSelect['name']).'</option>';
                                        $lastType = $currentType;
                                    }
                                    echo '</optgroup>';

    								//List any hooked units
    								$lastType = '';
                                    $currentType = '';
                                    try {
                                        $dataHooks = array();
                                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name";
                                        $resultHooks = $connection2->prepare($sqlHooks);
                                        $resultHooks->execute($dataHooks);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowHooks = $resultHooks->fetch()) {
                                        $hookOptions = unserialize($rowHooks['options']);
                                        if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                                            try {
                                                $dataHookUnits = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                                $sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') WHERE '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldClass'].'=:gibbonCourseClassID ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
                                                $resultHookUnits = $connection2->prepare($sqlHookUnits);
                                                $resultHookUnits->execute($dataHookUnits);
                                            } catch (PDOException $e) {
                                            }
                                            while ($rowHookUnits = $resultHookUnits->fetch()) {
                                                $currentType = $rowHooks['name'];
                                                if ($currentType != $lastType) {
                                                    echo "<optgroup label='--".$currentType."--'>";
                                                }
                                                echo "<option value='".$rowHookUnits[$hookOptions['unitIDField']].'-'.$rowHooks['gibbonHookID']."'>".htmlPrep($rowHookUnits[$hookOptions['unitNameField']]).'</option>';
                                                $lastType = $currentType;
                                            }
                                        }
                                    }
                                    ?>
								</select>
								<?php

                            } else {
                                ?>
								<select name="gibbonUnitID" id="gibbonUnitID" class="standardWidth">
									<?php
                                    //List units
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = "SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' ORDER BY name";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                                $lastType = '';
                                $currentType = '';
                                echo "<option value=''></option>";
                                echo "<optgroup label='--".__($guid, 'Gibbon Units')."--'>";
                                while ($rowSelect = $resultSelect->fetch()) {
                                    echo "<option class='".$rowSelect['gibbonCourseClassID']."' value='".$rowSelect['gibbonUnitID']."'>".htmlPrep($rowSelect['name']).'</option>';
                                    $lastType = $currentType;
                                }
                                echo '</optgroup>';

                                    //List any hooked units
                                    $lastType = '';
                                $currentType = '';
                                try {
                                    $dataHooks = array();
                                    $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name";
                                    $resultHooks = $connection2->prepare($sqlHooks);
                                    $resultHooks->execute($dataHooks);
                                } catch (PDOException $e) {
                                }
                                while ($rowHooks = $resultHooks->fetch()) {
                                    $hookOptions = unserialize($rowHooks['options']);
                                    if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                                        echo 'qhere';
                                        try {
                                            $dataHookUnits = array();
                                            echo $sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
                                            $resultHookUnits = $connection2->prepare($sqlHookUnits);
                                            $resultHookUnits->execute($dataHookUnits);
                                        } catch (PDOException $e) {
                                        }
                                        while ($rowHookUnits = $resultHookUnits->fetch()) {
                                            $currentType = $rowHooks['name'];
                                            if ($currentType != $lastType) {
                                                echo "<optgroup label='--".$currentType."--'>";
                                            }
                                            echo "<option class='".$rowHookUnits[$hookOptions['classLinkJoinFieldClass']]."' value='".$rowHookUnits[$hookOptions['unitIDField']].'-'.$rowHooks['gibbonHookID']."'>".htmlPrep($rowHookUnits[$hookOptions['unitNameField']]).'</option>';
                                            $lastType = $currentType;
                                        }
                                    }
                                }
                                ?>
								</select>
								<script type="text/javascript">
									$("#gibbonUnitID").chainedTo("#gibbonCourseClassID");
								</script>
								<?php

                            }
            				?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=50 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Summary') ?></b><br/>
						</td>
						<td class="right">
							<input name="summary" id="summary" maxlength=255 value="" type="text" class="standardWidth">
						</td>
					</tr>

					<?php

                    //Try and find the next unplanned slot for this class.
                    if ($viewBy == 'class') {
                        //Get $_GET values
                        $nextDate = null;
                        if (isset($_GET['date'])) {
                            $nextDate = $_GET['date'];
                        }
                        $nextTimeStart = null;
                        if (isset($_GET['timeStart'])) {
                            $nextTimeStart = $_GET['timeStart'];
                        }
                        $nextTimeEnd = null;
                        if (isset($_GET['timeEnd'])) {
                            $nextTimeEnd = $_GET['timeEnd'];
                        }

                        if ($nextDate == '') {
                            try {
                                $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => date('Y-m-d'));
                                $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10';
                                $resultNext = $connection2->prepare($sqlNext);
                                $resultNext->execute($dataNext);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            $nextDate = '';
                            $nextTimeStart = '';
                            $nextTimeEnd = '';
                            while ($rowNext = $resultNext->fetch()) {
                                try {
                                    $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                    $resultPlanner = $connection2->prepare($sqlPlanner);
                                    $resultPlanner->execute($dataPlanner);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultPlanner->rowCount() == 0) {
                                    $nextDate = $rowNext['date'];
                                    $nextTimeStart = $rowNext['timeStart'];
                                    $nextTimeEnd = $rowNext['timeEnd'];
                                    break;
                                }
                            }
                        }
                    }
            		?>

					<tr>
						<td>
							<b><?php echo __($guid, 'Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
            				?><br/></span>
						</td>
						<td class="right">
							<?php
                            if ($viewBy == 'date') {
                                ?>
								<input readonly name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $date) ?>" type="text" class="standardWidth">
								<?php

                            } else {
                                ?>
								<input name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $nextDate) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var date=new LiveValidation('date');
									date.add(Validate.Presence);
									date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') { echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
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
										$( "#date" ).datepicker();
									});
								</script>
								<?php

                            }
            				?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Start Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="<?php if (isset($nextTimeStart)) { echo substr($nextTimeStart, 0, 5); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
							</script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT timeStart FROM gibbonPlannerEntry ORDER BY timeStart';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
										while ($rowAuto = $resultAuto->fetch()) {
											echo '"'.substr($rowAuto['timeStart'], 0, 5).'", ';
										}
										?>
									];
									$( "#timeStart" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'End Time') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="<?php if (isset($nextTimeEnd)) { echo substr($nextTimeEnd, 0, 5); } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var timeEnd=new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
							</script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT timeEnd FROM gibbonPlannerEntry ORDER BY timeEnd';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
										while ($rowAuto = $resultAuto->fetch()) {
											echo '"'.substr($rowAuto['timeEnd'], 0, 5).'", ';
										}
										?>
									];
									$( "#timeEnd" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Lesson Details') ?></b>
							<?php $description = getSettingByScope($connection2, 'Planner', 'lessonDetailsTemplate') ?>
							<?php echo getEditor($guid,  true, 'description', $description, 25, true, false, false) ?>
						</td>
					</tr>
					<tr id="teachersNotesRow">
						<td colspan=2>
							<b><?php echo __($guid, 'Teacher\'s Notes') ?></b>
							<?php $teachersNotes = getSettingByScope($connection2, 'Planner', 'teachersNotesTemplate') ?>
							<?php echo getEditor($guid,  true, 'teachersNotes', $teachersNotes, 25, true, false, false) ?>
						</td>
					</tr>



					<script type="text/javascript">
						/* Homework Control */
						$(document).ready(function(){
							$("#homeworkDueDateRow").css("display","none");
							$("#homeworkDueDateTimeRow").css("display","none");
							$("#homeworkDetailsRow").css("display","none");
							$("#homeworkSubmissionRow").css("display","none");
							$("#homeworkSubmissionDateOpenRow").css("display","none");
							$("#homeworkSubmissionDraftsRow").css("display","none");
							$("#homeworkSubmissionTypeRow").css("display","none");
							$("#homeworkSubmissionRequiredRow").css("display","none");
							$("#homeworkCrowdAssessRow").css("display","none");
							$("#homeworkCrowdAssessControlRow").css("display","none");

							//Response to clicking on homework control
							$(".homework").click(function(){
								if ($('input[name=homework]:checked').val()=="Yes" ) {
									homeworkDueDate.enable();
									homeworkDetails.enable();
									$("#homeworkDueDateRow").slideDown("fast", $("#homeworkDueDateRow").css("display","table-row"));
									$("#homeworkDueDateTimeRow").slideDown("fast", $("#homeworkDueDateTimeRow").css("display","table-row"));
									$("#homeworkDetailsRow").slideDown("fast", $("#homeworkDetailsRow").css("display","table-row"));
									$("#homeworkSubmissionRow").slideDown("fast", $("#homeworkSubmissionRow").css("display","table-row"));

									if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
										$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row"));
										$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row"));
										$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row"));
										$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row"));
										$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row"));

										if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
											$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row"));

										} else {
											$("#homeworkCrowdAssessControlRow").css("display","none");
										}
									} else {
										$("#homeworkSubmissionDateOpenRow").css("display","none");
										$("#homeworkSubmissionDraftsRow").css("display","none");
										$("#homeworkSubmissionTypeRow").css("display","none");
										$("#homeworkSubmissionRequiredRow").css("display","none");
										$("#homeworkCrowdAssessRow").css("display","none");
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								} else {
									homeworkDueDate.disable();
									homeworkDetails.disable();
									$("#homeworkDueDateRow").css("display","none");
									$("#homeworkDueDateTimeRow").css("display","none");
									$("#homeworkDetailsRow").css("display","none");
									$("#homeworkSubmissionRow").css("display","none");
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 });

							 //Response to clicking on online submission control
							 $(".homeworkSubmission").click(function(){
								if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
									$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row"));
									$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row"));
									$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row"));
									$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row"));
									$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row"));

									if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
										$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row"));

									} else {
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								} else {
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 });

							 //Response to clicking on crowd assessment control
							 $(".homeworkCrowdAssess").click(function(){
								if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
									$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row"));

								} else {
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 });
						});
					</script>

					<tr class='break' id="homeworkHeaderRow">
						<td colspan=2>
							<h3><?php echo __($guid, 'Homework') ?></h3>
						</td>
					</tr>
					<tr id="homeworkRow">
						<td>
							<b><?php echo __($guid, 'Homework?') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input type="radio" name="homework" value="Yes" class="homework" /> <?php echo __($guid, 'Yes') ?>
							<input checked type="radio" name="homework" value="No" class="homework" /> <?php echo __($guid, 'No') ?>
						</td>
					</tr>
					<tr id="homeworkDueDateRow">
						<td>
							<b><?php echo __($guid, 'Homework Due Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
            				?><br/></span>
						</td>
						<td class="right">
							<input name="homeworkDueDate" id="homeworkDueDate" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var homeworkDueDate=new LiveValidation('homeworkDueDate');
								homeworkDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							 	homeworkDueDate.add(Validate.Presence);
								homeworkDueDate.disable();
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#homeworkDueDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkDueDateTimeRow">
						<td>
							<b><?php echo __($guid, 'Homework Due Date Time') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format: hh:mm (24hr)') ?><br/></span>
						</td>
						<td class="right">
							<input name="homeworkDueDateTime" id="homeworkDueDateTime" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var homeworkDueDateTime=new LiveValidation('homeworkDueDateTime');
								homeworkDueDateTime.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } );
							</script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?php
                                        try {
                                            $dataAuto = array();
                                            $sqlAuto = 'SELECT DISTINCT SUBSTRING(homeworkDueDateTime,12,5) AS homeworkDueTime FROM gibbonPlannerEntry ORDER BY homeworkDueDateTime';
                                            $resultAuto = $connection2->prepare($sqlAuto);
                                            $resultAuto->execute($dataAuto);
                                        } catch (PDOException $e) {
                                        }
										while ($rowAuto = $resultAuto->fetch()) {
											echo '"'.$rowAuto['homeworkDueTime'].'", ';
										}
										?>
									];
									$( "#homeworkDueDateTime" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkDetailsRow">
						<td colspan=2>
							<b><?php echo __($guid, 'Homework Details') ?> *</b>
							<?php echo getEditor($guid,  true, 'homeworkDetails', '', 25, true, true, true) ?>
						</td>
					</tr>
					<tr id="homeworkSubmissionRow">
						<td>
							<b><?php echo __($guid, 'Online Submission?') ?> *</b><br/>
						</td>
						<td class="right">
							<input type="radio" name="homeworkSubmission" value="Yes" class="homeworkSubmission" /> Yes
							<input checked type="radio" name="homeworkSubmission" value="No" class="homeworkSubmission" /> No
						</td>
					</tr>
					<tr id="homeworkSubmissionDateOpenRow">
						<td>
							<b><?php echo __($guid, 'Submission Open Date') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?><br/></span>
						</td>
						<td class="right">
							<input name="homeworkSubmissionDateOpen" id="homeworkSubmissionDateOpen" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var homeworkSubmissionDateOpen=new LiveValidation('homeworkSubmissionDateOpen');
								homeworkSubmissionDateOpen.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
									$( "#homeworkSubmissionDateOpen" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkSubmissionDraftsRow">
						<td>
							<b><?php echo __($guid, 'Drafts') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionDrafts" id="homeworkSubmissionDrafts" class="standardWidth">
								<option value="0"><?php echo __($guid, 'None') ?></option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
							</select>
						</td>
					</tr>
					<tr id="homeworkSubmissionTypeRow">
						<td>
							<b><?php echo __($guid, 'Submission Type') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionType" id="homeworkSubmissionType" class="standardWidth">
								<option value="Link"><?php echo __($guid, 'Link') ?></option>
								<option value="File"><?php echo __($guid, 'File') ?></option>
								<option value="Link/File"><?php echo __($guid, 'Link/File') ?></option>
							</select>
						</td>
					</tr>
					<tr id="homeworkSubmissionRequiredRow">
						<td>
							<b><?php echo __($guid, 'Submission Required') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionRequired" id="homeworkSubmissionRequired" class="standardWidth">
								<option value="Optional">Optional</option>
								<option value="Compulsory">Compulsory</option>
							</select>
						</td>
					</tr>
					<?php if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php')) { ?>
						<tr id="homeworkCrowdAssessRow">
							<td>
								<b><?php echo __($guid, 'Crowd Assessment?') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Allow crowd assessment of homework?') ?></span>
							</td>
							<td class="right">
								<input type="radio" name="homeworkCrowdAssess" value="Yes" class="homeworkCrowdAssess" /> <?php echo __($guid, 'Yes') ?>
								<input checked type="radio" name="homeworkCrowdAssess" value="No" class="homeworkCrowdAssess" /> <?php echo __($guid, 'No') ?>
							</td>
						</tr>
						<tr id="homeworkCrowdAssessControlRow">
							<td>
								<b><?php echo __($guid, 'Access Controls?') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Decide who can see this homework.') ?></span>
							</td>
							<td class="right">
								<?php
                                echo "<table cellspacing='0' style='width: 308px' align=right>";
								echo "<tr class='head'>";
								echo '<th>';
								echo __($guid, 'Role');
								echo '</th>';
								echo "<th style='text-align: center'>";
								echo __($guid, 'Access');
								echo '</th>';
								echo '</tr>';
								echo "<tr class='even'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Class Teachers');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input checked disabled='disabled' type='checkbox' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='even'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Submitter');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input checked disabled='disabled' type='checkbox' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='odd'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Classmates');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessClassmatesRead' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='even'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Other Students');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessOtherStudentsRead' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='odd'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Other Teachers');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessOtherTeachersRead' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='even'>";
								echo "<td style='text-align: left'>";
								echo __($guid, "Submitter's Parents");
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessSubmitterParentsRead' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='odd'>";
								echo "<td style='text-align: left'>";
								echo __($guid, "Classmates's Parents");
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessClassmatesParentsRead' />";
								echo '</td>';
								echo '</tr>';
								echo "<tr class='even'>";
								echo "<td style='text-align: left'>";
								echo __($guid, 'Other Parents');
								echo '</td>';
								echo "<td style='text-align: center'>";
								echo "<input type='checkbox' name='homeworkCrowdAssessOtherParentsRead' />";
								echo '</td>';
								echo '</tr>';
								echo '</table>';?>
							</td>
						</tr>
					<?php
					}
            		//OUTCOMES
                    if ($viewBy == 'date') {
                        ?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Outcomes') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<div class='warning'>
									<?php echo __($guid, 'Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.') ?>
								</div>
							</td>
						</tr>
						<?php

                    } else {
                        ?>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Outcomes') ?></h3>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<p><?php echo __($guid, 'Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.') ?></p>
							</td>
						</tr>
						<?php
                        $type = 'outcome';
                        $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');
                        $categories = array();
                        $categoryCount = 0;
                        ?>
						<style>
							#<?php echo $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<?php echo $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<?php echo $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<?php echo $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<?php echo $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<script>
							$(function() {
								$( "#<?php echo $type ?>" ).sortable({
									placeholder: "<?php echo $type ?>-ui-state-highlight",
									axis: 'y'
								});
							});
						</script>
						<tr>
							<td colspan=2>
								<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
										<div id="outcomeOuter0">
											<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>Key outcomes listed here...</div>
										</div>
									</div>
								<div style='width: 100%; padding: 0px 0px 0px 0px'>
									<div class="ui-state-default_dud" style='padding: 0px; min-height: 66px'>
										<table class='blank' cellspacing='0' style='width: 100%'>
											<tr>
												<td style='width: 50%'>
													<script type="text/javascript">
														var outcomeCount=1 ;
														/* Unit type control */
														$(document).ready(function(){
															$("#new").click(function(){

															 });
														});
													</script>
													<select id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'><?php echo __($guid, 'Choose an outcome to add it to this lesson') ?></option>
														<?php
                                                        $currentCategory = '';
														$lastCategory = '';
														$switchContents = '';

														try {
															$countClause = 0;
															$years = explode(',', $gibbonYearGroupIDList);
															$dataSelect = array();
															$sqlSelect = '';
															foreach ($years as $year) {
																$dataSelect['clause'.$countClause] = '%'.$year.'%';
																$sqlSelect .= "(SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
																++$countClause;
															}
															$resultSelect = $connection2->prepare(substr($sqlSelect, 0, -6).'ORDER BY category, name');
															$resultSelect->execute($dataSelect);
														} catch (PDOException $e) {
															echo "<div class='error'>".$e->getMessage().'</div>';
														}
														echo "<optgroup label='--".__($guid, 'SCHOOL OUTCOMES')."--'>";
														while ($rowSelect = $resultSelect->fetch()) {
															$currentCategory = $rowSelect['category'];
															if (($currentCategory != $lastCategory) and $currentCategory != '') {
																echo "<optgroup label='--".$currentCategory."--'>";
																echo "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>";
																$categories[$categoryCount] = $currentCategory;
																++$categoryCount;
															}
															echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
															$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
															$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
															$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/units_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
															$switchContents .= 'outcomeCount++ ;';
															$switchContents .= "$('#newOutcome').val('0');";
															$switchContents .= 'break;';
															$lastCategory = $rowSelect['category'];
														}

														if ($gibbonDepartmentID != '') {
															$currentCategory = '';
															$lastCategory = '';
															$currentLA = '';
															$lastLA = '';
															try {
																$countClause = 0;
																$years = explode(',', $gibbonYearGroupIDList);
																$dataSelect = array('gibbonDepartmentID' => $gibbonDepartmentID);
																$sqlSelect = '';
																foreach ($years as $year) {
																	$dataSelect['clause'.$countClause] = '%'.$year.'%';
																	$sqlSelect .= "(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause".$countClause.') UNION ';
																	++$countClause;
																}
																$resultSelect = $connection2->prepare(substr($sqlSelect, 0, -6).'ORDER BY learningArea, category, name');
																$resultSelect->execute($dataSelect);
															} catch (PDOException $e) {
																echo "<div class='error'>".$e->getMessage().'</div>';
															}
															while ($rowSelect = $resultSelect->fetch()) {
																$currentCategory = $rowSelect['category'];
																$currentLA = $rowSelect['learningArea'];
																if (($currentLA != $lastLA) and $currentLA != '') {
																	echo "<optgroup label='--".strToUpper($currentLA).' '.__($guid, 'OUTCOMES')."--'>";
																}
																if (($currentCategory != $lastCategory) and $currentCategory != '') {
																	echo "<optgroup label='--".$currentCategory."--'>";
																	echo "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>";
																	$categories[$categoryCount] = $currentCategory;
																	++$categoryCount;
																}
																echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
																$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
																$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
																$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Planner/units_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
																$switchContents .= 'outcomeCount++ ;';
																$switchContents .= "$('#newOutcome').val('0');";
																$switchContents .= 'break;';
																$lastCategory = $rowSelect['category'];
																$lastLA = $rowSelect['learningArea'];
															}
														}
														?>
													</select><br/>
													<?php
                                                    if (count($categories) > 0) {
                                                        ?>
														<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
															<option value='all'><?php echo __($guid, 'View All') ?></option>
															<?php
                                                            $categories = array_unique($categories);
                                                        $categories = msort($categories);
                                                        foreach ($categories as $category) {
                                                            echo "<option value='$category'>$category</option>";
                                                        }
                                                        ?>
														</select>
														<script type="text/javascript">
															$("#newOutcome").chainedTo("#outcomeFilter");
														</script>
														<?php

                                                    }
                        							?>
                        							<script type='text/javascript'>
														var <?php echo $type ?>Used=new Array();
														var <?php echo $type ?>UsedCount=0 ;

														function outcomeDisplayElements(number) {
															$("#<?php echo $type ?>Outer0").css("display", "none") ;
															if (<?php echo $type ?>Used.indexOf(number)<0) {
																<?php echo $type ?>Used[<?php echo $type ?>UsedCount]=number ;
																<?php echo $type ?>UsedCount++ ;
																switch(number) {
																	<?php echo $switchContents ?>
																}
															}
															else {
																alert("This element has already been selected!") ;
																$('#newOutcome').val('0');
															}
														}
													</script>
												</td>
											</tr>
										</table>
									</div>
								</div>
							</td>
						</tr>
						<?php

                    }
            		?>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Markbook') ?></h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Create Markbook Column?') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Linked to this lesson by default.') ?></span>
						</td>
						<td class="right">
							<input type="radio" name="markbook" value="Y" id="markbook" /> <?php echo __($guid, 'Yes') ?>
							<input checked type="radio" name="markbook" value="N" id="markbook" /> <?php echo __($guid, 'No') ?>
						</td>
					</tr>



					<tr class='break'>
						<script type="text/javascript">
							/* Advanced Options Control */
							$(document).ready(function(){
								$("#accessRow").css("display","none");
								$("#accessRowStudents").css("display","none");
								$("#accessRowParents").css("display","none");
								$("#guestRow").css("display","none");
								$("#guestListRow").css("display","none");
								$("#guestRoleRow").css("display","none");

								$(".advanced").click(function(){
									if ($('input[name=advanced]:checked').val()=="Yes" ) {
										$("#accessRow").slideDown("fast", $("#accessRow").css("display","table-row"));
										$("#accessRowStudents").slideDown("fast", $("#accessRowStudents").css("display","table-row"));
										$("#accessRowParents").slideDown("fast", $("#accessRowParents").css("display","table-row"));
										$("#guestRow").slideDown("fast", $("#guestRow").css("display","table-row"));
										$("#guestListRow").slideDown("fast", $("#guestListRow").css("display","table-row"));
										$("#guestRoleRow").slideDown("fast", $("#guestRoleRow").css("display","table-row"));
									}
									else {
										$("#accessRow").slideUp("fast");
										$("#accessRowStudents").slideUp("fast");
										$("#accessRowParents").slideUp("fast");
										$("#guestRow").slideUp("fast");
										$("#guestListRow").slideUp("fast");
										$("#guestRoleRow").slideUp("fast");
									}
								 });
							});
						</script>
						<td colspan=2>
							<h3><?php echo __($guid, 'Advanced Options') ?></h3>
						</td>
					</tr>
					<tr>
						<td></td>
						<td class="right">
							<?php
                            echo "<input type='checkbox' name='advanced' class='advanced' id='advanced' value='Yes' />";
            				echo "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".__($guid, 'Show Advanced Options').'</span>'; ?>
						</td>
					</tr>

					<tr class='break' id="accessRow">
						<td colspan=2>
							<h4><?php echo __($guid, 'Access') ?></h4>
						</td>
					</tr>
					<tr id="accessRowStudents">
						<td>
							<b><?php echo __($guid, 'Viewable to Students') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<?php
                            $sharingDefaultStudents = getSettingByScope($connection2, 'Planner', 'sharingDefaultStudents'); ?>
							<select name="viewableStudents" id="viewableStudents" class="standardWidth">
								<option <?php if ($sharingDefaultStudents == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($sharingDefaultStudents == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr id="accessRowParents">
						<td>
							<b><?php echo __($guid, 'Viewable to Parents') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<?php
                            $sharingDefaultParents = getSettingByScope($connection2, 'Planner', 'sharingDefaultParents'); ?>
							<select name="viewableParents" id="viewableParents" class="standardWidth">
								<option <?php if ($sharingDefaultParents == 'Y') { echo 'selected'; } ?> value="Y"><?php echo __($guid, 'Yes') ?></option>
								<option <?php if ($sharingDefaultParents == 'N') { echo 'selected'; } ?> value="N"><?php echo __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>

					<tr class='break' id="guestRow">
						<td colspan=2>
							<h4><?php echo __($guid, 'Guests') ?></h4>
						</td>
					</tr>
					<tr id="guestListRow">
						<td>
							<b><?php echo __($guid, 'Guest List') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="guests[]" id="guests[]" multiple class='standardWidth' style="height: 150px">
								<?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT title, surname, preferredName, category, gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName(htmlPrep($rowSelect['title']), htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), htmlPrep($rowSelect['category']), true, true).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="guestRoleRow">
						<td>
							<b><?php echo __($guid, 'Role') ?></b><br/>
						</td>
						<td class="right">
							<select name="role" id="role" class="standardWidth">
								<option value="Guest Student"><?php echo __($guid, 'Guest Student') ?></option>
								<option value="Guest Teacher"><?php echo __($guid, 'Guest Teacher') ?></option>
								<option value="Guest Assistant"><?php echo __($guid, 'Guest Assistant') ?></option>
								<option value="Guest Technician"><?php echo __($guid, 'Guest Technician') ?></option>
								<option value="Guest Parent"><?php echo __($guid, 'Guest Parent') ?></option>
								<option value="Other Guest"><?php echo __($guid, 'Other Guest') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input type="checkbox" name="notify" value="on">
							<label for="notify"><?php echo __('Notify all class participants') ?></label>
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }

        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
    }
}
?>
