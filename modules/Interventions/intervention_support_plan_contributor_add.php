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
use Gibbon\Services\Format;
use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Interventions'), 'interventions.php')
    ->add(__('Add Support Plan Contributor'));

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_contributor_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Get parameters
    $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
    $gibbonINSupportPlanID = $_GET['gibbonINSupportPlanID'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($gibbonINSupportPlanID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get support plan details
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);

    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    if (!$isAdmin && !$isCoordinator) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Get existing contributors to avoid duplicates
    $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
    $sql = "SELECT gibbonPersonIDContributor FROM gibbonINSupportPlanContributor WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $existingContributors = $pdo->selectColumn($sql, $data);

    // Create the form
    $form = Form::create('supportPlanContributorAdd', $_SESSION[$guid]['absoluteURL'].'/modules/Interventions/intervention_support_plan_contributor_addProcess.php');
    $form->setClass('w-full');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
    $form->addHiddenValue('gibbonINSupportPlanID', $gibbonINSupportPlanID);

    // Display support plan info
    $row = $form->addRow();
    $row->addContent('<h4>'.__('Support Plan').': '.$supportPlan['name'].'</h4>');

    // Contributor details
    $row = $form->addRow();
    $row->addLabel('gibbonPersonIDContributor', __('Contributor'));
    
    // Get all staff members who are not already contributors
    $sql = "SELECT gibbonPerson.gibbonPersonID, CONCAT(gibbonPerson.surname, ', ', gibbonPerson.preferredName, ' (', gibbonRole.name, ')') as name 
            FROM gibbonPerson 
            JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) 
            WHERE gibbonPerson.status='Full' 
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
    $resultStaff = $pdo->executeQuery(array(), $sql);
    $staff = ($resultStaff->rowCount() > 0)? $resultStaff->fetchAll() : array();
    
    $staffOptions = array_reduce($staff, function($group, $item) use ($existingContributors) {
        if (!in_array($item['gibbonPersonID'], $existingContributors)) {
            $group[$item['gibbonPersonID']] = $item['name'];
        }
        return $group;
    }, []);
    
    $row->addSelect('gibbonPersonIDContributor')
        ->fromArray($staffOptions)
        ->required()
        ->placeholder();

    // Role options
    $roles = [
        'Classroom Teacher' => __('Classroom Teacher'),
        'Special Education Teacher' => __('Special Education Teacher'),
        'School Counselor' => __('School Counselor'),
        'Psychologist' => __('Psychologist'),
        'Speech Therapist' => __('Speech Therapist'),
        'Occupational Therapist' => __('Occupational Therapist'),
        'Physical Therapist' => __('Physical Therapist'),
        'Behavior Specialist' => __('Behavior Specialist'),
        'Administrator' => __('Administrator'),
        'Other' => __('Other')
    ];

    $row = $form->addRow();
    $row->addLabel('role', __('Role'));
    $row->addSelect('role')
        ->fromArray($roles)
        ->required()
        ->placeholder();

    // Can edit permission
    $row = $form->addRow();
    $row->addLabel('canEdit', __('Can Edit Plan'));
    $row->addYesNo('canEdit')->required()->selected('N');

    // Add the submit button
    $row = $form->addRow();
    $row->addSubmit(__('Add Contributor'));

    // Display the form
    echo $form->getOutput();
}
