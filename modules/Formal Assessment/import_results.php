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

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/import_results.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Import External Assessments').'</div>';
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
        echo '<h2>';
        echo __('Step 1 - Select CSV Files');
        echo '</h2>';
        echo '<p>';
        echo __('This page allows you to import external assessment results from a CSV file. The import includes one row for each student result. The system will match assessments by type and date, updating any matching results, whilst creating new results not already existing in the system.');
        echo '</p>';

        $form = Form::create('importExternalResults', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_results.php&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('file', __('CSV File'))->description(__('See Notes below for specification.'));
            $row->addFileUpload('file')->isRequired()->accepts('.csv');

        $row = $form->addRow();
            $row->addLabel('fieldDelimiter', __('Field Delimiter'));
            $row->addTextField('fieldDelimiter')->isRequired()->maxLength(1)->setValue(',');

        $row = $form->addRow();
            $row->addLabel('stringEnclosure', __('String Enclosure'));
            $row->addTextField('stringEnclosure')->isRequired()->maxLength(1)->setValue('"');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
        ?>

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
					<li><b><?php echo __($guid, 'Assessment Name') ?>* </b> - <?php echo __($guid, 'Must match value of gibbonExternalAssessment.name in database.') ?></li>
					<li><b><?php echo __($guid, 'Official Name') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonPerson.officialName in database,') ?></li>
					<li><b><?php echo __($guid, 'Assessment Date') ?> *</b> - <?php echo __($guid, 'dd/mm/yyyy') ?></li>
					<li><b><?php echo __($guid, 'Field Name') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonExternalAssessmentField.name in database.') ?></li>
					<li><b><?php echo __($guid, 'Field Name Category') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonExternalAssessmentField.category in database, less [numeric_] prefix.') ?></li>
					<li><b><?php echo __($guid, 'Result') ?> *</b> - <?php echo __($guid, 'Must match value of gibbonScaleGrade.value in database.') ?></li>
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
                $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonExternalAssessment WRITE, gibbonExternalAssessmentField WRITE, gibbonExternalAssessmentStudent WRITE, gibbonExternalAssessmentStudentEntry WRITE, gibbonScale WRITE, gibbonScaleGrade WRITE';
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
                        if ($data[0] != '' and $data[1] != '' and $data[2] != '' and $data[3] != '' and $data[4] != '' and $data[5] != '') {
                            $results[$resultSuccessCount]['assessmentName'] = '';
                            if (isset($data[0])) {
                                $results[$resultSuccessCount]['assessmentName'] = $data[0];
                            }
                            $results[$resultSuccessCount]['officialName'] = '';
                            if (isset($data[1])) {
                                $results[$resultSuccessCount]['officialName'] = $data[1];
                            }
                            $results[$resultSuccessCount]['assessmentDate'] = '';
                            if (isset($data[2])) {
                                $results[$resultSuccessCount]['assessmentDate'] = $data[2];
                            }
                            $results[$resultSuccessCount]['fieldName'] = '';
                            if (isset($data[3])) {
                                $results[$resultSuccessCount]['fieldName'] = $data[3];
                            }
                            $results[$resultSuccessCount]['fieldCategory'] = '';
                            if (isset($data[4])) {
                                $results[$resultSuccessCount]['fieldCategory'] = $data[4];
                            }
                            $results[$resultSuccessCount]['result'] = '';
                            if (isset($data[5])) {
                                $results[$resultSuccessCount]['result'] = $data[5];
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

                    $users = array();
                    $assessments = array();
                    $fields = array();

                    //Scroll through all records
                    foreach ($results as $result) {

                        //Turn officialName into gibbonPersonID in a db-efficient manner
                        if (isset($users[$result['officialName']]) == false) {
                            try {
                                $dataUser = array('officialName' => $result['officialName']);
                                $sqlUser = 'SELECT gibbonPersonID FROM gibbonPerson WHERE officialName=:officialName';
                                $resultUser = $connection2->prepare($sqlUser);
                                $resultUser->execute($dataUser);
                            } catch (PDOException $e) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed due to a database error.');
                                echo '</div>';
                                $users[$result['officialName']] = null;
                            }
                            if ($resultUser->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'User with official name %1$s in import cannot be found.'), $result['officialName']);
                                echo '</div>';
                                $users[$result['officialName']] = null;
                            } else {
                                $rowUser = $resultUser->fetch();
                                $users[$result['officialName']] = $rowUser['gibbonPersonID'];
                            }
                        }

                        //If we have gibbonPersonID, move on
                        if ($users[$result['officialName']] != '') {

                            //Turn assessmentName into gibbonExternalAssessmentID in a db-efficient manner
                            if (isset($assessments[$result['assessmentName']]) == false) {
                                try {
                                    $dataAssessment = array('assessmentName' => $result['assessmentName']);
                                    $sqlAssessment = 'SELECT gibbonExternalAssessmentID FROM gibbonExternalAssessment WHERE name=:assessmentName';
                                    $resultAssessment = $connection2->prepare($sqlAssessment);
                                    $resultAssessment->execute($dataAssessment);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'Your request failed due to a database error.');
                                    echo '</div>';
                                    $assessments[$result['assessmentName']] = null;
                                }
                                if ($resultAssessment->rowCount() != 1) {
                                    echo "<div class='error'>";
                                    echo sprintf(__($guid, 'External Assessment with name %1$s in import cannot be found.'), $result['assessmentName']);
                                    echo '</div>';
                                    $assessments[$result['assessmentName']] = null;
                                } else {
                                    $rowAssessment = $resultAssessment->fetch();
                                    $assessments[$result['assessmentName']] = $rowAssessment['gibbonExternalAssessmentID'];
                                }
                            }

                            //If we have gibbonExternalAssessmentID, move on
                            if ($assessments[$result['assessmentName']] != '') {

                                //Turn fieldName into gibbonExternalAssessmentFieldID in a db-efficient manner
                                if (isset($fields[$result['fieldName'].$result['fieldCategory']]) == false) {
                                    //Check for existence of field in assessment
                                    try {
                                        $dataAssessmentField = array('gibbonExternalAssessmentID' => $assessments[$result['assessmentName']], 'name' => $result['fieldName'], 'category' => '%'.$result['fieldCategory']);
                                        $sqlAssessmentField = 'SELECT gibbonExternalAssessmentFieldID FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND name=:name AND category LIKE :category';
                                        $resultAssessmentField = $connection2->prepare($sqlAssessmentField);
                                        $resultAssessmentField->execute($dataAssessmentField);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Your request failed due to a database error.');
                                        echo '</div>';
                                        $fields[$result['fieldName'].$result['fieldCategory']] = null;
                                    }
                                    if ($resultAssessmentField->rowCount() != 1) {
                                        echo "<div class='error'>";
                                        echo sprintf(__($guid, 'External Assessment field with name %1$s in import cannot be found.'), $result['fieldName']);
                                        echo '</div>';
                                        $fields[$result['fieldName'].$result['fieldCategory']] = null;
                                    } else {
                                        $rowAssessmentField = $resultAssessmentField->fetch();
                                        $fields[$result['fieldName'].$result['fieldCategory']] = $rowAssessmentField['gibbonExternalAssessmentFieldID'];
                                    }
                                }

                                //If we have the field, we can proceed
                                if ($fields[$result['fieldName'].$result['fieldCategory']] != '') {
                                    //Check for record assessment for student
                                    try {
                                        $dataAssessmentStudent = array('gibbonExternalAssessmentID' => $assessments[$result['assessmentName']], 'gibbonPersonID' => $users[$result['officialName']], 'date' => dateConvert($guid, $result['assessmentDate']));
                                        $sqlAssessmentStudent = 'SELECT gibbonExternalAssessmentStudentID FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonPersonID=:gibbonPersonID AND date=:date';
                                        $resultAssessmentStudent = $connection2->prepare($sqlAssessmentStudent);
                                        $resultAssessmentStudent->execute($dataAssessmentStudent);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'Your request failed due to a database error.');
                                        echo '</div>';
                                    }
                                    if ($resultAssessmentStudent->rowCount() == 1) { //Assessment exists for this student
                                        $rowAssessmentStudent = $resultAssessmentStudent->fetch();

                                        //Check for field entry for student
                                        try {
                                            $dataAssessmentStudentField = array('gibbonExternalAssessmentStudentID' => $rowAssessmentStudent['gibbonExternalAssessmentStudentID'], 'gibbonExternalAssessmentFieldID' => $fields[$result['fieldName'].$result['fieldCategory']]);
                                            $sqlAssessmentStudentField = 'SELECT gibbonExternalAssessmentStudentEntryID FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
                                            $resultAssessmentStudentField = $connection2->prepare($sqlAssessmentStudentField);
                                            $resultAssessmentStudentField->execute($dataAssessmentStudentField);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'Your request failed due to a database error.');
                                            echo '</div>';
                                        }
                                        if ($resultAssessmentStudentField->rowCount() == 1) { //If exists, update
                                            $updateFail = false;
                                            $rowAssessmentStudentField = $resultAssessmentStudentField->fetch();
                                            try {
                                                //Grade
                                                $dataAssessmentStudentFieldUpdate = array('gibbonExternalAssessmentStudentEntryID' => $rowAssessmentStudentField['gibbonExternalAssessmentStudentEntryID'], 'result' => $result['result'], 'gibbonExternalAssessmentFieldID' => $fields[$result['fieldName'].$result['fieldCategory']]);
                                                $sqlAssessmentStudentFieldUpdate = 'UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID';
                                                $resultAssessmentStudentFieldUpdate = $connection2->prepare($sqlAssessmentStudentFieldUpdate);
                                                $resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>";
                                                echo __($guid, 'Your request failed due to a database error.');
                                                echo '</div>';
                                                $updateFail = true;
                                            }
                                            if ($updateFail == false) {
                                                echo "<div class='success'>";
                                                echo sprintf(__($guid, '%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result['assessmentName'], $result['fieldName'], $result['result'], $result['officialName']);
                                                echo '</div>';
                                            }
                                        } else { //If not, insert
                                            $insertFail = false;
                                            try {
                                                //Grade
                                                $dataAssessmentStudentFieldUpdate = array('gibbonExternalAssessmentStudentID' => $rowAssessmentStudent['gibbonExternalAssessmentStudentID'], 'gibbonExternalAssessmentFieldID1' => $fields[$result['fieldName'].$result['fieldCategory']], 'result' => $result['result'], 'gibbonExternalAssessmentFieldID2' => $fields[$result['fieldName'].$result['fieldCategory']]);
                                                $sqlAssessmentStudentFieldUpdate = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID1), gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID2';
                                                $resultAssessmentStudentFieldUpdate = $connection2->prepare($sqlAssessmentStudentFieldUpdate);
                                                $resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>";
                                                echo __($guid, 'Your request failed due to a database error.');
                                                echo '</div>';
                                                $insertFail = true;
                                            }
                                            if ($insertFail == false) {
                                                echo "<div class='success'>";
                                                echo sprintf(__($guid, '%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result['assessmentName'], $result['fieldName'], $result['result'], $result['officialName']);
                                                echo '</div>';
                                            }
                                        }
                                    } else { //Assessment does not exist for this student
                                        //Insert assessment
                                        $insertFail = false;
                                        try {
                                            $dataAssessmentStudentInsert = array('gibbonExternalAssessmentID' => $assessments[$result['assessmentName']], 'gibbonPersonID' => $users[$result['officialName']], 'date' => dateConvert($guid, $result['assessmentDate']));
                                            $sqlAssessmentStudentInsert = 'INSERT INTO gibbonExternalAssessmentStudent SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, gibbonPersonID=:gibbonPersonID, date=:date';
                                            $resultAssessmentStudentInsert = $connection2->prepare($sqlAssessmentStudentInsert);
                                            $resultAssessmentStudentInsert->execute($dataAssessmentStudentInsert);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>";
                                            echo __($guid, 'Your request failed due to a database error.');
                                            echo '</div>';
                                            $insertFail = true;
                                        }
                                        if ($insertFail == false) {
                                            $gibbonExternalAssessmentStudentID = $connection2->lastInsertID();

                                            //Insert field
                                            if ($gibbonExternalAssessmentStudentID == '') {
                                                echo "<div class='error'>";
                                                echo __($guid, 'Your request failed due to a database error.');
                                                echo '</div>';
                                            } else {
                                                try {
                                                    //Grade
                                                    $dataAssessmentStudentFieldUpdate = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID, 'gibbonExternalAssessmentFieldID1' => $fields[$result['fieldName'].$result['fieldCategory']], 'result' => $result['result'], 'gibbonExternalAssessmentFieldID2' => $fields[$result['fieldName'].$result['fieldCategory']]);
                                                    $sqlAssessmentStudentFieldUpdate = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID1), gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID2';
                                                    $resultAssessmentStudentFieldUpdate = $connection2->prepare($sqlAssessmentStudentFieldUpdate);
                                                    $resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>";
                                                    echo __($guid, 'Your request failed due to a database error.');
                                                    echo '</div>';
                                                    $insertFail = true;
                                                }
                                                if ($insertFail == false) {
                                                    echo "<div class='success'>";
                                                    echo sprintf(__($guid, '%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result['assessmentName'], $result['fieldName'], $result['result'], $result['officialName']);
                                                    echo '</div>';
                                                }
                                            }
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
