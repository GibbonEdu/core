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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\FacilityBookingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Facility Bookings').'</div>';
        echo '</div>';

        if ($highestAction == 'Manage Facility Bookings_allBookings') {
            echo '<p>'.__($guid, 'This page allows you to create facility and library bookings, whilst managing bookings created by all users. Only current and future bookings are shown: past bookings are hidden.').'</p>';
        } else {
            echo '<p>'.__($guid, 'This page allows you to create and manage facility and library bookings. Only current and future changes are shown: past bookings are hidden.').'</p>';
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $facilityBookingGateway = $container->get(FacilityBookingGateway::class);

        $criteria = $facilityBookingGateway->newQueryCriteria()
            ->sortBy(['date', 'name'])
            ->fromArray($_POST);

        if ($highestAction == 'Manage Facility Bookings_allBookings') {
            $facilityBookings = $facilityBookingGateway->queryFacilityBookings($criteria);
        } else {
            $facilityBookings = $facilityBookingGateway->queryFacilityBookings($criteria, $_SESSION[$guid]['gibbonPersonID']);
        }

        // DATA TABLE
        $table = DataTable::createPaginated('facilityBookings', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable/spaceBooking_manage_add.php')
            ->displayLabel();

        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', 'date'));
        $table->addColumn('name', __('Facility'))
            ->format(function($row) {
                return $row['name'].'<br/><small><i>'
                     .($row['foreignKey'] == 'gibbonLibraryItemID'? __('Library') :'').'</i></small>';
            });
        $table->addColumn('time', __('Time'))
            ->sortable(['timeStart', 'timeEnd'])
            ->format(Format::using('timeRange', ['timeStart', 'timeEnd']));
        $table->addColumn('person', __('Person'))
            ->sortable(['preferredName', 'surname'])
            ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));

        $table->addActionColumn()
            ->addParam('gibbonTTSpaceBookingID')
            ->format(function ($row, $actions) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable/spaceBooking_manage_delete.php');
            });

        echo $table->render($facilityBookings);
    }
}
