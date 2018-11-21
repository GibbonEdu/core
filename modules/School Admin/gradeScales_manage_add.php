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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Grade Scales'), 'gradeScales_manage.php')
        ->add(__('Add Grade Scale'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/gradeScales_manage_edit.php&gibbonScaleID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('gradeScaleAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->isRequired()->maxLength(40);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
        $row->addTextField('nameShort')->isRequired()->maxLength(5);

    $row = $form->addRow();
        $row->addLabel('usage', __('Usage'))->description(__('Brief description of how scale is used.'));
        $row->addTextField('usage')->isRequired()->maxLength(50);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->isRequired();

    $row = $form->addRow();
        $row->addLabel('numeric', __('Numeric'))->description(__('Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.'));
        $row->addYesNo('numeric')->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
