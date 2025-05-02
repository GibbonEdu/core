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
use Gibbon\Module\Interventions\Domain\INEligibilityAssessmentTypeGateway;

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
    
    // Edit Assessment Type Form
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
    
    // Subfields Table
    echo '<h2>'.__('Assessment Subfields').'</h2>';
    echo '<p>'.__('Define the specific areas to be assessed within this assessment type. Each subfield will be rated on a scale of 0-5, where 0 means not evaluated, 1 is low concern, and 5 is high concern.').'</p>';
    
    // Get existing subfields
    $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
            ORDER BY sequenceNumber";
    $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID]);
    
    // Create the table
    $table = DataTable::create('subfieldTable');
    $table->setTitle(__('Assessment Subfields'));
    
    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Interventions/eligibility_assessment_subfield_add.php')
        ->addParam('gibbonINEligibilityAssessmentTypeID', $gibbonINEligibilityAssessmentTypeID)
        ->displayLabel();
    
    $table->addColumn('name', __('Name'));
    $table->addColumn('description', __('Description'))
        ->format(function($values) {
            return Format::truncate($values['description'], 100);
        });
    $table->addColumn('sequenceNumber', __('Sequence'));
    $table->addColumn('active', __('Active'))
        ->format(Format::using('yesNo', ['active']));
    
    $table->addActionColumn()
        ->addParam('gibbonINEligibilityAssessmentTypeID', $gibbonINEligibilityAssessmentTypeID)
        ->addParam('gibbonINEligibilityAssessmentSubfieldID')
        ->format(function ($subfield, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Interventions/eligibility_assessment_subfield_edit.php');
            
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Interventions/eligibility_assessment_subfield_delete.php');
        });
    
    // Convert the database result to an array for the DataTable
    $subfields = $result->fetchAll();
    
    // Render the table with data
    echo $table->render($subfields);
    
    // Rating Scale Legend
    echo '<h3>'.__('Rating Scale').'</h3>';
    echo '<table class="smallIntBorder" cellspacing="0" style="width:100%">';
    echo '<tr><th style="width:10%">'.__('Rating').'</th><th>'.__('Description').'</th></tr>';
    echo '<tr><td>0</td><td>'.__('Not Evaluated').'</td></tr>';
    echo '<tr><td>1</td><td>'.__('No Concern').'</td></tr>';
    echo '<tr><td>2</td><td>'.__('Mild Concern').'</td></tr>';
    echo '<tr><td>3</td><td>'.__('Moderate Concern').'</td></tr>';
    echo '<tr><td>4</td><td>'.__('Significant Concern').'</td></tr>';
    echo '<tr><td>5</td><td>'.__('High Concern').'</td></tr>';
    echo '</table>';
}
