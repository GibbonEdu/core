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

@session_start();

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/space_manage.php'>".__($guid, 'Manage Facilities')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Facility').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/space_manage_edit.php&gibbonSpaceID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('spaceAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/space_manage_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->isRequired()->maxLength(30);

    $types = getSettingByScope($connection2, 'School Admin', 'facilityTypes');

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromString($types)->isRequired()->placeholder();

    $row = $form->addRow();
        $row->addLabel('capacity', __('Capacity'));
        $row->addNumber('capacity')->maxLength(5)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('computer', __('Teacher\'s Computer'));
        $row->addYesNo('computer')->selected('N');

    $row = $form->addRow();
        $row->addLabel('computerStudent', __('Student Computers'))->description(__('How many are there'));
        $row->addNumber('computerStudent')->maxLength(5)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('projector', __('Projector'));
        $row->addYesNo('projector')->selected('N');

    $row = $form->addRow();
        $row->addLabel('tv', __('TV'));
        $row->addYesNo('tv')->selected('N');

    $row = $form->addRow();
        $row->addLabel('dvd', __('DVD Player'));
        $row->addYesNo('dvd')->selected('N');

    $row = $form->addRow();
        $row->addLabel('hifi', __('Hifi'));
        $row->addYesNo('hifi')->selected('N');

    $row = $form->addRow();
        $row->addLabel('speakers', __('Speakers'));
        $row->addYesNo('speakers')->selected('N');

    $row = $form->addRow();
        $row->addLabel('iwb', __('Interactive White Board'));
        $row->addYesNo('iwb')->selected('N');

    $row = $form->addRow();
        $row->addLabel('phoneInternal', __('Extension'))->description(__('Room\'s internal phone number.'));
        $row->addTextField('phoneInternal')->maxLength(5);

    $row = $form->addRow();
        $row->addLabel('phoneExternal', __('Phone Number'))->description(__('Room\'s external phone number.'));
        $row->addTextField('phoneExternal')->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'));
        $row->addTextArea('comment')->setRows(8);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
