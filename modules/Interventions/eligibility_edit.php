<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Domain\Interventions\INReferralGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_edit.php') == false) {
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
        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
        $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';
        $action = $_GET['action'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Eligibility Assessments'), 'eligibility_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Eligibility Assessment'));

        // Check if we're creating a new assessment or editing an existing one
        if ($action == 'create' && !empty($gibbonINInterventionID)) {
            // Create a new eligibility assessment
            $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
            
            // Check if an assessment already exists
            $existingAssessment = $eligibilityAssessmentGateway->getByInterventionID($gibbonINInterventionID);
            
            if (empty($existingAssessment)) {
                // Create a new assessment
                $data = [
                    'gibbonINInterventionID' => $gibbonINInterventionID,
                    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                    'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
                    'status' => 'In Progress',
                    'timestampCreated' => date('Y-m-d H:i:s')
                ];
                
                $gibbonINInterventionEligibilityAssessmentID = $eligibilityAssessmentGateway->insert($data);
                
                if (!$gibbonINInterventionEligibilityAssessmentID) {
                    $page->addError(__('Could not create eligibility assessment.'));
                    return;
                }
                
                // Redirect to the edit page for the new assessment
                $url = './index.php?q=/modules/Interventions/eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$gibbonINInterventionEligibilityAssessmentID.'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonIDStudent='.$gibbonPersonIDStudent;
                header("Location: {$url}");
                exit;
            } else {
                // Assessment already exists, redirect to edit it
                $url = './index.php?q=/modules/Interventions/eligibility_edit.php&gibbonINInterventionEligibilityAssessmentID='.$existingAssessment['gibbonINInterventionEligibilityAssessmentID'].'&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonIDStudent='.$gibbonPersonIDStudent;
                header("Location: {$url}");
                exit;
            }
        }

        // Get the eligibility assessment
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        
        if (empty($gibbonINInterventionEligibilityAssessmentID) && empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $gibbonINReferralID = $_GET['gibbonINReferralID'] ?? '';
        if (empty($gibbonINReferralID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $referralGateway = $container->get(INReferralGateway::class);
        $referral = $referralGateway->getByID($gibbonINReferralID);

        if (empty($referral)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Eligibility Assessments_my' && $referral['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $form = Form::create('eligibility', $session->get('absoluteURL').'/modules/Intervention/eligibility_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINReferralID', $gibbonINReferralID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        // Get student details
        $studentName = Format::name('', $referral['preferredName'], $referral['surname'], 'Student', true);
        $form->addRow()->addHeading(__('Student Details'));
        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($referral['formGroup'])->readonly();

        // Eligibility Assessment Details
        $form->addRow()->addHeading(__('Eligibility Assessment Details'));
        
        // Status
        $row = $form->addRow();
            $row->addLabel('statusText', __('Status'));
            $row->addTextField('statusText')->setValue(__($referral['status']))->required()->readonly();
            
        // Eligibility Decision
        if ($referral['status'] == 'Eligibility Complete' || $session->get('gibbonPersonID') == $referral['gibbonPersonIDCreator']) {
            $row = $form->addRow();
                $row->addLabel('eligibilityDecision', __('Eligibility Decision'));
                $options = [
                    'Pending' => __('Pending'),
                    'Eligible' => __('Eligible for IEP'),
                    'Not Eligible' => __('Needs Intervention')
                ];
                $row->addSelect('eligibilityDecision')->fromArray($options)->selected($referral['eligibilityDecision'] ?? 'Pending');
                
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('eligibilityNotes', __('Notes'));
                $column->addTextArea('eligibilityNotes')->setRows(5)->setClass('w-full')->setValue($referral['eligibilityNotes'] ?? '');
                
            // Add explanation text for the decision options
            $row = $form->addRow();
            $row->addContent('<div class="message emphasis">');
            $row->addContent('<p><strong>'.__('Decision Options').':</strong></p>');
            $row->addContent('<ul>');
            $row->addContent('<li>'.__('Eligible for IEP: Student will follow the IEP path').'</li>');
            $row->addContent('<li>'.__('Needs Intervention: Student will receive interventions before considering an IEP').'</li>');
            $row->addContent('</ul>');
            $row->addContent('</div>');
        }
        
        // Assessments
        $form->addRow()->addHeading(__('Required Assessments'));
        
        $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $criteria = $eligibilityAssessmentGateway->newQueryCriteria();
        $assessments = $eligibilityAssessmentGateway->queryAssessmentsByReferral($criteria, $gibbonINReferralID);
        
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
                ->addParam('gibbonINInterventionEligibilityAssessmentID')
                ->addParam('gibbonINReferralID', $gibbonINReferralID)
                ->format(function ($assessment, $actions) use ($session, $referral) {
                    if (empty($assessment['gibbonPersonIDAssessor'])) {
                        $actions->addAction('assign', __('Assign Contributor'))
                            ->setURL('/modules/Intervention/eligibility_contributor_add.php');
                    } else {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Intervention/eligibility_assessment_edit.php');
                    }
                });
                
            echo $table->render($assessments);
        }
        
        // Add buttons for completing the eligibility assessment
        $row = $form->addRow();
        if ($referral['status'] == 'Eligibility Assessment' && $session->get('gibbonPersonID') == $referral['gibbonPersonIDCreator']) {
            $row->addSubmit(__('Complete Eligibility Assessment'));
        } else {
            $row->addSubmit(__('Update'));
        }
        
        echo $form->getOutput();
        
        // Add a section for creating interventions if eligible
        if ($referral['eligibilityDecision'] == 'Eligible') {
            $form = Form::create('createIntervention', $session->get('absoluteURL').'/modules/Intervention/eligibility_create_interventionProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));
            
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonINReferralID', $gibbonINReferralID);
            
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
