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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class.php') == false) {
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

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Timetable, day, period

        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $values = $timetableDayGateway->getTTDayRowByID($gibbonTTDayID, $gibbonTTColumnRowID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/tt.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Timetables')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'>".__($guid, 'Edit Timetable')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Edit Timetable Day')."</a> > </div><div class='trailEnd'>".__($guid, 'Classes in Period').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Timetable').'</span><br/>';
            echo $values['ttName'];
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Day').'</span><br/>';
            echo $values['dayName'];
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Period').'</span><br/>';
            echo $values['rowName'];
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            $ttDayRowClasses = $timetableDayGateway->selectTTDayRowClassesByID($gibbonTTDayID, $gibbonTTColumnRowID);

            // DATA TABLE
            $table = DataTable::create('timetableDayRowClasses');

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
