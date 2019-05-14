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
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_approval.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Approve Staff Absences'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
    $gibbonStaffAbsenceTypeID = $_GET['gibbonStaffAbsenceTypeID'] ?? '';
    $search = $_GET['search'] ?? '';
    $dateStart = $_GET['dateStart'] ?? '';
    $dateEnd = $_GET['dateEnd'] ?? '';

    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);

    // QUERY
    $criteria = $staffAbsenceGateway->newQueryCriteria()
        ->searchBy($staffAbsenceGateway->getSearchableColumns(), $search)
        ->sortBy('status', 'ASC')
        ->fromPOST();

    $absences = $staffAbsenceGateway->queryAbsencesByApprover($criteria, $_SESSION[$guid]['gibbonPersonID']);

    // DATA TABLE
    $table = DataTable::createPaginated('staffAbsences', $criteria);
    $table->setTitle(__('View'));

    $table->modifyRows(function ($absence, $row) {
        if ($absence['status'] == 'Approved') $row->addClass('current');
        if ($absence['status'] == 'Declined') $row->addClass('error');
        return $row;
    });
    
    $table->addMetaData('filterOptions', [
        'date:upcoming'           => __('Upcoming'),
        'date:today'              => __('Today'),
        'date:past'               => __('Past'),
        'status:pending approval' => __('Status').': '.__('Pending Approval'),
        'status:approved'         => __('Status').': '.__('Approved'),
        'status:declined'         => __('Status').': '.__('Declined'),
    ]);

    // COLUMNS
    $table->addColumn('fullName', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($absence) use ($guid) {
            $text = Format::name($absence['title'], $absence['preferredName'], $absence['surname'], 'Staff', false, true);
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/absences_view_byPerson.php&gibbonPersonID='.$absence['gibbonPersonID'];

            return Format::link($url, $text);
        });

    $table->addColumn('date', __('Date'))
        ->width('18%')
        ->format([AbsenceFormats::class, 'dateDetails']);

    $table->addColumn('type', __('Type'))
        ->description(__('Reason'))
        ->format([AbsenceFormats::class, 'typeAndReason']);

    $table->addColumn('timestampCreator', __('Created'))
        ->format([AbsenceFormats::class, 'createdOn']);

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonStaffAbsenceID')
        ->format(function ($absence, $actions) {
            $actions->addAction('view', __('View Details'))
                ->isModal(800, 550)
                ->setURL('/modules/Staff/absences_view_details.php');

            if ($absence['status'] == 'Pending Approval') {
                $actions->addAction('approve', __('Approve'))
                    ->setIcon('iconTick')
                    ->addParam('status', 'Approved')
                    ->setURL('/modules/Staff/absences_approval_action.php');

                $actions->addAction('decline', __('Decline'))
                    ->setIcon('iconCross')
                    ->addParam('status', 'Declined')
                    ->setURL('/modules/Staff/absences_approval_action.php');
            }
        });

    echo $table->render($absences);
}
