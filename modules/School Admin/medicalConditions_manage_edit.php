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
use Gibbon\Domain\School\MedicalConditionGateway;
use Gibbon\Tables\Action;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/medicalConditions_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Medical Conditions'), 'medicalConditions_manage.php')
        ->add(__('Edit Condition'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonMedicalConditionID = $_GET['gibbonMedicalConditionID'] ?? '';

    if (empty($gibbonMedicalConditionID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(MedicalConditionGateway::class)->getByID($gibbonMedicalConditionID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('medicalConditionsManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/medicalConditions_manage_editProcess.php");

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonMedicalConditionID', $gibbonMedicalConditionID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxlength(80);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Medical condition descriptions are displayed next to the condition and can offer additional background information.'));
        $row->addTextArea('description');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

     $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
?>
