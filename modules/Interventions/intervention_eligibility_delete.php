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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction) || $highestAction != 'Manage Eligibility Assessments') {
        $page->addError(__('You do not have access to this action.'));
    } else {
        // Proceed!
        $gibbonINInterventionEligibilityAssessmentID = $_GET['gibbonINInterventionEligibilityAssessmentID'] ?? '';
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';
        
        if (empty($gibbonINInterventionEligibilityAssessmentID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }
        
        $assessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $assessment = $assessmentGateway->getByID($gibbonINInterventionEligibilityAssessmentID);
        
        if (empty($assessment)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        // Get student details
        $sql = "SELECT p.preferredName, p.surname, i.name as interventionName
                FROM gibbonINInterventionEligibilityAssessment AS a
                JOIN gibbonPerson AS p ON (a.gibbonPersonIDStudent=p.gibbonPersonID)
                JOIN gibbonINIntervention AS i ON (a.gibbonINInterventionID=i.gibbonINInterventionID)
                WHERE a.gibbonINInterventionEligibilityAssessmentID=:gibbonINInterventionEligibilityAssessmentID";
        $result = $pdo->select($sql, ['gibbonINInterventionEligibilityAssessmentID' => $gibbonINInterventionEligibilityAssessmentID]);
        
        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }
        
        $student = $result->fetch();
        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', true);
        
        $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Interventions/intervention_eligibility_deleteProcess.php', true, false);
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionEligibilityAssessmentID', $gibbonINInterventionEligibilityAssessmentID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);
        
        echo $form->getOutput();
    }
}
