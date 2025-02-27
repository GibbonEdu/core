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
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INEligibilityAssessmentGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/../moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/eligibility_contributor_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonINInvestigationID = $_GET['gibbonINInvestigationID'] ?? '';
        $gibbonINEligibilityAssessmentID = $_GET['gibbonINEligibilityAssessmentID'] ?? '';
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Eligibility Assessments'), 'eligibility_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
            ])
            ->add(__('Edit Eligibility Assessment'), 'eligibility_edit.php', [
                'gibbonINInvestigationID' => $gibbonINInvestigationID,
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
            ])
            ->add(__('Add Contributor'));

        if (empty($gibbonINInvestigationID) || empty($gibbonINEligibilityAssessmentID)) {
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

        // Get the eligibility assessment
        $eligibilityAssessmentGateway = $container->get(INEligibilityAssessmentGateway::class);
        $assessment = $eligibilityAssessmentGateway->getByID($gibbonINEligibilityAssessmentID);

        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Get assessment type name
        $sql = "SELECT name FROM gibbonINEligibilityAssessmentType WHERE gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
        $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentTypeID' => $assessment['gibbonINEligibilityAssessmentTypeID']]);
        $assessmentTypeName = ($result->rowCount() > 0) ? $result->fetchColumn(0) : __('Unknown');

        // Get student details
        $studentName = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', true);

        $form = Form::create('addContributor', $session->get('absoluteURL').'/modules/Individual Needs/eligibility_contributor_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
        $form->addHiddenValue('gibbonINEligibilityAssessmentID', $gibbonINEligibilityAssessmentID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);

        $form->addRow()->addHeading(__('Assessment Details'));

        $row = $form->addRow();
            $row->addLabel('studentNameDisplay', __('Student'));
            $row->addTextField('studentNameDisplay')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('assessmentTypeDisplay', __('Assessment Type'));
            $row->addTextField('assessmentTypeDisplay')->setValue($assessmentTypeName)->readonly();

        $form->addRow()->addHeading(__('Contributor Details'));

        // Get a list of staff who can be assigned as contributors
        $staffGateway = $container->get(StaffGateway::class);
        $criteria = $staffGateway->newQueryCriteria()
            ->sortBy(['surname', 'preferredName'])
            ->fromPOST();

        $staff = $staffGateway->queryAllStaff($criteria, $session->get('gibbonSchoolYearID'));
        $staffOptions = [];
        foreach ($staff as $person) {
            $staffOptions[$person['gibbonPersonID']] = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDContributor', __('Contributor'))->description(__('The staff member who will perform this assessment'));
            $row->addSelect('gibbonPersonIDContributor')->fromArray($staffOptions)->required()->placeholder();

        $row = $form->addRow();
            $row->addLabel('contributorNotes', __('Notes'))->description(__('Additional notes for the contributor'));
            $row->addTextArea('contributorNotes')->setRows(5);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
