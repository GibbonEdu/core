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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Special Days')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Special Day').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearSpecialDayID = (isset($_GET['gibbonSchoolYearSpecialDayID']))? $_GET['gibbonSchoolYearSpecialDayID'] : null;
    $gibbonSchoolYearTermID = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : null;
    $gibbonSchoolYearID = (isset($_GET['gibbonSchoolYearID']))? $_GET['gibbonSchoolYearID'] : null;

    if (empty($gibbonSchoolYearSpecialDayID) && empty($gibbonSchoolYearID)) {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearSpecialDayID' => $gibbonSchoolYearSpecialDayID);
            $sql = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearSpecialDayID=:gibbonSchoolYearSpecialDayID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('specialDayAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage_editProcess.php?gibbonSchoolYearSpecialDayID='.$gibbonSchoolYearSpecialDayID.'&gibbonSchoolYearID='.$gibbonSchoolYearID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            $form->addHiddenValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);

            $row = $form->addRow();
                $row->addLabel('dateDisplay', __('Date'))->description(__('Must be unique.'));
                $row->addTextField('dateDisplay')->readonly()->setValue(dateConvertBack($guid, $values['date']));

            $types = array(
                'School Closure' => __('School Closure'),
                'Timing Change' => __('Timing Change'),
            );

            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromArray($types)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->maxLength(20);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextField('description')->maxLength(255);

            $form->toggleVisibilityByClass('timingChange')->onSelect('type')->when('Timing Change');

            $hoursArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 23));
            $hours = implode(',', $hoursArray);

            $minutesArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 59));
            $minutes = implode(',', $minutesArray);

            if (!empty($values['schoolOpen']) && stripos($values['schoolOpen'], ':') !== false) {
                list($values['schoolOpenH'], $values['schoolOpenM'], $sec) = explode(':', $values['schoolOpen']);
            }

            $row = $form->addRow()->addClass('timingChange');
                $row->addLabel('schoolOpen', __('School Opens'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect('schoolOpenH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
                $col->addSelect('schoolOpenM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

            if (!empty($values['schoolStart']) && stripos($values['schoolStart'], ':') !== false) {
                list($values['schoolStartH'], $values['schoolStartM'], $sec) = explode(':', $values['schoolStart']);
            }

            $row = $form->addRow()->addClass('timingChange');
                $row->addLabel('schoolStart', __('School Starts'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect('schoolStartH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
                $col->addSelect('schoolStartM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

            if (!empty($values['schoolEnd']) && stripos($values['schoolEnd'], ':') !== false) {
                list($values['schoolEndH'], $values['schoolEndM'], $sec) = explode(':', $values['schoolEnd']);
            }

            $row = $form->addRow()->addClass('timingChange');
                $row->addLabel('schoolEnd', __('School Ends'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect('schoolEndH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
                $col->addSelect('schoolEndM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

            if (!empty($values['schoolClose']) && stripos($values['schoolClose'], ':') !== false) {
                list($values['schoolCloseH'], $values['schoolCloseM'], $sec) = explode(':', $values['schoolClose']);
            }

            $row = $form->addRow()->addClass('timingChange');
                $row->addLabel('schoolClose', __('School Closes'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect('schoolCloseH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
                $col->addSelect('schoolCloseM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
