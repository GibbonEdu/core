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

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonExternalAssessmentStudentID = $_GET['gibbonExternalAssessmentStudentID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';

    $page->breadcrumbs
        ->add(__('View All Assessments'), 'externalAssessment.php')
        ->add(__('Student Details'), 'externalAssessment_details.php', ['gibbonPersonID' => $gibbonPersonID])
        ->add(__('Edit Assessment'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    //Check if school year specified
    if ($gibbonExternalAssessmentStudentID == '' or $gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
            $sql = 'SELECT gibbonExternalAssessmentStudent.*, gibbonExternalAssessment.name AS assessment, gibbonExternalAssessment.allowFileUpload FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>".__('Back').'</a>';
                echo '</div>';
            }
            
            //Check for all fields
            
                $dataCheck = array('gibbonExternalAssessmentID' => $values['gibbonExternalAssessmentID']);
                $sqlCheck = 'SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
                $resultCheck = $connection2->prepare($sqlCheck);
                $resultCheck->execute($dataCheck);

            while ($rowCheck = $resultCheck->fetch()) {
                
                    $dataCheck2 = array('gibbonExternalAssessmentFieldID' => $rowCheck['gibbonExternalAssessmentFieldID'], 'gibbonExternalAssessmentStudentID' => $values['gibbonExternalAssessmentStudentID']);
                    $sqlCheck2 = 'SELECT * FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
                    $resultCheck2 = $connection2->prepare($sqlCheck2);
                    $resultCheck2->execute($dataCheck2);

                if ($resultCheck2->rowCount() < 1) {
                    
                        $dataCheck3 = array('gibbonExternalAssessmentStudentID' => $values['gibbonExternalAssessmentStudentID'], 'gibbonExternalAssessmentFieldID' => $rowCheck['gibbonExternalAssessmentFieldID']);
                        $sqlCheck3 = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
                        $resultCheck3 = $connection2->prepare($sqlCheck3);
                        $resultCheck3->execute($dataCheck3);
                }
            }
			
            $form = Form::create('editAssessment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/externalAssessment_manage_details_editProcess.php?search='.$search.'&allStudents='.$allStudents);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
            $form->addHiddenValue('gibbonExternalAssessmentStudentID', $gibbonExternalAssessmentStudentID);
            
            $row = $form->addRow();
                $row->addLabel('name', __('Assessment Type'));
                $row->addTextField('name')->required()->readOnly()->setValue(__($values['assessment']));

            $row = $form->addRow();
                $row->addLabel('date', __('Date'));
                $row->addDate('date')->required()->loadFrom($values);

            if ($values['allowFileUpload'] == 'Y') {
                $row = $form->addRow();
                $row->addLabel('file', __('Upload File'))->description(__('Use this to attach raw data, graphical summary, etc.'));
                $row->addFileUpload('file')->setAttachment('attachment', $_SESSION[$guid]['absoluteURL'], $values['attachment']);
            }

            
                $dataField = array('gibbonExternalAssessmentID' => $values['gibbonExternalAssessmentID'], 'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
                $sqlField = 'SELECT category, gibbonExternalAssessmentStudentEntryID, gibbonExternalAssessmentField.*, gibbonScale.usage, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) LEFT JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order';
                $resultField = $connection2->prepare($sqlField);
                $resultField->execute($dataField);

            if ($resultField->rowCount() <= 0) {
                $form->addRow()->addAlert(__('There are no fields in this assessment.'), 'warning');
            } else {
                $fieldGroup = $resultField->fetchAll(\PDO::FETCH_GROUP);
                $count = 0;

                foreach ($fieldGroup as $category => $fields) {
                    $categoryName = (strpos($category, '_') !== false)? substr($category, (strpos($category, '_') + 1)) : $category;

                    $row = $form->addRow();
                        $row->addHeading($categoryName);
                        $row->addContent(__('Grade'))->wrap('<b>', '</b>')->setClass('right');

                    foreach ($fields as $field) {
                        $form->addHiddenValue($count.'-gibbonExternalAssessmentStudentEntryID', $field['gibbonExternalAssessmentStudentEntryID']);
                        $gradeScale = renderGradeScaleSelect($connection2, $guid, $field['gibbonScaleID'], $count.'-gibbonScaleGradeID', 'id', false, '150', 'id', $field['gibbonScaleGradeID']);

                        $row = $form->addRow();
                            $row->addLabel($count.'-gibbonScaleGradeID', $field['name'])->setTitle($field['usage']);
                            $row->addContent($gradeScale);

                        $count++;
                    }
                }

                $form->addHiddenValue('count', $count);
            }
            
            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
            
            echo $form->getOutput();
        }
    }
}
