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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit.php') == false) {
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
        //IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
        $gibbonUnitID = $_GET['gibbonUnitID'];
        if (strpos($gibbonUnitID, '-') == false) {
            $hooked = false;
        } else {
            $hooked = true;
            $gibbonHookIDToken = substr($gibbonUnitID, 11);
            $gibbonUnitIDToken = substr($gibbonUnitID, 0, 10);
        }

        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/units.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'].'&gibbonCourseID='.$_GET['gibbonCourseID']."'>".__($guid, 'Unit Planner')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Unit').'</div>';
        echo '</div>';

        $returns = array();
        $returns['success1'] = __($guid, 'Smart Blockify was successful.');
        $returns['success2'] = __($guid, 'Copy was successful. The blocks from the selected working unit have replaced those in the master unit (see below for the new block listing).');
        $returns['success3'] = __($guid, 'Your unit was successfully created: you can now edit and deploy it using the form below.');
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $returns);
        }

        //Check if courseschool year specified
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        $gibbonCourseID = $_GET['gibbonCourseID'];
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            //IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
            if (strpos($gibbonUnitID, '-') == false) {
                try {
                    if ($highestAction == 'Unit Planner_all') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                    } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
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
                    $yearName = $row['name'];
                    $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'];

                    //Check if unit specified
                    if ($gibbonUnitID == '') {
                        echo "<div class='error'>";
                        echo __($guid, 'You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        try {
                            $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                            $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonCourse.gibbonDepartmentID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
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
                            $gibbonDepartmentID = $row['gibbonDepartmentID'];
                            ?>
							<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_editProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=".$_GET['q'] ?>" enctype="multipart/form-data">
                                <h3><?php echo __($guid, 'Unit Basics') ?></h3>
                                <table class='smallIntBorder fullWidth' cellspacing='0'>
									<tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Overview') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'>
											<b><?php echo __($guid, 'School Year') ?> *</b><br/>
											<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
										</td>
										<td class="right">
											<input readonly name="yearName" id="yearName" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Course') ?> *</b><br/>
											<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
										</td>
										<td class="right">
											<input readonly name="courseName" id="courseName" maxlength=20 value="<?php echo htmlPrep($row['courseName']) ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Name') ?> *</b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<input name="name" id="name" maxlength=40 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var name2=new LiveValidation('name');
												name2.add(Validate.Presence);
											</script>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<b><?php echo __($guid, 'Blurb') ?> *</b>
											<textarea name='description' id='description' rows=5 style='width: 300px'><?php echo htmlPrep($row['description']) ?></textarea>
											<script type="text/javascript">
												var description=new LiveValidation('description');
												description.add(Validate.Presence);
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
											<b><?php echo __($guid, 'Ordering') ?> *</b><br/>
											<span class="emphasis small"><?php echo __($guid, 'Units are arranged form lowest to highest ordering value, then alphabetically.'); ?></span>
										</td>
										<td class="right">
											<input name="ordering" id="ordering" maxlength=4 value="<?php echo htmlPrep($row['ordering']) ?>" type="text" class="standardWidth">
											<script type="text/javascript">
												var ordering=new LiveValidation('ordering');
												ordering.add(Validate.Presence);
												ordering.add(Validate.Numericality);
											</script>
										</td>
									</tr>
                                    <tr>
            							<td class='long' colspan=2>
            								<b><?php echo __($guid, 'Tags') ?> *</b><br/>
            								<span class="emphasis small"><?php echo __($guid, 'Use tags to describe unit and its contents.') ?></span><br/>
            								<?php
                                            $tags = getTagList($connection2);
                                            sort($tags, SORT_STRING) ;
                                            $list = '';
                                            foreach ($tags AS $tag) {
                                                $list = $list.'{id: "'.$tag.'", name: "'.$tag.'"},';
                                            }
                    						?>
            								<style>
            									td.long ul.token-input-list-facebook { width: 100%; margin-top: 5px }
            									td.long div.token-input-dropdown-facebook { width: 120px }
            								</style>
            								<input type="text" id="tags" name="tags" class='standardWidth' />
            								<?php
                                                $prepopulate = '';
                                                $tags = array();
            									$tagsInner = explode(',', $row['tags']);
                                                foreach ($tagsInner AS $tagInner) {
                                                    array_push ($tags, trim($tagInner));
                                                }
                                                sort($tags, SORT_STRING) ;
            									foreach ($tags as $tag) {
                                                    if ($tag != '')
                                                        $prepopulate .= '{id: \''.$tag.'\', name: \''.$tag.'\'}, ';
            									}
            									$prepopulate = substr($prepopulate, 0, -2);
            									?>
                                                <script type="text/javascript">
            									$(document).ready(function() {
            										 $("#tags").tokenInput([
            												<?php echo substr($list, 0, -1) ?>
            											],
            											{theme: "facebook",
            											hintText: "Start typing a tag...",
            											allowCreation: true,
            											<?php
                                                        if ($prepopulate != '{id: , name: }') {
                                                            echo "prePopulate: [ $prepopulate ],";
                                                        }
                            						?>
            											preventDuplicates: true});
            									});
            								</script>
            								<script type="text/javascript">
            									var tags=new LiveValidation('tags');
            									tags.add(Validate.Presence);
            								</script>
            							</td>
            						</tr>
									<tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Classes') ?></h3>
										</td>
									</tr>
									<?php
                                    if ($_SESSION[$guid]['gibbonSchoolYearIDCurrent'] == $gibbonSchoolYearID and $_SESSION[$guid]['gibbonSchoolYearIDCurrent'] == $_SESSION[$guid]['gibbonSchoolYearID']) {
                                        ?>
										<tr>
											<td colspan=2>
												<p><?php echo __($guid, 'Select classes which will study this unit.') ?></p>
												<?php
                                                $classCount = 0;
                                        try {
                                            $dataClass = array('gibbonCourseID' => $gibbonCourseID);
                                            $sqlClass = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name';
                                            $resultClass = $connection2->prepare($sqlClass);
                                            $resultClass->execute($dataClass);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($resultClass->rowCount() < 1) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'There are no records to display.');
                                            echo '</div>';
                                        } else {
                                            echo "<table cellspacing='0' style='width: 100%'>";
                                            echo "<tr class='head'>";
                                            echo '<th>';
                                            echo __($guid, 'Class');
                                            echo '</th>';
                                            echo '<th>';
                                            echo __($guid, 'Running')."<br/><span style='font-size: 80%'>".__($guid, 'Is class studying this unit?').'</span>';
                                            echo '</th>';
                                            echo '<th>';
                                            echo __($guid, 'First Lesson')."<br/><span style='font-size: 80%'>";
                                            if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                                                echo 'dd/mm/yyyy';
                                            } else {
                                                echo $_SESSION[$guid]['i18n']['dateFormat'];
                                            }
                                            echo '</span>';
                                            echo '</th>';
                                            echo '<th>';
                                            echo __($guid, 'Actions');
                                            echo '</th>';
                                            echo '</tr>';

                                            $count = 0;
                                            $rowNum = 'odd';

                                            while ($rowClass = $resultClass->fetch()) {
                                                if ($count % 2 == 0) {
                                                    $rowNum = 'even';
                                                } else {
                                                    $rowNum = 'odd';
                                                }
                                                ++$count;

                                                try {
                                                    $dataClassData = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseClassID' => $rowClass['gibbonCourseClassID']);
                                                    $sqlClassData = 'SELECT * FROM gibbonUnitClass WHERE gibbonUnitID=:gibbonUnitID AND gibbonCourseClassID=:gibbonCourseClassID';
                                                    $resultClassData = $connection2->prepare($sqlClassData);
                                                    $resultClassData->execute($dataClassData);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                $rowClassData = null;
                                                if ($resultClassData->rowCount() == 1) {
                                                    $rowClassData = $resultClassData->fetch();
                                                }

												//COLOR ROW BY STATUS!
												echo "<tr class=$rowNum>";
                                                echo '<td>';
                                                echo $row['courseName'].'.'.$rowClass['name'].'</a>';
                                                echo '</td>';
                                                echo '<td>';
                                                ?>
												<input name="gibbonCourseClassID<?php echo $classCount?>" id="gibbonCourseClassID<?php echo $classCount?>" maxlength=10 value="<?php echo $rowClass['gibbonCourseClassID'] ?>" type="hidden" class="standardWidth">
												<select name="running<?php echo $classCount?>" id="running<?php echo $classCount?>" style="width:100%">
													<option <?php if ($rowClassData['running'] == 'N') { echo 'selected '; }?>value="N"><?php echo __($guid, 'No') ?></option>
													<option <?php if ($rowClassData['running'] == 'Y') { echo 'selected '; }?>value="Y"><?php echo __($guid, 'Yes') ?></option>
													</select>
													<?php
												echo '</td>';
                                                echo '<td>';
                                                try {
                                                    $dataDate = array('gibbonCourseClassID' => $rowClass['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                                                    $sqlDate = 'SELECT date FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart';
                                                    $resultDate = $connection2->prepare($sqlDate);
                                                    $resultDate->execute($dataDate);
                                                } catch (PDOException $e) {
                                                }
                                                if ($resultDate->rowCount() < 1) {
                                                    echo '<i>'.__($guid, 'There are no records to display.').'</i>';
                                                } else {
                                                    $rowDate = $resultDate->fetch();
                                                    echo dateConvertBack($guid, $rowDate['date']);
                                                }
                                                echo '</td>';
                                                echo '<td>';
                                                if ($rowClassData['running'] == 'Y') {
                                                    if ($resultDate->rowCount() < 1) {
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_deploy.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClassData['gibbonUnitClassID']."'><img title='Edit Unit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                                    } else {
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_working.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClassData['gibbonUnitClassID']."'><img title='Edit Unit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                                    }
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&viewBy=class'><img style='margin-top: 3px' title='".__($guid, 'View Planner')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_copyBack.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClassData['gibbonUnitClassID']."'><img title='Copy Back' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copyback.png'/></a> ";
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_copyForward.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClassData['gibbonUnitClassID']."'><img title='Copy Forward' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copyforward.png'/></a> ";
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_smartBlockify.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClassData['gibbonUnitClassID']."'><img title='Smart Blockify' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/run.png'/></a> ";
                                                }
                                                echo '</td>';
                                                echo '</tr>';
                                                ++$classCount;
                                            }
                                            echo '</table>';
                                        }
                                        ?>
										</td>
									</tr>
									<?php

                                    } else {
                                        echo '<tr>';
                                        echo "<td colspan=2 style='margin-top: 0; padding-top: 0'>";
                                        echo "<div class='warning'>";
                                        echo __($guid, 'You are currently not logged into the current year and/or are looking at units in another year, and so you cannot access your classes. Please log back into the current school year, and look at units in the current year.');
                                        echo '</div>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                            		?>

									<tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Unit Outline') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<p><?php echo __($guid, 'The contents of this field are viewable only to those with full access to the Planner (usually teachers and administrators, but not students and parents), whereas the downloadable version (below) is available to more users (usually parents).') ?></p>
											<?php echo getEditor($guid,  true, 'details', $row['details'], 40, true, false, false) ?>
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Downloadable Unit Outline') ?></b><br/>
											<span class="emphasis small"><?php echo __($guid, 'Available to most users.') ?></span>
											<?php if ($row['attachment'] != '') { ?>
												<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
											<?php } ?>
										</td>
										<td class="right">
											<?php
                                            if ($row['attachment'] != '') {
                                                echo __($guid, 'Current attachment:')." <a href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['attachment']."'>".$row['attachment'].'</a><br/><br/>';
                                            }
                            				?>
											<input type="file" name="file" id="file"><br/><br/>
											<?php
                                            echo getMaxUpload($guid);

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
                                </table>


                                <h3 class='bigTop'><?php echo __($guid, 'Advanced Options') ?></h3>
                                <table class='smallIntBorder fullWidth' cellspacing='0'>
                                    <tr class='break'>
                                        <td colspan=2>
                                            <h3><?php echo __($guid, 'Outcomes') ?></h3>
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
                                            <p><?php echo __($guid, 'Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.') ?></p>
                                            <div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
                                                <?php
                                                $i = 1;
                                                $usedArrayFill = '';
                                                try {
                                                    $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                                                    $sqlBlocks = "SELECT gibbonUnitOutcome.*, scope, name, category FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
                                                    $resultBlocks = $connection2->prepare($sqlBlocks);
                                                    $resultBlocks->execute($dataBlocks);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($resultBlocks->rowCount() < 1) {
                                                    echo "<div id='outcomeOuter0'>";
                                                    echo "<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>".__($guid, 'Outcomes listed here...').'</div>';
                                                    echo '</div>';
                                                } else {
                                                    while ($rowBlocks = $resultBlocks->fetch()) {
                                                        makeBlockOutcome($guid, $i, 'outcome', $rowBlocks['gibbonOutcomeID'],  $rowBlocks['name'],  $rowBlocks['category'], $rowBlocks['content'], '', true, $allowOutcomeEditing);
                                                        $usedArrayFill .= '"'.$rowBlocks['gibbonOutcomeID'].'",';
                                                        ++$i;
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div style='width: 100%; padding: 0px 0px 0px 0px'>
                                                <div class="ui-state-default_dud" style='padding: 0px; min-height: 50px'>
                                                    <table class='blank' cellspacing='0' style='width: 100%'>
                                                        <tr>
                                                            <td style='width: 50%'>
                                                                <script type="text/javascript">
                                                                    var outcomeCount=<?php echo $i ?>;
                                                                </script>
                                                                <select class='all' id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
                                                                    <option class='all' value='0'><?php echo __($guid, 'Choose an outcome to add it to this unit') ?></option>
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
                                                                            echo "<option class='$currentCategory' value='0'>".__($guid, 'Choose an outcome to add it to this unit').'</option>';
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
                                                                            echo "<option class='$currentCategory' value='0'>".__($guid, 'Choose an outcome to add it to this unit').'</option>';
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
                                                                    var <?php echo $type ?>Used=new Array(<?php echo substr($usedArrayFill, 0, -1) ?>);
                                                                    var <?php echo $type ?>UsedCount=<?php echo $type ?>Used.length ;

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
                                    <tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Smart Blocks') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<p>
												<?php echo __($guid, 'Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.') ?>
											</p>

											<style>
												#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
												#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
												div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
												html>body #sortable li { min-height: 58px; line-height: 1.2em; }
												#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
											</style>
											<script>
												$(function() {
													$( "#sortable" ).sortable({
														placeholder: "ui-state-highlight",
														axis: 'y'
													});
												});
											</script>


											<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px'>
												<?php
                                                try {
                                                    $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                                                    $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                                                    $resultBlocks = $connection2->prepare($sqlBlocks);
                                                    $resultBlocks->execute($dataBlocks);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
												try {
													$dataOutcomes = array('gibbonUnitID' => $gibbonUnitID);
													$sqlOutcomes = "SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.name, gibbonOutcome.category, scope, gibbonDepartment.name AS department FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
													$resultOutcomes = $connection2->prepare($sqlOutcomes);
													$resultOutcomes->execute($dataOutcomes);
												} catch (PDOException $e) {
													echo "<div class='error'>".$e->getMessage().'</div>';
												}
												$unitOutcomes = $resultOutcomes->fetchall();
												$i = 1;
												while ($rowBlocks = $resultBlocks->fetch()) {
													makeBlock($guid, $connection2, $i, 'masterEdit', $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], 'N', $rowBlocks['gibbonUnitBlockID'], '', $rowBlocks['teachersNotes'], true, $unitOutcomes, $rowBlocks['gibbonOutcomeIDList']);
													++$i;
												}
												?>
											</div>
											<div style='width: 100%; padding: 0px 0px 0px 0px'>
												<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
													<table class='blank' cellspacing='0' style='width: 100%'>
														<tr>
															<td style='width: 50%'>
																<script type="text/javascript">
																	var count=<?php echo $resultBlocks->rowCount() + 1 ?> ;
																	$(document).ready(function(){
																		$("#new").click(function(){
																			$("#sortable").append('<div id=\'blockOuter' + count + '\' class=\'blockOuter\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php echo $_SESSION[$guid]['absoluteURL'] ?>/themes<?php echo '/'.$_SESSION[$guid]['gibbonThemeName'].'/' ?>img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
																			$("#blockOuter" + count).load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Planner/units_add_blockAjax.php","id=" + count + "&mode=masterEdit") ;
																			count++ ;
																		 });
																	});
																</script>
																<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php echo __($guid, 'Click to create a new block') ?></div><br/>
															</td>
														</tr>
													</table>
												</div>
											</div>
										</td>
									</tr>


                                    <tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Miscellaneous Settings') ?></h3>
										</td>
									</tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __($guid, 'License') ?></b><br/>
                                            <span class="emphasis small"><?php echo __($guid, 'Under what conditions can this work be reused?'); ?></span>
                                        </td>
                                        <td class="right">
                                            <select name="license" id="license" class="standardWidth">
                                                <option <?php if ($row['license'] == '') { echo 'selected '; }?>value=""></option>
                                                <option <?php if ($row['license'] == 'Copyright') { echo 'selected '; }?>value="Copyright"><?php echo __($guid, 'Copyright') ?></option>
                                                <option <?php if ($row['license'] == 'Creative Commons BY') { echo 'selected '; }?>value="Creative Commons BY"><?php echo __($guid, 'Creative Commons BY') ?></option>
                                                <option <?php if ($row['license'] == 'Creative Commons BY-SA') { echo 'selected '; }?>value="Creative Commons BY-SA"><?php echo __($guid, 'Creative Commons BY-SA') ?></option>
                                                <option <?php if ($row['license'] == 'Creative Commons BY-SA-NC') { echo 'selected '; }?>value="Creative Commons BY-SA-NC"><?php echo __($guid, 'Creative Commons BY-SA-NC') ?></option>
                                                <option <?php if ($row['license'] == 'Public Domain') { echo 'selected '; }?>value="Public Domain"><?php echo __($guid, 'Public Domain') ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                    $makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
                                    if ($makeUnitsPublic == 'Y') {
                                        ?>
                                        <tr>
                                            <td>
                                                <b><?php echo __($guid, 'Shared Publically') ?> * </b><br/>
                                                <span class="emphasis small"><?php echo __($guid, 'Share this unit via the public listing of units? Useful for building MOOCS.');?></span>
                                            </td>
                                            <td class="right">
                                                <input <?php if ($row['sharedPublic'] == 'Y') { echo 'checked'; } ?> type="radio" name="sharedPublic" value="Y" /> <?php echo __($guid, 'Yes') ?>
                                                <input <?php if ($row['sharedPublic'] == 'N' or $row['sharedPublic'] == '') { echo 'checked'; } ?> type="radio" name="sharedPublic" value="N" /> <?php echo __($guid, 'No') ?>
                                            </td>
                                        </tr>
                                        <?php

                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <b><?php echo __($guid, 'Embeddable') ?> *</b><br/>
                                            <span class="emphasis small"><?php echo __($guid, 'Can this unit be embedded and shared publicly in other websites?') ?></span>
                                        </td>
                                        <td class="right">
                                            <input <?php if ($row['embeddable'] == 'Y') { echo 'checked'; } ?> type="radio" id="embeddable" name="embeddable" class="embeddable" value="Y" /> <?php echo __($guid, 'Yes') ?>
                                            <input <?php if ($row['embeddable'] == 'N') { echo 'checked'; } ?> type="radio" id="embeddable" name="embeddable" class="embeddable" value="N" /> <?php echo __($guid, 'No') ?>
                                        </td>
                                    </tr>
                                    <script type="text/javascript">
                                        $(document).ready(function(){
                                            <?php
                                            if ($row['embeddable'] == 'Y') {
                                                echo '$("#embeddableRow").slideDown("fast", $("#embeddableRow").css("display","table-row"));';
                                            }
                                            ?>
                                            $(".embeddable").click(function(){
                                                if ($('input[name=embeddable]:checked').val()=="Y" ) {
                                                    $("#embeddableRow").slideDown("fast", $("#embeddableRow").css("display","table-row"));
                                                } else {
                                                    $("#embeddableRow").css("display","none");

                                                }
                                             });
                                        });
                                    </script>

                                    <tr id="embeddableRow" <?php if ($row['embeddable'] == 'N') { echo "style='display: none'";} ?>>
                                        <td>
                                            <b><?php echo __($guid, 'Embed Code') ?></b><br/>
                                            <span class="emphasis small"><?php echo __($guid, 'Copy and paste this HTML code into the target website.') ?></span>
                                        </td>
                                        <td class="right">
                                            <textarea readonly name='embedCode' id='embedCode' rows=5 style='width: 300px'><?php echo "<iframe style='border: none; width: 620px; height: 800px; overflow-x: hidden; overflow-y: scroll' src=\"".$_SESSION[$guid]['absoluteURL']."/modules/Planner/units_embed.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&themeName=".$_SESSION[$guid]['gibbonThemeName'].'&title=false"></iframe>' ?></textarea>
                                        </td>
                                    </tr>

									<tr>
										<td>
											<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
										</td>
										<td class="right">
											<input name="classCount" id="classCount" value="<?php echo $classCount ?>" type="hidden">
											<input id="submit" type="submit" value="Submit">
										</td>
									</tr>
								</table>
							</form>
							<?php

                        }
                    }
                }
            }
            //IF UNIT DOES CONTAIN HYPHEN, IT IS A HOOKED UNIT
            else {
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
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();
                    $yearName = $row['name'];
                    $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'];

                    //Check if unit specified
                    if ($gibbonHookIDToken == '') {
                        echo "<div class='error'>";
                        echo __($guid, 'You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        try {
                            $dataHooks = array('gibbonHookID' => $gibbonHookIDToken);
                            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name";
                            $resultHooks = $connection2->prepare($sqlHooks);
                            $resultHooks->execute($dataHooks);
                        } catch (PDOException $e) {
                        }
                        if ($resultHooks->rowCount() == 1) {
                            $rowHooks = $resultHooks->fetch();
                            $hookOptions = unserialize($rowHooks['options']);
                            if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                                try {
                                    $data = array('unitIDField' => $gibbonUnitIDToken);
                                    $sql = 'SELECT '.$hookOptions['unitTable'].'.*, gibbonCourse.nameShort FROM '.$hookOptions['unitTable'].' JOIN gibbonCourse ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitCourseIDField'].'=gibbonCourse.gibbonCourseID) WHERE '.$hookOptions['unitIDField'].'=:unitIDField';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                }
                            }
                        }
                        if ($result->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'The specified record cannot be found.');
                            echo '</div>';
                        } else {
                            //Let's go!
                            $row = $result->fetch();
                            ?>
							<table cellspacing='0' style="width: 100%">
								<tr><td style="width: 30%"></td><td></td></tr>
								<tr>
									<td colspan=2>
										<h3>Unit Basics</h3>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'School Year') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="yearName" id="yearName" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Course') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="courseName" id="courseName" maxlength=20 value="<?php echo $row['nameShort'] ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Name') ?> *</b><br/>
										<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=40 value="<?php echo $row['name'] ?>" type="text" class="standardWidth">
									</td>
								</tr>

								<tr>
									<td colspan=2>
										<h3><?php echo __($guid, 'Classes') ?></h3>
										<p><?php echo __($guid, 'Select classes which will study this unit.') ?></p>
									</td>
								</tr>
								<tr>
									<td colspan=2>
										<?php
                                        $classCount = 0;
										try {
											$dataClass = array('unitIDField' => $row[$hookOptions['unitIDField']], 'gibbonCourseID' => $row[$hookOptions['unitCourseIDField']]);
											$sqlClass = 'SELECT gibbonCourseClass.nameShort AS className, gibbonCourse.nameShort AS courseName, '.$hookOptions['classLinkTable'].'.* FROM '.$hookOptions['classLinkTable'].' JOIN '.$hookOptions['unitTable'].' ON ('.$hookOptions['classLinkTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].') JOIN gibbonCourseClass ON ('.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldClass'].'=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE '.$hookOptions['classLinkTable'].'.'.$hookOptions['unitIDField'].'=:unitIDField AND '.$hookOptions['unitTable'].'.'.$hookOptions['unitCourseIDField'].'=:gibbonCourseID ORDER BY courseName, className';
											$resultClass = $connection2->prepare($sqlClass);
											$resultClass->execute($dataClass);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}

										if ($resultClass->rowCount() < 1) {
											echo "<div class='error'>";
											echo __($guid, 'There are no records to display.');
											echo '</div>';
										} else {
											echo "<table cellspacing='0' style='width: 100%'>";
											echo "<tr class='head'>";
											echo '<th>';
											echo __($guid, 'Class');
											echo '</th>';
											echo '<th>';
											echo __($guid, 'Running')."<br/><span style='font-size: 80%'>".__($guid, 'Is class studying this unit?').'</span>';
											echo '</th>';
											echo '<th>';
											echo __($guid, 'First Lesson')."<br/><span style='font-size: 80%'>";
											if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
												echo 'dd/mm/yyyy';
											} else {
												echo $_SESSION[$guid]['i18n']['dateFormat'];
											};
											echo '</span>';
											echo '</th>';
											echo '<th>';
											echo __($guid, 'Actions');
											echo '</th>';
											echo '</tr>';

											$count = 0;
											$rowNum = 'odd';

											while ($rowClass = $resultClass->fetch()) {
												if ($count % 2 == 0) {
													$rowNum = 'even';
												} else {
													$rowNum = 'odd';
												}
												++$count;

												//COLOR ROW BY STATUS!
												echo "<tr class=$rowNum>";
												echo '<td>';
												echo $rowClass['courseName'].'.'.$rowClass['className'].'</a>';
												echo '</td>';
												echo '<td>';
												echo 'Y';
												echo '</td>';
												echo '<td>';
												try {
													$dataDate = array('gibbonCourseClassID' => $rowClass['gibbonCourseClassID'], 'gibbonHookID' => $gibbonHookIDToken, 'gibbonUnitID' => $gibbonUnitIDToken);
													$sqlDate = 'SELECT date FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonHookID=:gibbonHookID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart';
													$resultDate = $connection2->prepare($sqlDate);
													$resultDate->execute($dataDate);
												} catch (PDOException $e) {
												}
												if ($resultDate->rowCount() < 1) {
													echo '<i>'.__($guid, 'There are no records to display.').'</i>';
												} else {
													$rowDate = $resultDate->fetch();
													echo dateConvertBack($guid, $rowDate['date']);
												}
												echo '</td>';
												echo '<td>';
												if ($resultDate->rowCount() < 1) {
													echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_deploy.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass[$hookOptions['classLinkIDField']]."'><img title='Edit Unit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
												} else {
													echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_working.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass[$hookOptions['classLinkIDField']]."'><img title='Edit Unit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
												}
												echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&viewBy=class'><img style='margin-top: 3px' title='".__($guid, 'View Planner')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
												echo '</td>';
												echo '</tr>';
												++$classCount;
											}
											echo '</table>';
										}
										?>
									</td>
								</tr>
							</table>
							<?php

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
