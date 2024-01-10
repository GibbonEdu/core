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
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Module\Staff\Tables\AbsenceFormats;
use Gibbon\Module\Staff\Tables\AbsenceCalendar;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View Absences'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $settingGateway = $container->get(SettingGateway::class);
    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    if ($highestAction == 'View Absences_any') {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID');

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('q', '/modules/Staff/absences_view_byPerson.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    } else {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    
    $absences = $staffAbsenceDateGateway->selectApprovedAbsenceDatesByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetchGrouped();
    $schoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);

    $coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');

    // CALENDAR VIEW
    $table = AbsenceCalendar::create($absences, $schoolYear['firstDay'], $schoolYear['lastDay']);
    echo $table->getOutput().'<br/>';

    // COUNT TYPES
    $absenceTypes = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();
    $types = array_fill_keys(array_column($absenceTypes, 'name'), 0);

    foreach ($absences as $days) {
        foreach ($days as $absence) {
            $types[$absence['type']] += $absence['value'];
        }
    }

    $table = DataTable::create('staffAbsenceTypes');

    foreach ($types as $name => $count) {
        $table->addColumn($name, $name)->context('primary')->width((100 / count($types)).'%');
    }

    echo $table->render(new DataSet([$types]));

    // QUERY
    $criteria = $staffAbsenceGateway->newQueryCriteria(true)
        ->sortBy('date', 'DESC')
        ->filterBy('schoolYear', $gibbonSchoolYearID)
        ->fromPOST();

    $absences = $staffAbsenceGateway->queryAbsencesByPerson($criteria, $gibbonPersonID);

    // Join a set of coverage data per absence
    $absenceIDs = $absences->getColumn('gibbonStaffAbsenceID');
    $coverageData = $staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($absenceIDs)->fetchGrouped();
    $absences->joinColumn('gibbonStaffAbsenceID', 'coverageList', $coverageData);

    // DATA TABLE
    $table = DataTable::createPaginated('staffAbsences', $criteria);
    $table->setTitle(__('View'));

    $table->modifyRows(function ($absence, $row) {
        if ($absence['status'] == 'Pending Approval') $row->addClass('warning');
        if ($absence['status'] == 'Declined') $row->addClass('dull');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'schoolYear:'.$gibbonSchoolYearID => __('School Year').': '.__('Current'),
    ]);

    $table->addHeaderAction('add', __('New Absence'))
        ->setURL('/modules/Staff/absences_add.php')
        ->addParam('gibbonPersonID', $gibbonPersonID)
        ->displayLabel();

    // COLUMNS
    $table->addColumn('date', __('Date'))
        ->format([AbsenceFormats::class, 'dateDetails']);
    
    $table->addColumn('type', __('Type'))
        ->description(__('Reason'))
        ->format([AbsenceFormats::class, 'typeAndReason']);
    
    $table->addColumn('coverage', __('Coverage'))
        ->format([AbsenceFormats::class, 'coverageList']);

    $table->addColumn('timestampCreator', __('Created'))
        ->width('20%')
        ->format([AbsenceFormats::class, 'createdOn']);

    // ACTIONS
    $canManage = isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage.php');
    $canRequest = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php');

    $table->addActionColumn()
        ->addParam('gibbonStaffAbsenceID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($absence, $actions) use ($canManage, $canRequest, $coverageMode) {
            $noApprovalRequired = ($coverageMode == 'Requested' && $absence['status'] == 'Approved') || $coverageMode == 'Assigned';
            if ($canRequest && $noApprovalRequired && $absence['dateEnd'] >= date('Y-m-d')) {
                $actions->addAction('coverage', __('Request Coverage'))
                    ->setIcon('attendance')
                    ->setURL('/modules/Staff/coverage_request.php');
            }

            $actions->addAction('view', __('View Details'))
                ->isModal(800, 550)
                ->setURL('/modules/Staff/absences_view_details.php');

            if ($canManage) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Staff/absences_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Staff/absences_manage_delete.php');
            }
        });

    echo $table->render($absences);
}
