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
use Gibbon\Domain\School\MedicalConditionGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/medicalConditions_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Medical Conditions'), 'medicalConditions_manage.php')
        ->add(__('Add Condition'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/medicalConditions_manage_edit.php&gibbonMedicalConditionID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('medicalConditionManage', $session->get('absoluteURL').'/modules/'.$session->get('module').'/medicalConditions_manage_addProcess.php?');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxlength(80)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Medical condition descriptions are displayed next to the condition and can offer additional background information.'));
        $row->addTextArea('description');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
