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
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'];
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'];
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowClassByID($gibbonTTDayID, $gibbonTTColumnRowID, $gibbonCourseClassID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $gibbonTTDayRowClassID = $values['gibbonTTDayRowClassID'];

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > ... > ... > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'>".__($guid, 'Edit Timetable')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Edit Timetable Day')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit_class.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID'>".__($guid, 'Classes in Period')."</a> > </div><div class='trailEnd'>".__($guid, 'Class List Exception').'</div>';
            echo '</div>';

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
