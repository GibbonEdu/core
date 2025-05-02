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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Contributor Dashboard'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_contributor_dashboard.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Get current user
        $gibbonPersonID = $session->get('gibbonPersonID');
        
        // Get the database connection
        $pdo = $container->get('db')->getConnection();
        
        echo '<h2>'.__('My Eligibility Assessments').'</h2>';
        
        // Get eligibility assessments where the user is a contributor
        $sql = "SELECT c.*, a.status as assessmentStatus, a.gibbonINInterventionEligibilityAssessmentID,
                    i.name as interventionName, i.status as interventionStatus, i.gibbonINInterventionID,
                    p.preferredName as studentPreferredName, p.surname as studentSurname,
                    t.name as assessmentTypeName
                FROM gibbonINInterventionEligibilityContributor AS c
                JOIN gibbonINInterventionEligibilityAssessment AS a ON (c.gibbonINInterventionEligibilityAssessmentID=a.gibbonINInterventionEligibilityAssessmentID)
                JOIN gibbonINIntervention AS i ON (a.gibbonINInterventionID=i.gibbonINInterventionID)
                JOIN gibbonPerson AS p ON (i.gibbonPersonIDStudent=p.gibbonPersonID)
                LEFT JOIN gibbonINEligibilityAssessmentType AS t ON (c.gibbonINEligibilityAssessmentTypeID=t.gibbonINEligibilityAssessmentTypeID)
                WHERE c.gibbonPersonIDContributor=:gibbonPersonID
                ORDER BY i.status ASC, c.timestampCreated DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
        $result = $stmt;
        
        // Debug: Output all contributor records
        error_log('All contributor records:');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $record) {
            error_log('ID: ' . $record['gibbonINInterventionEligibilityContributorID'] . ', Status: ' . $record['status']);
        }
        
        // Re-execute the query since we consumed the results
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
        $result = $stmt;
        $eligibilityAssessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($eligibilityAssessments) == 0) {
            echo "<div class='message warning'>".__('There are no eligibility assessments to display.')."</div>";
        } else {
            // Create a table for eligibility assessments
            $table = DataTable::create('eligibilityAssessments');
            $table->setTitle(__('Eligibility Assessments'));
            
            $table->addColumn('student', __('Student'))
                ->format(function($row) {
                    return Format::name('', $row['studentPreferredName'], $row['studentSurname'], 'Student', true);
                });
                
            $table->addColumn('interventionName', __('Intervention'));
            
            $table->addColumn('assessmentTypeName', __('Assessment Type'));
            
            $table->addColumn('creator', __('Created By'))
                ->format(function($row) {
                    return Format::name('', $row['creatorPreferredName'], $row['creatorSurname'], 'Staff', false, true);
                });
                
            $table->addColumn('status', __('Your Status'))
                ->format(function($row) {
                    // Debug: Output the actual status values
                    error_log('Dashboard status values - status: ' . $row['status']);
                    
                    // Use the contributor status, not the assessment status
                    if ($row['status'] == 'Complete') {
                        return '<span class="tag success">'.__('Complete').'</span>';
                    } else {
                        return '<span class="tag dull">'.__('Pending').'</span>';
                    }
                });
                
            $table->addColumn('timestampCreated', __('Date'))
                ->format(Format::using('dateTime', ['timestampCreated']));
                
            $table->addActionColumn()
                ->addParam('gibbonINInterventionEligibilityContributorID')
                ->addParam('gibbonINInterventionEligibilityAssessmentID')
                ->addParam('gibbonINInterventionID')
                ->format(function ($row, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Interventions/intervention_eligibility_contributor_edit.php');
                    
                    // Add a button to add another assessment type
                    $actions->addAction('add', __('Add Assessment Type'))
                        ->setIcon('page_new')
                        ->setURL('/modules/Interventions/intervention_eligibility_contributor_add_type.php');
                });
                
            // Create a DataSet object from the array
            $dataSet = new DataSet($eligibilityAssessments);
            
            echo $table->render($dataSet);
        }
        
        echo '<h2>'.__('My Support Plan Contributions').'</h2>';
        
        // Get support plans where the user is a contributor
        $sql = "SELECT c.*, sp.name as supportPlanName, i.name as interventionName, i.status as interventionStatus, i.gibbonINInterventionID,
                    p.preferredName as studentPreferredName, p.surname as studentSurname
                FROM gibbonINSupportPlanContributor AS c
                JOIN gibbonINSupportPlan AS sp ON (c.gibbonINSupportPlanID=sp.gibbonINSupportPlanID)
                JOIN gibbonINIntervention AS i ON (sp.gibbonINInterventionID=i.gibbonINInterventionID)
                JOIN gibbonPerson AS p ON (i.gibbonPersonIDStudent=p.gibbonPersonID)
                WHERE c.gibbonPersonID=:gibbonPersonID
                ORDER BY i.status ASC, sp.dateStart DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
        $result = $stmt;
        $supportPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($supportPlans) == 0) {
            echo "<div class='message warning'>".__('There are no support plans to display.')."</div>";
        } else {
            // Create a table for support plans
            $table = DataTable::create('supportPlans');
            $table->setTitle(__('Support Plans'));
            
            $table->addColumn('student', __('Student'))
                ->format(function($row) {
                    return Format::name('', $row['studentPreferredName'], $row['studentSurname'], 'Student', true);
                });
                
            $table->addColumn('interventionName', __('Intervention'));
            
            $table->addColumn('supportPlanName', __('Support Plan'));
            
            $table->addColumn('role', __('Your Role'));
            
            $table->addColumn('interventionStatus', __('Status'))
                ->format(function($row) {
                    $statusColors = [
                        'Referral' => 'dull',
                        'Form Tutor Review' => 'warning',
                        'Eligibility Assessment' => 'message',
                        'Intervention Required' => 'warning',
                        'Support Plan Active' => 'success',
                        'Ready for Evaluation' => 'message',
                        'Resolved' => 'success',
                        'Referred for IEP' => 'dull'
                    ];
                    
                    $color = $statusColors[$row['interventionStatus']] ?? 'dull';
                    return '<span class="tag '.$color.'">'.__($row['interventionStatus']).'</span>';
                });
                
            $table->addActionColumn()
                ->addParam('gibbonINInterventionID')
                ->addParam('gibbonINSupportPlanID')
                ->format(function ($row, $actions) {
                    $actions->addAction('view', __('View'))
                        ->setURL('/modules/Interventions/intervention_support_plan_view.php');
                });
                
            // Create a DataSet object from the array
            $dataSet = new DataSet($supportPlans);
            
            echo $table->render($dataSet);
        }
        
        echo '<h2>'.__('My Intervention Contributions').'</h2>';
        
        // Get interventions where the user is a contributor
        $sql = "SELECT c.*, i.name as interventionName, i.status as interventionStatus, i.gibbonINInterventionID,
                    p.preferredName as studentPreferredName, p.surname as studentSurname
                FROM gibbonINInterventionContributor AS c
                JOIN gibbonINIntervention AS i ON (c.gibbonINInterventionID=i.gibbonINInterventionID)
                JOIN gibbonPerson AS p ON (i.gibbonPersonIDStudent=p.gibbonPersonID)
                WHERE c.gibbonPersonIDContributor=:gibbonPersonID
                ORDER BY i.status ASC, c.timestampCreated DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
        $result = $stmt;
        $interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($interventions) == 0) {
            echo "<div class='message warning'>".__('There are no interventions to display.')."</div>";
        } else {
            // Create a table for interventions
            $table = DataTable::create('interventions');
            $table->setTitle(__('Interventions'));
            
            $table->addColumn('student', __('Student'))
                ->format(function($row) {
                    return Format::name('', $row['studentPreferredName'], $row['studentSurname'], 'Student', true);
                });
                
            $table->addColumn('interventionName', __('Intervention'));
            
            $table->addColumn('type', __('Your Role'));
            
            $table->addColumn('interventionStatus', __('Status'))
                ->format(function($row) {
                    $statusColors = [
                        'Referral' => 'dull',
                        'Form Tutor Review' => 'warning',
                        'Intervention' => 'message',
                        'IEP' => 'success',
                        'Resolved' => 'success',
                        'Completed' => 'success'
                    ];
                    
                    $color = $statusColors[$row['interventionStatus']] ?? 'dull';
                    return '<span class="tag '.$color.'">'.__($row['interventionStatus']).'</span>';
                });
                
            $table->addColumn('timestampCreated', __('Date'))
                ->format(Format::using('dateTime', ['timestampCreated']));
                
            $table->addActionColumn()
                ->addParam('gibbonINInterventionID')
                ->format(function ($row, $actions) {
                    $actions->addAction('view', __('View'))
                        ->setURL('/modules/Interventions/interventions_manage_edit.php');
                });
                
            // Create a DataSet object from the array
            $dataSet = new DataSet($interventions);
            
            echo $table->render($dataSet);
        }
    }
}
