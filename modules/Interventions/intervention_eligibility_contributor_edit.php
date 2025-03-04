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
        $returnProcess = $_GET['returnProcess'] ?? '';

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

        // Determine if the current user is a contributor
        $isContributor = false;
        if ($session->get('gibbonPersonID') == $contributor['gibbonPersonIDContributor']) {
            $isContributor = true;
        }

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
        $form->addHiddenValue('returnProcess', $returnProcess);
        $form->addHiddenValue('isContributor', $isContributor ? 'true' : '');

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
                        // Function to load subfields
                        function loadSubfields(assessmentTypeID) {
                            if (!assessmentTypeID) return;
                            
                            // Show loading indicator
                            $('#subfieldsContainer').html('<div class=\"text-center\"><img src=\"".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/loading.gif\" alt=\"Loading\" /></div>');
                            
                            // Ajax request to get subfields
                            $.ajax({
                                url: '".$session->get('absoluteURL')."/modules/Interventions/intervention_eligibility_contributor_subfieldsAjax.php',
                                type: 'GET',
                                data: {
                                    gibbonINEligibilityAssessmentTypeID: assessmentTypeID,
                                    gibbonINInterventionEligibilityContributorID: '".$gibbonINInterventionEligibilityContributorID."'
                                },
                                success: function(data) {
                                    $('#subfieldsContainer').html(data);
                                },
                                error: function() {
                                    $('#subfieldsContainer').html('<div class=\"error\">".__('An error occurred while loading the subfields.')."</div>');
                                }
                            });
                        }
                        
                        // Load subfields on page load if assessment type is selected
                        var initialAssessmentType = $('#gibbonINEligibilityAssessmentTypeID').val();
                        if (initialAssessmentType) {
                            loadSubfields(initialAssessmentType);
                        }
                        
                        // Load subfields when assessment type changes
                        $('#gibbonINEligibilityAssessmentTypeID').change(function() {
                            loadSubfields($(this).val());
                        });
                    });
                </script>");

        // Add a container for subfields that will be loaded via AJAX
        $form->addRow()->addContent('<div id="subfieldsContainer"></div>');

        $row = $form->addRow();
            $row->addLabel('status', __('Status'))
                ->description(__('Current status of this assessment'));
            $row->addSelect('status')
                ->fromArray([
                    'Pending' => __('Pending'),
                    'In Progress' => __('In Progress'),
                    'Complete' => __('Complete')
                ])
                ->selected($contributor['status'] ?? 'Pending')
                ->required();

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))
                ->description(__('Contributor\'s assessment notes'));
            $row->addTextArea('notes')
                ->setRows(10)
                ->setValue($contributor['notes'] ?? '');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
