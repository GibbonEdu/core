<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonExternalAssessmentFieldID and gibbonExternalAssessmentID specified
    $gibbonExternalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'] ?? '';
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'] ?? '';
    if ($gibbonExternalAssessmentFieldID == '' or $gibbonExternalAssessmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID);
            $sql = 'SELECT gibbonExternalAssessmentField.*, gibbonExternalAssessment.name AS assessmentName FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessment.gibbonExternalAssessmentID=gibbonExternalAssessmentField.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();
            $values['gibbonYearGroupIDList'] = explode(',', $values['gibbonYearGroupIDList']);

            $page->breadcrumbs
                ->add(__('Manage External Assessments'), 'externalAssessments_manage.php')
                ->add(__('Edit External Assessment'), 'externalAssessments_manage_edit.php', ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID])
                ->add(__('Edit Field'));

            $form = Form::create('externalAssessmentField', $session->get('absoluteURL').'/modules/'.$session->get('module').'/externalAssessments_manage_edit_field_editProcess.php?gibbonExternalAssessmentFieldID='.$gibbonExternalAssessmentFieldID.'&gibbonExternalAssessmentID='.$gibbonExternalAssessmentID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

            $row = $form->addRow();
                $row->addLabel('assessmentName', __('External Assessment'));
                $row->addTextField('assessmentName')->readonly()->setValue($values['assessmentName']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addTextField('category')->required()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('order', __('Order'))->description(__('Order in which fields appear within category<br/>Should be unique for this category.'));
                $row->addNumber('order')->required()->maxLength(4);

            $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE (active='Y') ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('gibbonScaleID', __('Grade Scale'))->description(__('Grade scale used to control values that can be assigned.'));
                $row->addSelect('gibbonScaleID')->fromQuery($pdo, $sql)->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('yearGroups', __('Year Groups'))->description(__('Year groups to which this field is relevant.'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
