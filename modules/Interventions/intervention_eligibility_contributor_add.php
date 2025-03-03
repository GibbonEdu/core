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
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionEligibilityAssessmentGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_contributor_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'), 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Eligibility Assessment'), 'intervention_eligibility_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID,
                'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Add Contributor'));

        if (empty($gibbonINInterventionID) || empty($gibbonINInterventionEligibilityAssessmentID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $interventionGateway = $container->get(INInterventionGateway::class);
        $intervention = $interventionGateway->getByID($gibbonINInterventionID);

        if (empty($intervention)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        // Get the eligibility assessment
        $eligibilityAssessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $assessment = $eligibilityAssessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);

        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Get student details
        $sql = "SELECT preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
        $result = $pdo->select($sql, ['gibbonPersonID' => $intervention['gibbonPersonIDStudent']]);
        $student = ($result->rowCount() > 0) ? $result->fetch() : [];
        $studentName = Format::name('', $student['preferredName'] ?? '', $student['surname'] ?? '', 'Student', true);

        $form = Form::create('addContributor', $session->get('absoluteURL').'/modules/Interventions/intervention_eligibility_contributor_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
        $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        $form->addRow()->addHeading(__('Assessment Details'));

        $row = $form->addRow();
            $row->addLabel('studentNameDisplay', __('Student'));
            $row->addTextField('studentNameDisplay')->setValue($studentName)->readonly();

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
            $row->addLabel('gibbonPersonIDContributor', __('Contributor'))->description(__('The staff member who will contribute to this assessment'));
            $row->addSelect('gibbonPersonIDContributor')->fromArray($staffOptions)->required()->placeholder();

        // Assessment Type selection removed to allow contributors to choose their own assessment type when they edit their contribution

        $row = $form->addRow();
            $row->addLabel('contributorNotes', __('Notes'))->description(__('Additional notes for the contributor'));
            $row->addTextArea('contributorNotes')->setRows(5);

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
