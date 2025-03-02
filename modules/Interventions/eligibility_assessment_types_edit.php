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
use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_types_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonINEligibilityAssessmentTypeID = $_GET['gibbonINEligibilityAssessmentTypeID'] ?? '';
    
    if (empty($gibbonINEligibilityAssessmentTypeID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }
    
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    $values = $assessmentTypeGateway->getByID($gibbonINEligibilityAssessmentTypeID);
    
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    $page->breadcrumbs
        ->add(__('Manage Assessment Types'), 'eligibility_assessment_types_manage.php')
        ->add(__('Edit Assessment Type'));
    
    $form = Form::create('editAssessmentType', $session->get('absoluteURL').'/modules/Interventions/eligibility_assessment_types_editProcess.php');
    $form->setTitle(__('Edit Assessment Type'));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonINEligibilityAssessmentTypeID', $gibbonINEligibilityAssessmentTypeID);
    
    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(50)->setValue($values['name']);
    
    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(3)->setValue($values['description']);
    
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required()->selected($values['active']);
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}
