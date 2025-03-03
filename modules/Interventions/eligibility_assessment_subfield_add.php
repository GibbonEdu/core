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
use Gibbon\Module\Interventions\Domain\INEligibilityAssessmentTypeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_subfield_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonINEligibilityAssessmentTypeID = $_GET['gibbonINEligibilityAssessmentTypeID'] ?? '';
    
    if (empty($gibbonINEligibilityAssessmentTypeID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }
    
    // Get assessment type details
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    $assessmentType = $assessmentTypeGateway->getByID($gibbonINEligibilityAssessmentTypeID);
    
    if (empty($assessmentType)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    $page->breadcrumbs
        ->add(__('Manage Assessment Types'), 'eligibility_assessment_types_manage.php')
        ->add(__('Edit Assessment Type'), 'eligibility_assessment_types_edit.php', ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID])
        ->add(__('Add Subfield'));
    
    // Get the next sequence number
    $sql = "SELECT MAX(sequenceNumber) as maxSequence FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
    $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID]);
    $maxSequence = $result->rowCount() > 0 ? $result->fetch()['maxSequence'] : 0;
    $nextSequence = $maxSequence + 1;
    
    $form = Form::create('addSubfield', $session->get('absoluteURL').'/modules/Interventions/eligibility_assessment_subfield_addProcess.php');
    $form->setTitle(__('Add Assessment Subfield'));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonINEligibilityAssessmentTypeID', $gibbonINEligibilityAssessmentTypeID);
    
    $row = $form->addRow();
        $row->addLabel('assessmentTypeName', __('Assessment Type'));
        $row->addTextField('assessmentTypeName')->setValue($assessmentType['name'])->readonly();
    
    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Name of the subfield to be assessed'));
        $row->addTextField('name')->required()->maxLength(100);
    
    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Detailed explanation of what this subfield assesses'));
        $row->addTextArea('description')->setRows(3);
    
    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Order in which this subfield appears'));
        $row->addNumber('sequenceNumber')->required()->setValue($nextSequence)->minimum(1);
    
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required()->selected('Y');
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}
