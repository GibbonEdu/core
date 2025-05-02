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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__m('Manage Units'), 'units_manage.php')
        ->add(__m('Import Units'));

    $form = Form::create('importUnits', $session->get('absoluteURL').'/modules/Free Learning/units_manage_importProcess.php');
    $form->setTitle(__m('Import Units'));
    $form->setDescription(__m('This page lets you import zip archives that have been created with the unit export tool.'));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
        $row->addLabel('file', __('ZIP File'));
        $row->addFileUpload('file')->required()->accepts('.zip');

    $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonDepartmentIDList', __('Learning Areas'))->description(__m('Optionally add all units to the selected Learning Area.'));
        $row->addSelect('gibbonDepartmentIDList')->fromQuery($pdo, $sql)->selectMultiple()->setSize(5);

    $row = $form->addRow();
        $row->addLabel('course', __m('Course'))->description(__m('Optionally add all units to the selected Course.'));
        $row->addTextField('course');

    $row = $form->addRow();
        $row->addLabel('override', __m('Allow override?'))->description(__m('Units with the same name will be updated with the imported content.'));
        $row->addCheckbox('override')->setValue('Y');

    $form->toggleVisibilityByClass('delete')->onCheckbox('override')->when('Y');
    $row = $form->addRow()->addClass('delete');;
        $row->addLabel('delete', __m('Delete Existing Smart Blocks?'));
        $row->addCheckbox('delete')->setValue('Y');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
