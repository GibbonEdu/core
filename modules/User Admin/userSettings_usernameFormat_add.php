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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('User Settings'),'userSettings.php')
        ->add(__('Add Username Format'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/userSettings_usernameFormat_edit.php&gibbonUsernameFormatID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);


    $form = Form::create('usernameFormat', $session->get('absoluteURL').'/modules/'.$session->get('module').'/userSettings_usernameFormat_addProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $sql = "SELECT gibbonRole.gibbonRoleID as value, gibbonRole.name FROM gibbonRole LEFT JOIN gibbonUsernameFormat ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonUsernameFormat.gibbonRoleIDList)) WHERE gibbonUsernameFormatID IS NULL ORDER BY gibbonRole.name";
    $result = $pdo->executeQuery(array(), $sql);

    $row = $form->addRow();
        $row->addLabel('format', __('Username Format'))->description(__('How should usernames be formated? Choose from [preferredName], [firstName], [surname].').'<br>'.__('Use a colon to limit the number of letters, for example [preferredName:1] will use the first initial.'));
        $row->addTextField('format')->required()->setValue('[preferredName:1][surname]');

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Roles'));
        $row->addSelect('gibbonRoleIDList')
            ->required()
            ->selectMultiple()
            ->setSize(4)
            ->fromResults($result);

    $row = $form->addRow();
        $row->addLabel('isDefault', __('Is Default?'));
        $row->addYesNo('isDefault')->selected('N');

    $row = $form->addRow();
        $row->addLabel('isNumeric', __('Numeric?'))->description(__('Enables the format [number] to insert a numeric value into your username.'));
        $row->addYesNo('isNumeric')->selected('N');

    $form->toggleVisibilityByClass('numericValueSettings')->onSelect('isNumeric')->when('Y');

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericValue', __('Starting Value'))->description(__('Each time a username is generated this value will increase by the increment defined below.'));
        $row->addTextField('numericValue')->required()->setValue('0')->maxLength(12);

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericSize', __('Number of Digits'));
        $row->addNumber('numericSize')->required()->setValue('4')->minimum(0)->maximum(12);

    $row = $form->addRow()->addClass('numericValueSettings');
        $row->addLabel('numericIncrement', __('Increment By'));
        $row->addNumber('numericIncrement')->required()->setValue('1')->minimum(0)->maximum(100);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
