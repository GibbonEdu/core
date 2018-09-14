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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/userSettings.php'>".__($guid, 'Manage User Settings')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Username Format').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    $gibbonUsernameFormatID = isset($_GET['gibbonUsernameFormatID'])? $_GET['gibbonUsernameFormatID'] : '';

    if (empty($gibbonUsernameFormatID)) {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
        return;
    }

    $data = array('gibbonUsernameFormatID' => $gibbonUsernameFormatID);
    $sql = "SELECT * FROM gibbonUsernameFormat WHERE gibbonUsernameFormatID=:gibbonUsernameFormatID";
    $result = $pdo->executeQuery($data, $sql);

    if ($result->rowCount() == 0) {
        echo "<div class='error'>";
        echo __($guid, 'The specified record cannot be found.');
        echo '</div>';
        return;
    }

    $values = $result->fetch();
    $values['gibbonRoleIDList'] = explode(',', $values['gibbonRoleIDList']);
    $values['numericValue'] = str_pad($values['numericValue'], $values['numericSize'], '0', STR_PAD_LEFT);

    $form = Form::create('usernameFormat', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/userSettings_usernameFormat_editProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonUsernameFormatID', $gibbonUsernameFormatID);

    $data = array('gibbonUsernameFormatID' => $gibbonUsernameFormatID);
    $sql = "SELECT gibbonRole.gibbonRoleID as value, gibbonRole.name FROM gibbonRole LEFT JOIN gibbonUsernameFormat ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonUsernameFormat.gibbonRoleIDList) AND gibbonUsernameFormatID<>:gibbonUsernameFormatID) WHERE gibbonUsernameFormatID IS NULL ORDER BY gibbonRole.name";
    $result = $pdo->executeQuery($data, $sql);

    $row = $form->addRow();
        $row->addLabel('format', __('Username Format'))->description(__('How should usernames be formated? Choose from [preferredName], [firstName], [surname].').'<br>'.__('Use a colon to limit the number of letters, for example [preferredName:1] will use the first initial.'));
        $row->addTextField('format')->isRequired();

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Roles'));
        $row->addSelect('gibbonRoleIDList')
            ->isRequired()
            ->selectMultiple()
            ->setSize(4)
            ->fromResults($result);

    $row = $form->addRow();
        $row->addLabel('isDefault', __('Is Default?'));
        $row->addYesNo('isDefault');

    $row = $form->addRow();
        $row->addLabel('isNumeric', __('Numeric?'))->description(__('Enables the format [number] to insert a numeric value into your username.'));
        $row->addYesNo('isNumeric');

    $form->toggleVisibilityByClass('numericValueSettings')->onSelect('isNumeric')->when('Y');

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericValue', __('Starting Value'))->description(__('Each time a username is generated this value will increase by the increment defined below.'));
        $row->addTextField('numericValue')->isRequired()->maxLength(12);

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericSize', __('Number of Digits'));
        $row->addNumber('numericSize')->isRequired()->minimum(0)->maximum(12);

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericIncrement', __('Increment By'));
        $row->addNumber('numericIncrement')->isRequired()->minimum(0)->maximum(100);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
