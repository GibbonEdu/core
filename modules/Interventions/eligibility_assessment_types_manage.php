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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_types_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Assessment Types'));

    // Establish gateways
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);

    // Add assessment type form
    $form = Form::create('addAssessmentType', $session->get('absoluteURL').'/modules/Interventions/eligibility_assessment_types_manageProcess.php');
    $form->setTitle(__('Add Assessment Type'));
    $form->addHiddenValue('address', $session->get('address'));
    
    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(50);
    
    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(3);
    
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required()->selected('Y');
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();

    // Assessment Types Table
    $criteria = $assessmentTypeGateway->newQueryCriteria()
        ->sortBy(['name'])
        ->fromPOST();

    $assessmentTypes = $assessmentTypeGateway->queryAssessmentTypes($criteria);

    $table = DataTable::createPaginated('assessmentTypes', $criteria);
    $table->setTitle(__('Assessment Types'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Interventions/eligibility_assessment_types_add.php')
        ->displayLabel();
        
    $table->addColumn('name', __('Name'));
    $table->addColumn('description', __('Description'));
    $table->addColumn('active', __('Active'))
        ->format(Format::using('yesNo', ['active']));

    // Actions
    $table->addActionColumn()
        ->addParam('gibbonINEligibilityAssessmentTypeID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Interventions/eligibility_assessment_types_edit.php');
            
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Interventions/eligibility_assessment_types_delete.php');
        });

    echo $table->render($assessmentTypes);
}
