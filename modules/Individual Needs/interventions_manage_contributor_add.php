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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\IndividualNeeds\INInterventionGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/interventions_manage_contributor_add.php') == false) {
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

        if (empty($gibbonINInterventionID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $interventionGateway = $container->get(INInterventionGateway::class);
        $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

        if (empty($intervention)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
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
            ->add(__('Add Contributor'));

        $form = Form::create('contributor', $session->get('absoluteURL').'/modules/Individual Needs/interventions_manage_contributor_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        $form->addRow()->addHeading(__('Intervention Details'));
        
        $row = $form->addRow();
            $row->addLabel('interventionName', __('Intervention'));
            $row->addTextField('interventionName')->setValue($intervention['name'])->readonly();

        $row = $form->addRow();
            $row->addLabel('student', __('Student'));
            $row->addTextField('student')->setValue(Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true))->readonly();

        $form->addRow()->addHeading(__('Contributor Details'));

        // Get list of staff
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson
                JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPerson.status='Full'
                ORDER BY surname, preferredName";
        $result = $pdo->executeQuery([], $sql);
        $staff = ($result->rowCount() > 0) ? $result->fetchAll() : [];
        $staffOptions = [];
        foreach ($staff as $person) {
            $staffOptions[$person['gibbonPersonID']] = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDContributor', __('Contributor'));
            $row->addSelect('gibbonPersonIDContributor')
                ->fromArray($staffOptions)
                ->required()
                ->placeholder();

        $contributorTypes = [
            'Teacher' => __('Teacher'),
            'Support Staff' => __('Support Staff'),
            'External Agency' => __('External Agency'),
            'Other' => __('Other')
        ];
        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($contributorTypes)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
