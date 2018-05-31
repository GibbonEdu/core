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
use Gibbon\Domain\Timetable\TimetableDayGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/tt.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Timetables')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Timetable').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $timetableGateway = $container->get(TimetableGateway::class);

    //Check if school year specified
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonTTID == '' || $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $values = $timetableGateway->getTTByID($gibbonTTID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/tt_editProcess.php');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonTTID', $gibbonTTID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
                $row->addTextField('schoolYear')->maxLength(20)->isRequired()->readonly()->setValue($values['schoolYear']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
                $row->addTextField('name')->maxLength(30)->isRequired();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->maxLength(12)->isRequired();

            $row = $form->addRow();
                $row->addLabel('nameShortDisplay', __('Day Column Name'));
                $row->addSelect('nameShortDisplay')->fromArray(array('Day Of The Week' => __('Day Of The Week'), 'Timetable Day Short Name' => __('Timetable Day Short Name')))->isRequired();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->isRequired();

            $yearGroupsOptions = $timetableGateway->getNonTimetabledYearGroups($gibbonSchoolYearID, $gibbonTTID);
            $row = $form->addRow();
                $row->addLabel('active', __('Year Groups'))->description(__('Groups not in an active TT this year.'));
                if (empty($yearGroupsOptions)) {
                    $row->addContent('<i>'.__('No year groups available.').'</i>')->addClass('right');
                } else {
                    $gibbonYearGroupIDList = explode(',', $values['gibbonYearGroupIDList']);
                    $checked = array_filter(array_keys($yearGroupsOptions), function ($item) use ($gibbonYearGroupIDList) {
                        return in_array($item, $gibbonYearGroupIDList);
                    });

                    $row->addCheckbox('gibbonYearGroupID')->fromArray($yearGroupsOptions)->checked($checked);
                }
            $form->addHiddenValue('count', count($yearGroupsOptions));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Timetable Days');
            echo '</h2>';

            $timetableDayGateway = $container->get(TimetableDayGateway::class);
            $ttDays = $timetableDayGateway->selectTTDaysByID($gibbonTTID);

            // DATA TABLE
            $table = DataTable::create('timetableDays');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/tt_edit_day_add.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('nameShort', __('Short Name'));
            $table->addColumn('columnName', __('Column'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('gibbonTTDayID')
                ->format(function ($values, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/tt_edit_day_delete.php');
                });

            echo $table->render($ttDays->toDataSet());
        }
    }
}
