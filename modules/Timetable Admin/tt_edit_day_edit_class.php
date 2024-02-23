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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\TimetableDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonTTDayID, gibbonTTID, gibbonSchoolYearID, and gibbonTTColumnRowID specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //Timetable, day, period

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowByID($gibbonTTDayID, $gibbonTTColumnRowID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            $urlParams = ['gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID];

            $page->breadcrumbs
                ->add(__('Manage Timetables'), 'tt.php', $urlParams)
                ->add(__('Edit Timetable'), 'tt_edit.php', $urlParams)
                ->add(__('Edit Timetable Day'), 'tt_edit_day_edit.php', $urlParams)
                ->add(__('Classes in Period'));


            // DISPLAY TIMETABLE DATA
            $table = DataTable::createDetails('ttDay');

            $table->addColumn('ttName', __('Timetable'));
            $table->addColumn('dayName', __('Day'));
            $table->addColumn('rowName', __('Period'));

            echo $table->render([$values]);

            $ttDayRowClasses = $timetableDayGateway->selectTTDayRowClassesByID($gibbonTTDayID, $gibbonTTColumnRowID);

            // DATA TABLE
            $table = DataTable::create('timetableDayRowClasses');
            $table->setTitle(__('Classes in Period'));

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_add.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID', $gibbonTTDayID)
                ->addParam('gibbonTTColumnRowID', $gibbonTTColumnRowID)
                ->displayLabel();

            $table->addColumn('class', __('Class'))->format(Format::using('courseClassName', ['courseName', 'className']));
            $table->addColumn('location', __('Location'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID', $gibbonTTDayID)
                ->addParam('gibbonTTColumnRowID', $gibbonTTColumnRowID)
                ->addParam('gibbonTTDayRowClassID')
                ->addParam('gibbonCourseClassID')
                ->format(function ($values, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_edit.php');
                        
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_delete.php');

                    $actions->addAction('exceptions', __('Exceptions'))
                        ->setIcon('attendance')
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit_class_exception.php');
                });

            echo $table->render($ttDayRowClasses->toDataSet());
        }
    }
}
