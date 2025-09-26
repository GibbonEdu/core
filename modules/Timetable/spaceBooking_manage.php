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
use Gibbon\Domain\Timetable\FacilityBookingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs->add(__('Manage Facility Bookings'));

        if ($highestAction == 'Manage Facility Bookings_allBookings') {
            echo '<p>'.__('This page allows you to create facility and library bookings, whilst managing bookings created by all users. Only current and future bookings are shown: past bookings are hidden.').'</p>';
        } else {
            echo '<p>'.__('This page allows you to create and manage facility and library bookings. Only current and future changes are shown: past bookings are hidden.').'</p>';
        }

        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $facilityBookingGateway = $container->get(FacilityBookingGateway::class);

        $criteria = $facilityBookingGateway->newQueryCriteria(true)
            ->searchBy($facilityBookingGateway->getSearchableColumns(), $search)
            ->sortBy(['date', 'name'])
            ->fromPOST();

        // SEARCH FORM
        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');

        $form->setTitle(__('Search'));
        $form->setClass('noIntBorder w-full');

        $form->addHiddenValue('q', '/modules/Timetable/spaceBooking_manage.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Facility, preferred name, surname'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addSearchSubmit($session, 'Clear Search');

        echo $form->getOutput();

        if ($highestAction == 'Manage Facility Bookings_allBookings') {
            $facilityBookings = $facilityBookingGateway->queryFacilityBookings($criteria);
        } else {
            $facilityBookings = $facilityBookingGateway->queryFacilityBookings($criteria, $session->get('gibbonPersonID'));
        }

        // DATA TABLE
        $table = DataTable::createPaginated('facilityBookings', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable/spaceBooking_manage_add.php')
            ->displayLabel();

        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', 'date'));
        $table->addColumn('name', __('Facility'))
            ->format(function($row) use ($session) {
                if ($row['foreignKey']=='gibbonSpaceID') {
                    $output = Format::link($session->get('absoluteURL').'/index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.str_pad($row['foreignKeyID'], 10, '0', STR_PAD_LEFT).'&ttDate='.Format::date($row['date']), $row['name']);
                } else {
                    $output = $row['name'];
                }

                return $output.'<br/><small><i>'
                     .($row['foreignKey'] == 'gibbonLibraryItemID'? __('Library') :'').'</i></small>';
            });
        $table->addColumn('time', __('Time'))
            ->sortable(['timeStart', 'timeEnd'])
            ->format(Format::using('timeRange', ['timeStart', 'timeEnd']));

        $table->addColumn('person', __('Booked For'))
            ->sortable(['preferredName', 'surname'])
            ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]))
            ->formatDetails(function ($values) {
                return Format::small(Format::truncate($values['reason'], 60));
            });

        $table->addActionColumn()
            ->addParam('gibbonTTSpaceBookingID')
            ->format(function ($row, $actions) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable/spaceBooking_manage_delete.php');
            });

        echo $table->render($facilityBookings);
    }
}
