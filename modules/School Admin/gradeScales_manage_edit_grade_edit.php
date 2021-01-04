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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonScaleGradeID = $_GET['gibbonScaleGradeID'] ?? '';
    $gibbonScaleID = $_GET['gibbonScaleID'] ?? '';
    if ($gibbonScaleGradeID == '' or $gibbonScaleID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonScaleID' => $gibbonScaleID, 'gibbonScaleGradeID' => $gibbonScaleGradeID);
            $sql = 'SELECT gibbonScaleGrade.*, gibbonScale.name AS name FROM gibbonScale JOIN gibbonScaleGrade ON (gibbonScale.gibbonScaleID=gibbonScaleGrade.gibbonScaleID) WHERE gibbonScaleGrade.gibbonScaleID=:gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=:gibbonScaleGradeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Manage Grade Scales'), 'gradeScales_manage.php')
                ->add(__('Edit Grade Scale'), 'gradeScales_manage_edit.php', ['gibbonScaleID' => $gibbonScaleID])
                ->add(__('Edit Grade'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = Form::create('gradeScaleGradeEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_editProcess.php?gibbonScaleGradeID='.$gibbonScaleGradeID.'&gibbonScaleID='.$gibbonScaleID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonScaleID', $gibbonScaleID);

            $row = $form->addRow();
                $row->addLabel('name', __('Grade Scale'));
                $row->addTextField('name')->readonly()->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('value', __('Value'))->description(__('Must be unique for this grade scale.'));
                $row->addTextField('value')->required()->maxLength(10);

            $row = $form->addRow();
                $row->addLabel('descriptor', __('Descriptor'));
                $row->addTextField('descriptor')->required()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique for this grade scale.'));
                $row->addNumber('sequenceNumber')->required()->maxLength(5);

            $row = $form->addRow();
                $row->addLabel('isDefault', __('Is Default?'))->description(__('Preselects this option when using this grade scale in appropriate contexts.'));
                $row->addYesNo('isDefault')->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
