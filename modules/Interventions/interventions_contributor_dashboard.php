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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;

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
        
        echo '<h2>'.__('My Eligibility Assessments').'</h2>';
        
        // Get eligibility assessments where the user is a contributor
        $sql = "SELECT c.*, a.*, i.name as interventionName, i.gibbonINInterventionID,
                    p.preferredName as studentPreferredName, p.surname as studentSurname,
                    creator.preferredName as creatorPreferredName, creator.surname as creatorSurname
                FROM gibbonINInterventionEligibilityContributor AS c
                JOIN gibbonINInterventionEligibilityAssessment AS a ON (c.gibbonINInterventionEligibilityAssessmentID=a.gibbonINInterventionEligibilityAssessmentID)
                JOIN gibbonINIntervention AS i ON (a.gibbonINInterventionID=i.gibbonINInterventionID)
                JOIN gibbonPerson AS p ON (a.gibbonPersonIDStudent=p.gibbonPersonID)
                JOIN gibbonPerson AS creator ON (a.gibbonPersonIDCreator=creator.gibbonPersonID)
                WHERE c.gibbonPersonIDContributor=:gibbonPersonID
                ORDER BY c.status ASC, c.timestampCreated DESC";
        
        $result = $pdo->select($sql, ['gibbonPersonID' => $gibbonPersonID]);
        
        if ($result->rowCount() == 0) {
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
            
            $table->addColumn('creator', __('Created By'))
                ->format(function($row) {
                    return Format::name('', $row['creatorPreferredName'], $row['creatorSurname'], 'Staff', false, true);
                });
                
            $table->addColumn('status', __('Your Status'))
                ->format(function($row) {
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
                });
                
            // Convert the database result to an array for the DataTable
            $assessments = $result->fetchAll();
            
            // Create a DataSet object from the array
            $dataSet = new DataSet($assessments);
            
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
        
        $result = $pdo->select($sql, ['gibbonPersonID' => $gibbonPersonID]);
        
        if ($result->rowCount() == 0) {
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
                
            // Convert the database result to an array for the DataTable
            $interventions = $result->fetchAll();
            
            // Create a DataSet object from the array
            $dataSet = new DataSet($interventions);
            
            echo $table->render($dataSet);
        }
    }
}
