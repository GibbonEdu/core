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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInterventionGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionUpdateGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionContributorGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/interventions_manage_edit.php') == false) {
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

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'));

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

        $form = Form::create('intervention', $session->get('absoluteURL').'/modules/Individual Needs/interventions_manage_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        // Get student details
        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true);
        $form->addRow()->addHeading(__('Student Details'));
        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($intervention['formGroup'])->readonly();

        // Intervention Details
        $form->addRow()->addHeading(__('Intervention Details'));
        $row = $form->addRow();
            $row->addLabel('name', __('Name'))->description(__('Brief name for this intervention'));
            $row->addTextField('name')->maxLength(100)->required();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'))->description(__('Details about the intervention'));
            $row->addTextArea('description')->setRows(5)->required();

        $row = $form->addRow();
            $row->addLabel('strategies', __('Strategies'))->description(__('Specific strategies to be implemented'));
            $row->addTextArea('strategies')->setRows(8)->required();

        $row = $form->addRow();
            $row->addLabel('targetDate', __('Target Date'))->description(__('When should this intervention be completed by?'));
            $row->addDate('targetDate')->required();

        $statuses = [
            'Pending' => __('Pending'),
            'In Progress' => __('In Progress'),
            'Completed' => __('Completed'),
            'Discontinued' => __('Discontinued')
        ];
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($statuses)->required();

        $parentConsent = [
            'Not Requested' => __('Not Requested'),
            'Awaiting Response' => __('Awaiting Response'),
            'Consent Given' => __('Consent Given'),
            'Consent Denied' => __('Consent Denied')
        ];
        $row = $form->addRow();
            $row->addLabel('parentConsent', __('Parent Consent'));
            $row->addSelect('parentConsent')->fromArray($parentConsent)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($intervention);

        echo $form->getOutput();

        // CONTRIBUTORS
        $contributorGateway = $container->get(INInterventionContributorGateway::class);
        
        $criteria = $contributorGateway->newQueryCriteria()
            ->sortBy(['dateCreated'])
            ->fromPOST();

        $contributors = $contributorGateway->queryContributorsByIntervention($criteria, $gibbonINInterventionID);

        $table = DataTable::createPaginated('contributors', $criteria);
        $table->setTitle(__('Contributors'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Individual Needs/interventions_manage_contributor_add.php')
            ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->displayLabel();

        $table->addColumn('name', __('Name'))
            ->format(function ($person) {
                return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);
            });

        $table->addColumn('type', __('Type'));
        $table->addColumn('status', __('Status'));
        $table->addColumn('dateCreated', __('Date'))
            ->format(Format::using('date', 'dateCreated'));

        $table->addActionColumn()
            ->addParam('gibbonINInterventionContributorID')
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->format(function ($contributor, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Individual Needs/interventions_manage_contributor_edit.php');
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Individual Needs/interventions_manage_contributor_delete.php')
                    ->modalWindow(650, 400);
            });

        echo $table->render($contributors);

        // UPDATES
        $updateGateway = $container->get(INInterventionUpdateGateway::class);
        
        $criteria = $updateGateway->newQueryCriteria()
            ->sortBy(['timestamp'], 'DESC')
            ->fromPOST();

        $updates = $updateGateway->queryUpdatesByIntervention($criteria, $gibbonINInterventionID);

        $table = DataTable::createPaginated('updates', $criteria);
        $table->setTitle(__('Updates'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Individual Needs/interventions_update.php')
            ->addParam('gibbonINInterventionID', $gibbonINInterventionID)
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->displayLabel();

        $table->addColumn('name', __('Name'))
            ->format(function ($person) {
                return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);
            });

        $table->addColumn('comment', __('Comment'))
            ->format(function ($update) {
                return Format::truncate($update['comment'], 60);
            });
            
        $table->addColumn('timestamp', __('Date'))
            ->format(Format::using('dateTime', 'timestamp'));

        $table->addActionColumn()
            ->addParam('gibbonINInterventionUpdateID')
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->format(function ($update, $actions) use ($session) {
                if ($update['gibbonPersonID'] == $session->get('gibbonPersonID')) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Individual Needs/interventions_update_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Individual Needs/interventions_update_delete.php')
                        ->modalWindow(650, 400);
                }
            });

        echo $table->render($updates);
    }
}
