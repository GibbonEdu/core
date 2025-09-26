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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\FacilityChangeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceChange_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs->add(__('Manage Facility Changes'));

        if ($highestAction == 'Manage Facility Changes_allClasses') {
            echo '<p>'.__('This page allows you to create and manage one-off location changes within any class in the timetable. Only current and future changes are shown: past changes are hidden.').'</p>';
        } else if ($highestAction == 'Manage Facility Changes_myDepartment') {
            echo '<p>'.__('This page allows you to create and manage one-off location changes within any of the classes departments for which have have the role Coordinator. Only current and future changes are shown: past changes are hidden.').'</p>';
        } else {
            echo '<p>'.__('This page allows you to create and manage one-off location changes within any of your classes in the timetable. Only current and future changes are shown: past changes are hidden.').'</p>';
        }

         $search = isset($_GET['search']) ? $_GET['search'] : '';

        $facilityChangeGateway = $container->get(FacilityChangeGateway::class);

        $criteria = $facilityChangeGateway->newQueryCriteria(true)
            ->searchBy($facilityChangeGateway->getSearchableColumns(), $search)
            ->sortBy(['date', 'courseName', 'className'])
            ->fromPOST();

        // SEARCH FORM
        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');

        $form->setTitle(__('Search'));
        $form->setClass('noIntBorder w-full');

        $form->addHiddenValue('q', '/modules/Timetable/spaceChange_manage.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Class, original facility, new facility, person'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addSearchSubmit($session, 'Clear Search');

        echo $form->getOutput();

        if ($highestAction == 'Manage Facility Changes_allClasses') {
            $facilityChanges = $facilityChangeGateway->queryFacilityChanges($criteria);
        } else if ($highestAction == 'Manage Facility Changes_myDepartment') {
            $facilityChanges = $facilityChangeGateway->queryFacilityChangesByDepartment($criteria, $session->get('gibbonPersonID'));
        } else {
            $facilityChanges = $facilityChangeGateway->queryFacilityChanges($criteria, $session->get('gibbonPersonID'));
        }

        // DATA TABLE
        $table = DataTable::createPaginated('facilityChanges', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable/spaceChange_manage_add.php')
            ->displayLabel();

        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', 'date'));
        $table->addColumn('courseClass', __('Class'))
            ->sortable(['courseName', 'className'])
            ->format(Format::using('courseClassName', ['courseName', 'className']));
        $table->addColumn('spaceOld', __('Original Facility'));
        $table->addColumn('spaceNew', __('New Facility'));
        $table->addColumn('person', __('Person'))
            ->sortable(['preferredName', 'surname'])
            ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));
        
        $table->addActionColumn()
            ->addParam('gibbonTTSpaceChangeID')
            ->addParam('gibbonCourseClassID')
            ->format(function ($row, $actions) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable/spaceChange_manage_delete.php');
            });

        echo $table->render($facilityChanges);
    }
}
