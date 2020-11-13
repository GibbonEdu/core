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
use Gibbon\Domain\Timetable\TimetableDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_exception.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowClassByID($gibbonTTDayID, $gibbonTTColumnRowID, $gibbonCourseClassID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $gibbonTTDayRowClassID = $values['gibbonTTDayRowClassID'];

            $urlParams = [
                'gibbonTTDayID' => $gibbonTTDayID,
                'gibbonTTID' => $gibbonTTID,
                'gibbonSchoolYearID' => $gibbonSchoolYearID,
                'gibbonTTColumnRowID' => $gibbonTTColumnRowID
            ];

            $page->breadcrumbs
                ->add(__('Manage Timetables'), 'tt.php', $urlParams)
                ->add(__('Edit Timetable'), 'tt_edit.php', $urlParams)
                ->add(__('Edit Timetable Day'), 'tt_edit_day_edit.php', $urlParams)
                ->add(__('Classes in Period'), 'tt_edit_day_edit_class.php', $urlParams)
                ->add(__('Class List Exception'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $ttDayRowClassExceptions = $timetableDayGateway->selectTTDayRowClassExceptionsByID($gibbonTTDayRowClassID);

            // DATA TABLE
            $table = DataTable::create('timetableDayRowClassExceptions');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_exception_add.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID', $gibbonTTDayID)
                ->addParam('gibbonTTColumnRowID', $gibbonTTColumnRowID)
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->addParam('gibbonTTDayRowClassID', $gibbonTTDayRowClassID)
                ->displayLabel();

            $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID', $gibbonTTDayID)
                ->addParam('gibbonTTColumnRowID', $gibbonTTColumnRowID)
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->addParam('gibbonTTDayRowClassID', $gibbonTTDayRowClassID)
                ->addParam('gibbonTTDayRowClassExceptionID')
                ->format(function ($values, $actions) {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_exception_delete.php');
                });

            echo $table->render($ttDayRowClassExceptions->toDataSet());
        }
    }
}
