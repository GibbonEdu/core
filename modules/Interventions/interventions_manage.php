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
use Gibbon\Domain\Interventions\INInterventionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Interventions'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder w-full');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', '/modules/Interventions/interventions_manage.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonFormGroupID);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);
            
        $statuses = [
            'Pending' => __('Pending'),
            'In Progress' => __('In Progress'),
            'Completed' => __('Completed'),
            'Discontinued' => __('Discontinued')
        ];
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray(['' => __('All')] + $statuses)->selected($status);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // CRITERIA
        $interventionGateway = $container->get(INInterventionGateway::class);
        
        $criteria = $interventionGateway->newQueryCriteria()
            ->sortBy(['student.surname', 'student.preferredName'])
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->filterBy('status', $status)
            ->fromPOST();

        // Get the current school year
        $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        
        // QUERY
        if ($highestAction == 'Manage Interventions_all') {
            $interventions = $interventionGateway->queryInterventions($criteria, $gibbonSchoolYearID);
        } else if ($highestAction == 'Manage Interventions_my') {
            $interventions = $interventionGateway->queryInterventions($criteria, $gibbonSchoolYearID, $session->get('gibbonPersonID'));
        }

        // DATA TABLE
        $table = DataTable::createPaginated('interventionsManage', $criteria);
        $table->setTitle(__('Interventions'));

        $table->modifyRows(function ($intervention, $row) {
            if ($intervention['status'] == 'Completed') $row->addClass('success');
            if ($intervention['status'] == 'Pending') $row->addClass('warning');
            if ($intervention['status'] == 'Discontinued') $row->addClass('error');
            return $row;
        });

        $table->addHeaderAction('add', __('New Intervention'))
            ->setURL('/modules/Intervention/interventions_manage_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();

        $table->addColumn('student', __('Student'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->format(function ($intervention) {
                return Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true);
            });

        $table->addColumn('formGroup', __('Form Group'))->sortable();

        $table->addColumn('name', __('Intervention'))->sortable();
        
        $table->addColumn('status', __('Status'))->sortable();
        
        // Remove the parent consent column as it's not in the database
        // $table->addColumn('parentConsent', __('Parent Consent'))->sortable();
        
        // Add the creation date column
        $table->addColumn('timestampCreated', __('Date'))
            ->format(Format::using('dateTime', ['timestampCreated']));
            
        $table->addColumn('creator', __('Created By'))
            ->sortable(['creatorSurname', 'creatorPreferredName'])
            ->format(function ($intervention) {
                return Format::name($intervention['title'], $intervention['creatorPreferredName'], $intervention['creatorSurname'], 'Staff', false, true);
            });

        $table->addColumn('targetDate', __('Target Date'))
            ->format(Format::using('date', ['targetDate']));
            
        $table->addActionColumn()
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->format(function ($intervention, $actions) use ($highestAction) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Intervention/interventions_manage_edit.php');
            });

        echo $table->render($interventions);
    }
}
