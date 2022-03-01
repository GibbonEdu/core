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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\TimetableGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Timetables'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $timetableGateway = $container->get(TimetableGateway::class);
        $timetables = $timetableGateway->selectTimetablesBySchoolYear($gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::create('timetables');

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Timetable Admin/tt_add.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->displayLabel();

        $table->modifyRows(function ($tt, $row) {
            if ($tt['active'] == 'N') $row->addClass('error');
            return $row;
        });

        $table->addColumn('name', __('Name'));
        $table->addColumn('nameShort', __('Short Name'));
        $table->addColumn('yearGroups', __('Year Groups'));
        $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonTTID')
            ->addParam('gibbonSchoolYearID')
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Timetable Admin/tt_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Timetable Admin/tt_delete.php');

                $actions->addAction('import', __('Import'))
                    ->setIcon('upload')
                    ->setURL('/modules/Timetable Admin/tt_import.php');

                $actions->addAction('notify', __('Notify students'))
                    ->setIcon('copyforward')
                    ->setURL('/modules/Timetable Admin/tt_update_notifier.php');
            });

        echo $table->render($timetables->toDataSet());
    }
}
