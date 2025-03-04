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
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityContributorGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_add_type.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonINInterventionEligibilityContributorID = $_GET['gibbonINInterventionEligibilityContributorID'] ?? '';
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';

        $page->breadcrumbs
            ->add(__('Contributor Dashboard'), 'interventions_contributor_dashboard.php')
            ->add(__('Add Assessment Type'));

        if (empty($gibbonINInterventionEligibilityContributorID) || empty($gibbonINInterventionEligibilityAssessmentID) || empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        // Get the current contributor
        $contributorGateway = $container->get(INInterventionEligibilityContributorGateway::class);
        $criteria = $contributorGateway->newQueryCriteria();
        $contributors = $contributorGateway->queryContributors($criteria, [
            'gibbonINInterventionEligibilityContributorID' => $gibbonINInterventionEligibilityContributorID
        ]);

        if ($contributors->getResultCount() == 0) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        $contributor = $contributors->getRow(0);

        // Check that the current user is the contributor
        if ($contributor['gibbonPersonIDContributor'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Get the assessment
        $assessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $assessment = $assessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);

        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Get student details
        $sql = "SELECT preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
        $result = $pdo->select($sql, ['gibbonPersonID' => $assessment['gibbonPersonIDStudent']]);
        $student = ($result->rowCount() > 0) ? $result->fetch() : [];
        $studentName = Format::name('', $student['preferredName'] ?? '', $student['surname'] ?? '', 'Student', true);

        // Get intervention details
        $sql = "SELECT name FROM gibbonINIntervention WHERE gibbonINInterventionID=:gibbonINInterventionID";
        $result = $pdo->select($sql, ['gibbonINInterventionID' => $gibbonINInterventionID]);
        $intervention = ($result->rowCount() > 0) ? $result->fetch() : [];
        $interventionName = $intervention['name'] ?? '';

        // Get the assessment types the contributor has already completed
        $sql = "SELECT gibbonINEligibilityAssessmentTypeID 
                FROM gibbonINInterventionEligibilityContributor 
                WHERE gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID 
                AND gibbonPersonIDContributor=:gibbonPersonIDContributor";
        $result = $pdo->select($sql, [
            'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
            'gibbonPersonIDContributor' => $session->get('gibbonPersonID')
        ]);
        $completedAssessmentTypes = ($result->rowCount() > 0) ? array_column($result->fetchAll(), 'gibbonINEligibilityAssessmentTypeID') : [];

        // Get all assessment types
        $sql = "SELECT gibbonINEligibilityAssessmentTypeID, name, description 
                FROM gibbonINEligibilityAssessmentType 
                WHERE active='Y' 
                ORDER BY name";
        $result = $pdo->select($sql);
        $assessmentTypes = ($result->rowCount() > 0) ? $result->fetchAll() : [];

        // Filter out assessment types that have already been completed by this contributor
        $availableAssessmentTypes = array_filter($assessmentTypes, function($type) use ($completedAssessmentTypes) {
            return !in_array($type['gibbonINEligibilityAssessmentTypeID'], $completedAssessmentTypes);
        });

        if (empty($availableAssessmentTypes)) {
            $page->addError(__('You have already completed all available assessment types for this intervention.'));
            return;
        }

        // Create the form
        $form = Form::create('addAssessmentType', $session->get('absoluteURL').'/modules/Interventions/intervention_eligibility_contributor_add_typeProcess.php');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonPersonIDContributor', $session->get('gibbonPersonID'));

        $form->addRow()->addHeading(__('Assessment Details'));

        $row = $form->addRow();
            $row->addLabel('studentNameDisplay', __('Student'));
            $row->addTextField('studentNameDisplay')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('interventionNameDisplay', __('Intervention'));
            $row->addTextField('interventionNameDisplay')->setValue($interventionName)->readonly();

        $form->addRow()->addHeading(__('New Assessment Type'));

        // Create options array for the select
        $assessmentTypeOptions = [];
        foreach ($availableAssessmentTypes as $type) {
            $assessmentTypeOptions[$type['gibbonINEligibilityAssessmentTypeID']] = $type['name'];
        }

        $row = $form->addRow();
            $row->addLabel('gibbonINEligibilityAssessmentTypeID', __('Assessment Type'))
                ->description(__('Select the type of assessment you would like to perform'));
            $row->addSelect('gibbonINEligibilityAssessmentTypeID')
                ->fromArray($assessmentTypeOptions)
                ->required()
                ->placeholder();

        $row = $form->addRow();
            $row->addLabel('notes', __('Notes'))
                ->description(__('Initial notes for this assessment'));
            $row->addTextArea('notes')
                ->setRows(5);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
