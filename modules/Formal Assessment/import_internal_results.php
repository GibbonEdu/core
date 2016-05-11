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

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/import_internal_results.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get alternative header names
    $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
    $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
    $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
    $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import Internal Assessments').'</div>';
    echo '</div>';

    $step = null;
    if (isset($_GET['step'])) {
        $step = $_GET['step'];
    }
    if ($step == '') {
        $step = 1;
    } elseif (($step != 1) and ($step != 2)) {
        $step = 1;
    }

    //STEP 1, SELECT TERM
    if ($step == 1) {
        ?>
		<h2>
			<?php echo __($guid, 'Step 1 - Select CSV Files') ?>
		</h2>
		<p>
			<?php echo __($guid, 'This page allows you to import interal assessment results from a CSV file into existing assessment columns. The system will match assessments by course name, class name and column name, updating any matching results, whilst creating new results not already existing in the system.') ?><br/>
		</p>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_internal_results.php&step=2' ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php echo __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars">
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Field Delimiter') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'String Enclosure') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>



		<h4>
			<?php echo __($guid, 'Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php echo __($guid, 'THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php echo __($guid, 'You may only submit CSV files.') ?></li>
			<li><?php echo __($guid, 'Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php echo __($guid, 'Your import can only include users whose status is set "Expected", "Full" or "Left" (e.g. all users).') ?></li>
			<li><?php echo __($guid, 'The submitted file must have the following fields in the following order (* denotes required field):') ?></li>
				<ol>
					<li><b><?php echo __($guid, 'Course Short Name') ?>* </b> - <?php echo __($guid, 'Must match value of gibbonCourse.nameShort in database.') ?></li>
                    <li><b><?php echo __($guid, 'Class Short Name') ?>* </b> - <?php echo __($guid, 'Must match value of gibbonCourseClass.nameShort in database.') ?></li>
                    <li><b><?php echo __($guid, 'Assessment Column Name') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonInternalAssessmentColumn.nam in database, and be unique for the class.') ?></li>
                    <li><b><?php echo __($guid, 'Student\'s Username') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonPerson.username in database,') ?></li>
                    <?php
                    if ($attainmentAlternativeName != '')
                        echo '<li><b>' . __($guid, '$attainmentAlternativeName Value') . ' *</b> - </li>' ;
                    else
                        echo '<li><b>' . __($guid, 'Attainment Value') . '</b></li>' ;
                    if ($effortAlternativeName != '')
                        echo '<li><b>' . __($guid, '$effortAlternativeName Value') . ' *</b> - </li>' ;
                    else
                        echo '<li><b>' . __($guid, 'Effort Value') . '</b></li>' ;
                    ?>
                </ol>
			</li>
			<li><?php echo __($guid, 'Do not include a header row in the CSV files.') ?></li>
		</ol>
	<?php

    } elseif ($step == 2) {
        ?>
		<h2>
			<?php echo __($guid, 'Step 2 - Data Check & Confirm') ?>
		</h2>
		<?php

        //Check file type
        if (($_FILES['file']['type'] != 'text/csv') and ($_FILES['file']['type'] != 'text/comma-separated-values') and ($_FILES['file']['type'] != 'text/x-comma-separated-values') and ($_FILES['file']['type'] != 'application/vnd.ms-excel') and ($_FILES['file']['type'] != 'application/csv')) {
            ?>
			<div class='error'>
				<?php echo sprintf(__($guid, 'Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
			</div>
			<?php

        } elseif (($_POST['fieldDelimiter'] == '') or ($_POST['stringEnclosure'] == '')) {
            ?>
			<div class='error'>
				<?php echo __($guid, 'Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
			</div>
			<?php

        } else {
            $proceed = true;

            //PREPARE TABLES
            echo '<h4>';
            echo __($guid, 'Prepare Database Tables');
            echo '</h4>';
            //Lock tables
            $lockFail = false;
            try {
                $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonInternalAssessmentColumn WRITE, gibbonInternalAssessmentEntry WRITE, gibbonScale WRITE, gibbonScaleGrade WRITE, gibbonCourse WRITE, gibbonCourseClass WRITE';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
                $lockFail = true;
                $proceed = false;
            }
            if ($lockFail == true) {
                echo "<div class='error'>";
                echo __($guid, 'The database could not be locked for use.');
                echo '</div>';
            } elseif ($lockFail == false) {
                echo "<div class='success'>";
                echo __($guid, 'The database was successfully locked.');
                echo '</div>';
            }

            if ($lockFail == false) {
                //READ IN DATA
                if ($proceed == true) {
                    echo '<h4>';
                    echo __($guid, 'File Import');
                    echo '</h4>';
                    $importFail = false;
                    $csvFile = $_FILES['file']['tmp_name'];
                    $handle = fopen($csvFile, 'r');
                    $results = array();
                    $resultCount = 0;
                    $resultSuccessCount = 0;

                    while (($data = fgetcsv($handle, 100000, stripslashes($_POST['fieldDelimiter']), stripslashes($_POST['stringEnclosure']))) !== false) {
                        if ($data[0] != '' and $data[1] != '' and $data[2] != '' and $data[3]) {
                            $results[$resultSuccessCount]['courseName'] = '';
                            if (isset($data[0])) {
                                $results[$resultSuccessCount]['courseName'] = $data[0];
                            }
                            $results[$resultSuccessCount]['className'] = '';
                            if (isset($data[1])) {
                                $results[$resultSuccessCount]['className'] = $data[1];
                            }
                            $results[$resultSuccessCount]['columnName'] = '';
                            if (isset($data[2])) {
                                $results[$resultSuccessCount]['columnName'] = $data[2];
                            }
                            $results[$resultSuccessCount]['username'] = '';
                            if (isset($data[3])) {
                                $results[$resultSuccessCount]['username'] = $data[3];
                            }
                            $results[$resultSuccessCount]['attainmentValue'] = '';
                            if (isset($data[4])) {
                                $results[$resultSuccessCount]['attainmentValue'] = $data[4];
                            }
                            $results[$resultSuccessCount]['effortValue'] = '';
                            if (isset($data[5])) {
                                $results[$resultSuccessCount]['effortValue'] = $data[5];
                            }
                            ++$resultSuccessCount;
                        } else {
                            echo "<div class='error'>";
                            echo sprintf(__($guid, 'User with official Name %1$s had some information malformations.'), $data[1]);
                            echo '</div>';
                        }
                        ++$resultCount;
                    }
                    fclose($handle);
                    if ($resultSuccessCount == 0) {
                        echo "<div class='error'>";
                        echo __($guid, 'No useful results were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($resultSuccessCount < $resultCount) {
                        echo "<div class='error'>";
                        echo __($guid, 'Some results could not be successfully read or used, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    } elseif ($resultSuccessCount == $resultCount) {
                        echo "<div class='success'>";
                        echo __($guid, 'All results could be read and used, so the import will proceed.');
                        echo '</div>';
                    } else {
                        echo "<div class='error'>";
                        echo __($guid, 'An unknown error occured, so the import will be aborted.');
                        echo '</div>';
                        $proceed = false;
                    }
                }

                if ($proceed == true) {
                    echo '<h4>';
                    echo __($guid, 'Results');
                    echo '</h4>';

                    //Cache descriptors
                    $descriptors = array() ;
                    $desciptorFail = FALSE;
                    try {
                        $dataDescriptor = array();
                        $sqlDescriptor = 'SELECT gibbonScaleID, value, descriptor FROM gibbonScaleGrade ORDER BY gibbonScaleGradeID, value';
                        $resultDescriptor = $connection2->prepare($sqlDescriptor);
                        $resultDescriptor->execute($dataDescriptor);
                    } catch (PDOException $e) {
                        $desciptorFail = TRUE;
                    }
                    while ($rowDescriptor = $resultDescriptor->fetch()) {
                        $desciptors[$rowDescriptor['gibbonScaleID'] . '-' . $rowDescriptor['value']] = $rowDescriptor['descriptor'] ;
                    }

                    if ($desciptorFail) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.') ;
                        echo '</div>';
                    }
                    else {
                        $users = array();
                        $assessments = array();
                        //Scroll through all records
                        foreach ($results as $result) {

                            //Turn username into gibbonPersonID in a db-efficient manner
                            if (isset($users[$result['username']]) == false) {
                                try {
                                    $dataUser = array('username' => $result['username']);
                                    $sqlUser = 'SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username';
                                    $resultUser = $connection2->prepare($sqlUser);
                                    $resultUser->execute($dataUser);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'Your request failed due to a database error.');
                                    echo '</div>';
                                    $users[$result['username']] = null;
                                }
                                if ($resultUser->rowCount() != 1) {
                                    echo "<div class='error'>";
                                    echo sprintf(__($guid, 'User with official name %1$s in import cannot be found.'), $result['username']);
                                    echo '</div>';
                                    $users[$result['username']] = null;
                                } else {
                                    $rowUser = $resultUser->fetch();
                                    $users[$result['username']] = $rowUser['gibbonPersonID'];
                                }
                            }

                            //If we have gibbonPersonID, move on
                            if ($users[$result['username']] != '') {

                                //Turn columnName into gibbonExternalAssessmentID in a db-efficient manner
                                if (isset($assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']]) == false) {
                                    try {
                                        $dataAssessment = array('columnName' => $result['columnName'], 'courseName' => $result['courseName'], 'className' => $result['className']);
                                        $sqlAssessment = 'SELECT gibbonInternalAssessmentColumnID, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, attainment, effort
                                            FROM gibbonInternalAssessmentColumn
                                                JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                                JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                            WHERE gibbonInternalAssessmentColumn.name=:columnName
                                                AND gibbonCourse.nameShort=:courseName
                                                AND gibbonCourseClass.nameShort=:className ';
                                        $resultAssessment = $connection2->prepare($sqlAssessment);
                                        $resultAssessment->execute($dataAssessment);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Your request failed due to a database error.') . $e->getMessage();
                                        echo '</div>';
                                        $assessments[$result['columnName']] = null;
                                    }
                                    if ($resultAssessment->rowCount() != 1) {
                                        echo "<div class='error'>";
                                        echo sprintf(__($guid, 'External Assessment with name %1$s in import cannot be found.'), $result['columnName']);
                                        echo '</div>';
                                        $assessments[$result['columnName']] = null;
                                    } else {
                                        $rowAssessment = $resultAssessment->fetch();
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']]=array() ;
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][0] = $rowAssessment['gibbonCourseID'];
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][1] = $rowAssessment['gibbonCourseClassID'];
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][2] = $rowAssessment['gibbonInternalAssessmentColumnID'];
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][3] = $rowAssessment['attainment'];
                                        $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][4] = $rowAssessment['effort'];
                                    }
                                }

                                //If we have gibbonExternalAssessmentID, , we can proceed
                                if (is_array($assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']])) {
                                    $gibbonPersonID = $users[$result['username']];
                                    $gibbonCourseID = $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][0] ;
                                    $gibbonCourseClassID = $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][1] ;
                                    $gibbonInternalAssessmentColumnID = $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][2] ;
                                    $attainment = $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][3] ;
                                    $effort = $assessments[$result['courseName'] . '.' . $result['className'] . '-' . $result['columnName']][4] ;

                                    //Check column for student
                                    try {
                                        $dataAssessmentStudent = array('gibbonPersonID'=>$gibbonPersonID, 'gibbonInternalAssessmentColumnID'=>$gibbonInternalAssessmentColumnID);
                                        $sqlAssessmentStudent = 'SELECT gibbonInternalAssessmentEntryID, gibbonScaleIDAttainment, gibbonScaleIDEffort
                                            FROM gibbonInternalAssessmentEntry
                                                JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID)
                                            WHERE gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID
                                                AND gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID
                                        ';
                                        $resultAssessmentStudent = $connection2->prepare($sqlAssessmentStudent);
                                        $resultAssessmentStudent->execute($dataAssessmentStudent);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Your request failed due to a database error.') ;
                                        echo '</div>';
                                    }

                                    //Set values for attainment and effortValue
                                    $attainmentValue = null ;
                                    $attainmentDescriptor = null ;
                                    if ($attainment == 'Y') {
                                        if ($result['attainmentValue']!='NULL')
                                            $attainmentValue = $result['attainmentValue'];
                                        if (isset($desciptors[$rowAssessmentStudent['gibbonScaleIDAttainment'] . '-' . $result['attainmentValue']]))
                                            $attainmentDescriptor = $desciptors[$rowAssessmentStudent['gibbonScaleIDAttainment'] . '-' . $result['attainmentValue']] ;
                                        else
                                            $attainmentDescriptor = $attainmentValue;
                                    }

                                    $effortValue = null ;
                                    $effortDescriptor = null ;
                                    if ($effort == 'Y') {
                                        if ($result['effortValue']!='NULL')
                                            $effortValue = $result['effortValue'];
                                        if (isset($desciptors[$rowAssessmentStudent['gibbonScaleIDEffort'] . '-' . $result['effortValue']]))
                                            $effortDescriptor = $desciptors[$rowAssessmentStudent['gibbonScaleIDEffort'] . '-' . $result['effortValue']];
                                        else {
                                            $effortDescriptor = $effortValue;
                                        }
                                    }

                                    if ($resultAssessmentStudent->rowCount() == 1) { //Assessment exists for this student
                                        //Row exists so update it
                                        $rowAssessmentStudent = $resultAssessmentStudent->fetch();

                                        $insertFail = false;
                                        try {
                                            $dataAssessmentStudentUpdate = array('gibbonInternalAssessmentEntryID'=>$rowAssessmentStudent['gibbonInternalAssessmentEntryID'], 'attainmentValue'=>$attainmentValue, 'attainmentDescriptor'=>$attainmentDescriptor, 'effortValue'=>$effortValue, 'effortDescriptor'=>$effortDescriptor);
                                            $sqlAssessmentStudentUpdate = 'UPDATE gibbonInternalAssessmentEntry
                                                SET attainmentValue=:attainmentValue,
                                                    attainmentDescriptor=:attainmentDescriptor,
                                                    effortValue=:effortValue,
                                                    effortDescriptor=:effortDescriptor
                                                WHERE gibbonInternalAssessmentEntryID=:gibbonInternalAssessmentEntryID';
                                            $resultAssessmentStudentUpdate = $connection2->prepare($sqlAssessmentStudentUpdate);
                                            $resultAssessmentStudentUpdate->execute($dataAssessmentStudentUpdate);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'Your request failed due to a database error.') . $e->getMessage() ;
                                            echo '</div>';
                                            $insertFail = true ;
                                        }

                                        if ($insertFail == false) {
                                            echo "<div class='success'>";
                                            echo sprintf(__($guid, '%1$s.%2$s results for assessment %3$s was successfully recorded for %4$s.'), $result['courseName'], $result['className'], $result['columnName'], $result['username']);
                                            echo '</div>';
                                        }
                                    }
                                    else {
                                        //Row does not exist, so create a new row
                                        $insertFail = false ;
                                        try {
                                            $dataAssessmentStudentUpdate = array('gibbonInternalAssessmentColumnID'=>$gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent'=>$gibbonPersonID, 'attainmentValue'=>$attainmentValue, 'attainmentDescriptor'=>$attainmentDescriptor, 'effortValue'=>$effortValue, 'effortDescriptor'=>$effortDescriptor, 'gibbonPersonIDLastEdit'=>$_SESSION[$guid]['gibbonPersonID']);
                                            $sqlAssessmentStudentUpdate = 'INSERT INTO gibbonInternalAssessmentEntry
                                                SET
                                                    gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID,
                                                    gibbonPersonIDStudent=:gibbonPersonIDStudent,
                                                    attainmentValue=:attainmentValue,
                                                    attainmentDescriptor=:attainmentDescriptor,
                                                    effortValue=:effortValue,
                                                    effortDescriptor=:effortDescriptor,
                                                    comment=NULL,
                                                    response=NULL,
                                                    gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit
                                                ';
                                            $resultAssessmentStudentUpdate = $connection2->prepare($sqlAssessmentStudentUpdate);
                                            $resultAssessmentStudentUpdate->execute($dataAssessmentStudentUpdate);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'Your request failed due to a database error.') . $e->getMessage() ;
                                            echo '</div>';
                                            $insertFail = true ;
                                        }

                                        if ($insertFail == false) {
                                            echo "<div class='success'>";
                                            echo sprintf(__($guid, '%1$s.%2$s results for assessment %3$s was successfully recorded for %4$s.'), $result['courseName'], $result['className'], $result['columnName'], $result['username']);
                                            echo '</div>';
                                        }
                                    }

                                }
                            }
                        }
                    }
                }

                //UNLOCK TABLES
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }
            }
        }
    }
}
?>
