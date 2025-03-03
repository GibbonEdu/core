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
use Gibbon\Services\Format;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_edit.php') == false) {
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
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        $gibbonINInterventionEligibilityContributorID = $_GET['gibbonINInterventionEligibilityContributorID'] ?? '';

        if (empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityContributorID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'), 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Eligibility Assessment'), 'intervention_eligibility_edit.php', [
                'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Contributor'));

        // Get the contributor data
        $sql = "SELECT c.*, p.title, p.preferredName, p.surname 
                FROM gibbonINInterventionEligibilityContributor AS c 
                JOIN gibbonPerson AS p ON (c.gibbonPersonIDContributor=p.gibbonPersonID) 
                WHERE c.gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                
        $result = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
        
        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        $contributor = $result->fetch();
        $contributorName = Format::name($contributor['title'], $contributor['preferredName'], $contributor['surname'], 'Staff', false, true);

        // Check access based on the highest action level
        $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);
        
        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        // Get intervention details to check access
        $sql = "SELECT * FROM gibbonINIntervention WHERE gibbonINInterventionID=:gibbonINInterventionID";
        $intervention = $pdo->selectOne($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
        
        if (empty($intervention)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Create the form
        $form = Form::create('contributorEdit', $session->get('absoluteURL').'/modules/Interventions/intervention_eligibility_contributor_editProcess.php');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
        $form->addHiddenValue('gibbonINInterventionEligibilityContributorID', $gibbonINInterventionEligibilityContributorID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        $form->addRow()->addHeading(__('Contributor Details'));

        $row = $form->addRow();
            $row->addLabel('contributorName', __('Contributor'));
            $row->addTextField('contributorName')->setValue($contributorName)->readonly();

        $form->addRow()->addHeading(__('Assessment Details'));

        // Get assessment types
        $sql = "SELECT gibbonINEligibilityAssessmentTypeID as value, name 
                FROM gibbonINEligibilityAssessmentType 
                WHERE active='Y' 
                ORDER BY name";
        $result = $pdo->select($sql);
        
        $assessmentTypes = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        // Display warning if assessment type not selected
        if (empty($contributor['gibbonINEligibilityAssessmentTypeID'])) {
            echo "<div class='warning'>";
            echo __('Please select an assessment type to specify the type of assessment you are performing.');
            echo "</div>";
        }
        
        $row = $form->addRow();
            $row->addLabel('gibbonINEligibilityAssessmentTypeID', __('Assessment Type'))
                ->description(__('Please select the type of assessment you wish to perform'));
            $row->addSelect('gibbonINEligibilityAssessmentTypeID')
                ->fromArray($assessmentTypes)
                ->placeholder(__('Please select...'))
                ->selected($contributor['gibbonINEligibilityAssessmentTypeID'] ?? '')
                ->required()
                ->append("<script>
                    $(document).ready(function() {
                        $('#gibbonINEligibilityAssessmentTypeID').change(function() {
                            $('#contributorEditForm').submit();
                        });
                    });
                </script>");

        // If assessment type is selected, display the subfields with rating options
        if (!empty($contributor['gibbonINEligibilityAssessmentTypeID'])) {
            // Get subfields for this assessment type
            $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
                    WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID 
                    AND active='Y' 
                    ORDER BY sequenceNumber";
            $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $contributor['gibbonINEligibilityAssessmentTypeID']]);
            
            if ($result->rowCount() > 0) {
                $form->addRow()->addHeading(__('Assessment Ratings'));
                
                // Add rating scale legend
                $ratingLegend = $form->addRow()->addContent('<div class="mt-2 mb-4">
                    <p><strong>'.__('Rating Scale Legend').':</strong></p>
                    <div class="flex flex-col sm:flex-row">
                        <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                            <strong>0:</strong> '.__('Not Evaluated').'
                        </div>
                        <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                            <strong>1:</strong> '.__('No Concern').'
                        </div>
                        <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                            <strong>2:</strong> '.__('Mild Concern').'
                        </div>
                        <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                            <strong>3:</strong> '.__('Moderate Concern').'
                        </div>
                        <div class="flex-1 bg-gray-100 border rounded p-2 mr-2 mb-2">
                            <strong>4:</strong> '.__('Significant Concern').'
                        </div>
                        <div class="flex-1 bg-gray-100 border rounded p-2 mb-2">
                            <strong>5:</strong> '.__('High Concern').'
                        </div>
                    </div>
                </div>');
                
                // Get existing ratings
                $ratings = [];
                $sql = "SELECT * FROM gibbonINInterventionEligibilityContributorRating 
                        WHERE gibbonINInterventionEligibilityContributorID=:gibbonINInterventionEligibilityContributorID";
                $ratingResults = $pdo->select($sql, ['gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID]);
                
                if ($ratingResults->rowCount() > 0) {
                    while ($rating = $ratingResults->fetch()) {
                        $ratings[$rating['gibbonINEligibilityAssessmentSubfieldID']] = $rating['rating'];
                    }
                }
                
                // Add rating fields for each subfield
                while ($subfield = $result->fetch()) {
                    $row = $form->addRow();
                    $row->addLabel('rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'], $subfield['name'])
                        ->description($subfield['description']);
                    
                    $ratingOptions = [
                        '0' => '0 - '.__('Not Evaluated'),
                        '1' => '1 - '.__('No Concern'),
                        '2' => '2 - '.__('Mild Concern'),
                        '3' => '3 - '.__('Moderate Concern'),
                        '4' => '4 - '.__('Significant Concern'),
                        '5' => '5 - '.__('High Concern')
                    ];
                    
                    $row->addSelect('rating'.$subfield['gibbonINEligibilityAssessmentSubfieldID'])
                        ->fromArray($ratingOptions)
                        ->selected($ratings[$subfield['gibbonINEligibilityAssessmentSubfieldID']] ?? '0');
                }
            } else {
                $form->addRow()->addAlert(__('There are no subfields defined for this assessment type. Please contact an administrator.'), 'warning');
            }
        }

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $options = [
                'Pending' => __('Pending'),
                'Complete' => __('Complete')
            ];
            $row->addSelect('status')->fromArray($options)->required()->selected($contributor['status']);

        $row = $form->addRow();
            $row->addLabel('recommendation', __('Recommendation'))->description(__('Contributor\'s recommendation based on assessment'));
            $options = [
                'Pending' => __('Pending'),
                'Eligible for IEP' => __('Eligible for IEP'),
                'Needs Intervention' => __('Needs Intervention')
            ];
            $row->addSelect('recommendation')->fromArray($options)->required()->selected($contributor['recommendation']);

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))->description(__('Contributor\'s assessment notes'));
            $row->addTextArea('notes')->setRows(10)->setValue($contributor['notes']);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
