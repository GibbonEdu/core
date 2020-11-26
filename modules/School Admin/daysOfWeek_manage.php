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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/daysOfWeek_manage.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Days of the Week'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    
        $data = array();
        $sql = "SELECT * FROM gibbonDaysOfWeek WHERE name='Monday' OR name='Tuesday' OR name='Wednesday' OR name='Thursday' OR name='Friday' OR name='Saturday' OR name='Sunday' ORDER BY sequenceNumber";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() != 7) {
        echo "<div class='error'>";
        echo __('There is a problem with your database information for school days.');
        echo '</div>';
    } else {
        //Let's go!

        $form = Form::create('daysOfWeek', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/daysOfWeek_manageProcess.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $hoursArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 23));
        $hours = implode(',', $hoursArray);

        $minutesArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 59));
        $minutes = implode(',', $minutesArray);

        while ($day = $result->fetch()) {
            $form->addHiddenValue($day['name'].'sequenceNumber', $day['sequenceNumber']);

            $form->addRow()->addHeading(__($day['name']).' ('.__($day['nameShort']).')');

            $row = $form->addRow();
                $row->addLabel($day['name'].'schoolDay', __('School Day'));
                $row->addYesNo($day['name'].'schoolDay')->required()->selected($day['schoolDay']);

            $form->toggleVisibilityByClass($day['name'])->onSelect($day['name'].'schoolDay')->when('Y');

            $row = $form->addRow()->addClass($day['name']);
                $row->addLabel($day['name'].'schoolOpen', __('School Opens'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect($day['name'].'schoolOpenH')
                    ->fromString($hours)
                    ->required()
                    ->setClass('shortWidth')
                    ->placeholder(__('Hours'))
                    ->selected(substr($day['schoolOpen'], 0, 2));
                $col->addSelect($day['name'].'schoolOpenM')
                    ->fromString($minutes)
                    ->setClass('shortWidth')
                    ->placeholder(__('Minutes'))
                    ->selected(substr($day['schoolOpen'], 3, 2));

            $row = $form->addRow()->addClass($day['name']);
                $row->addLabel($day['name'].'schoolStart', __('School Starts'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect($day['name'].'schoolStartH')
                    ->fromString($hours)
                    ->required()
                    ->setClass('shortWidth')
                    ->placeholder(__('Hours'))
                    ->selected(substr($day['schoolStart'], 0, 2));
                $col->addSelect($day['name'].'schoolStartM')
                    ->fromString($minutes)
                    ->setClass('shortWidth')
                    ->placeholder(__('Minutes'))
                    ->selected(substr($day['schoolStart'], 3, 2));

            $row = $form->addRow()->addClass($day['name']);
                $row->addLabel($day['name'].'schoolEnd', __('School Ends'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect($day['name'].'schoolEndH')
                    ->fromString($hours)
                    ->required()
                    ->setClass('shortWidth')
                    ->placeholder(__('Hours'))
                    ->selected(substr($day['schoolEnd'], 0, 2));
                $col->addSelect($day['name'].'schoolEndM')
                    ->fromString($minutes)
                    ->setClass('shortWidth')
                    ->placeholder(__('Minutes'))
                    ->selected(substr($day['schoolEnd'], 3, 2));

            $row = $form->addRow()->addClass($day['name']);
                $row->addLabel($day['name'].'schoolClose', __('School Closes'));
                $col = $row->addColumn()->addClass('right inline');
                $col->addSelect($day['name'].'schoolCloseH')
                    ->fromString($hours)
                    ->required()
                    ->setClass('shortWidth')
                    ->placeholder(__('Hours'))
                    ->selected(substr($day['schoolClose'], 0, 2));
                $col->addSelect($day['name'].'schoolCloseM')
                    ->fromString($minutes)
                    ->setClass('shortWidth')
                    ->placeholder(__('Minutes'))
                    ->selected(substr($day['schoolClose'], 3, 2));
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
