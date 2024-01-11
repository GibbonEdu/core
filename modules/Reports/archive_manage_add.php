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

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Archives'), 'archive_manage.php')
        ->add(__('Add Archive'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_manage_edit.php&gibbonReportArchiveID='.$_GET['editID'];
    }

    $page->return->setEditLink($editLink);

    $form = Form::create('archiveManage', $session->get('absoluteURL').'/modules/Reports/archive_manage_addProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('path', __('Path'));
        $row->addTextField('path')->maxLength(255)->required();

    $row = $form->addRow();
        $row->addLabel('readonly', __('Read Only'));
        $row->addYesNo('readonly')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableStaff', __('Viewable to Staff'));
        $row->addYesNo('viewableStaff')->required()->selected('Y');

    $row = $form->addRow();
        $row->addLabel('viewableStudents', __('Viewable to Students'));
        $row->addYesNo('viewableStudents')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableParents', __('Viewable to Parents'));
        $row->addYesNo('viewableParents')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableOther', __('Viewable to Other'));
        $row->addYesNo('viewableOther')->required()->selected('N');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
