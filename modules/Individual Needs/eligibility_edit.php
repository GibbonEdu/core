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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INEligibilityAssessmentGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/../moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/eligibility_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Eligibility Assessments'), 'eligibility_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Eligibility Assessment'));

        $gibbonINInvestigationID = $_GET['gibbonINInvestigationID'] ?? '';
        if (empty($gibbonINInvestigationID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $investigationGateway = $container->get(INInvestigationGateway::class);
        $investigation = $investigationGateway->getByID($gibbonINInvestigationID);

        if (empty($investigation)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Eligibility Assessments_my' && $investigation['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $form = Form::create('eligibility', $session->get('absoluteURL').'/modules/Individual Needs/eligibility_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        // Get student details
        $studentName = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', true);
        $form->addRow()->addHeading(__('Student Details'));
        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($investigation['formGroup'])->readonly();

        // Eligibility Assessment Details
        $form->addRow()->addHeading(__('Eligibility Assessment Details'));
        
        // Status
        $row = $form->addRow();
            $row->addLabel('statusText', __('Status'));
            $row->addTextField('statusText')->setValue(__($investigation['status']))->required()->readonly();
            
        // Eligibility Decision
        if ($investigation['status'] == 'Eligibility Complete' || $session->get('gibbonPersonID') == $investigation['gibbonPersonIDCreator']) {
            $row = $form->addRow();
                $row->addLabel('eligibilityDecision', __('Eligibility Decision'));
                $options = [
                    'Pending' => __('Pending'),
                    'Eligible' => __('Eligible'),
                    'Not Eligible' => __('Not Eligible')
                ];
                $row->addSelect('eligibilityDecision')->fromArray($options)->selected($investigation['eligibilityDecision'] ?? 'Pending');
                
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('eligibilityNotes', __('Notes'));
                $column->addTextArea('eligibilityNotes')->setRows(5)->setClass('w-full')->setValue($investigation['eligibilityNotes'] ?? '');
        }
        
        // Assessments
        $form->addRow()->addHeading(__('Required Assessments'));
        
        $eligibilityAssessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
        $criteria = $eligibilityAssessmentGateway->newQueryCriteria();
        $assessments = $eligibilityAssessmentGateway->queryAssessmentsByInvestigation($criteria, $gibbonINInvestigationID);
        
        if ($assessments->getResultCount() == 0) {
            $form->addRow()->addAlert(__('There are no assessments to display.'), 'warning');
        } else {
            // Create a table for assessments
            $table = DataTable::create('assessments');
            $table->setTitle(__('Assessments'));
            
            $table->addColumn('assessmentName', __('Assessment Type'));
            
            $table->addColumn('assessor', __('Assessor'))
                ->format(function($assessment) {
                    if (!empty($assessment['gibbonPersonIDAssessor'])) {
                        return Format::name($assessment['title'], $assessment['preferredName'], $assessment['surname'], 'Staff', false, true);
                    } else {
                        return '<span class="tag dull">'.__('Not Assigned').'</span>';
                    }
                });
                
            $table->addColumn('date', __('Date'))
                ->format(function($assessment) {
                    if (!empty($assessment['date'])) {
                        return Format::date($assessment['date']);
                    } else {
                        return '<span class="tag dull">'.__('Not Completed').'</span>';
                    }
                });
                
            $table->addColumn('result', __('Result'))
                ->format(function($assessment) {
                    if ($assessment['result'] == 'Pass') {
                        return '<span class="tag success">'.__('Pass').'</span>';
                    } else if ($assessment['result'] == 'Fail') {
                        return '<span class="tag error">'.__('Fail').'</span>';
                    } else {
                        return '<span class="tag dull">'.__('Inconclusive').'</span>';
                    }
                });
                
            $table->addActionColumn()
                ->addParam('gibbonINEligibilityAssessmentID')
                ->addParam('gibbonINInvestigationID', $gibbonINInvestigationID)
                ->format(function ($assessment, $actions) use ($session, $investigation) {
                    if (empty($assessment['gibbonPersonIDAssessor'])) {
                        $actions->addAction('assign', __('Assign Contributor'))
                            ->setURL('/modules/Individual Needs/eligibility_contributor_add.php');
                    } else {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Individual Needs/eligibility_assessment_edit.php');
                    }
                });
                
            echo $table->render($assessments);
        }
        
        // Add buttons for completing the eligibility assessment
        $row = $form->addRow();
        if ($investigation['status'] == 'Eligibility Assessment' && $session->get('gibbonPersonID') == $investigation['gibbonPersonIDCreator']) {
            $row->addSubmit(__('Complete Eligibility Assessment'));
        } else {
            $row->addSubmit(__('Update'));
        }
        
        echo $form->getOutput();
        
        // Add a section for creating interventions if eligible
        if ($investigation['eligibilityDecision'] == 'Eligible') {
            $form = Form::create('createIntervention', $session->get('absoluteURL').'/modules/Individual Needs/eligibility_create_interventionProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));
            
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
            
            $form->addRow()->addHeading(__('Create Intervention'));
            
            $row = $form->addRow();
            $row->addLabel('interventionName', __('Intervention Name'))->description(__('A short name for this intervention'));
            $row->addTextField('interventionName')->required()->maxLength(100);
            
            $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('interventionDescription', __('Description'))->description(__('Describe the intervention and its goals'));
            $column->addTextArea('interventionDescription')->setRows(5)->setClass('w-full')->required();
            
            $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('interventionStrategies', __('Strategies'))->description(__('Specific strategies to be used in this intervention'));
            $column->addTextArea('interventionStrategies')->setRows(5)->setClass('w-full')->required();
            
            $row = $form->addRow();
            $row->addLabel('interventionTargetDate', __('Target Date'))->description(__('When should this intervention be reviewed?'));
            $row->addDate('interventionTargetDate')->required();
            
            $row = $form->addRow();
            $row->addLabel('interventionParentConsent', __('Parent Consent'))->description(__('Has parent consent been obtained for this intervention?'));
            $options = [
                'Not Requested' => __('Not Requested'),
                'Consent Given' => __('Consent Given'),
                'Consent Denied' => __('Consent Denied'),
                'Awaiting Response' => __('Awaiting Response')
            ];
            $row->addSelect('interventionParentConsent')->fromArray($options)->required()->selected('Not Requested');
            
            $row = $form->addRow();
            $row->addSubmit(__('Create Intervention'));
            
            echo $form->getOutput();
        }
    }
}
