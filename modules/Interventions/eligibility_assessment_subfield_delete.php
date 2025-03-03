<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Domain\Interventions\INEligibilityAssessmentTypeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/eligibility_assessment_subfield_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonINEligibilityAssessmentTypeID = $_GET['gibbonINEligibilityAssessmentTypeID'] ?? '';
    $gibbonINEligibilityAssessmentSubfieldID = $_GET['gibbonINEligibilityAssessmentSubfieldID'] ?? '';
    
    if (empty($gibbonINEligibilityAssessmentTypeID) || empty($gibbonINEligibilityAssessmentSubfieldID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }
    
    // Get assessment type details
    $assessmentTypeGateway = $container->get(INEligibilityAssessmentTypeGateway::class);
    $assessmentType = $assessmentTypeGateway->getByID($gibbonINEligibilityAssessmentTypeID);
    
    if (empty($assessmentType)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    // Get subfield details
    $sql = "SELECT * FROM gibbonINEligibilityAssessmentSubfield 
            WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID 
            AND gibbonINEligibilityAssessmentTypeID=:gibbonINEligibilityAssessmentTypeID";
            
    $result = $pdo->select($sql, [
        'gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID,
        'gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID
    ]);
    
    if ($result->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }
    
    $values = $result->fetch();
    
    $page->breadcrumbs
        ->add(__('Manage Assessment Types'), 'eligibility_assessment_types_manage.php')
        ->add(__('Edit Assessment Type'), 'eligibility_assessment_types_edit.php', ['gibbonINEligibilityAssessmentTypeID' => $gibbonINEligibilityAssessmentTypeID])
        ->add(__('Delete Subfield'));
    
    // Check if this subfield is in use
    $sql = "SELECT COUNT(*) FROM gibbonINInterventionEligibilityContributorRating 
            WHERE gibbonINEligibilityAssessmentSubfieldID=:gibbonINEligibilityAssessmentSubfieldID";
            
    $result = $pdo->select($sql, ['gibbonINEligibilityAssessmentSubfieldID' => $gibbonINEligibilityAssessmentSubfieldID]);
    $count = $result->fetchColumn(0);
    
    if ($count > 0) {
        $page->addError(__('This subfield cannot be deleted because it is currently in use.'));
        return;
    }
    
    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Interventions/eligibility_assessment_subfield_deleteProcess.php', true, false);
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonINEligibilityAssessmentTypeID', $gibbonINEligibilityAssessmentTypeID);
    $form->addHiddenValue('gibbonINEligibilityAssessmentSubfieldID', $gibbonINEligibilityAssessmentSubfieldID);
    
    echo $form->getOutput();
}
