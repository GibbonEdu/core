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
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Archives'), 'archive_manage.php')
        ->add(__('Edit Archive'));

    $gibbonReportArchiveID = $_GET['gibbonReportArchiveID'] ?? '';
    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);

    if (empty($gibbonReportArchiveID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportArchiveGateway->getByID($gibbonReportArchiveID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('archiveManage', $gibbon->session->get('absoluteURL').'/modules/Reports/archive_manage_editProcess.php');

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportArchiveID', $gibbonReportArchiveID);

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
        $row->addYesNo('viewableStaff')->required()->selected('N');

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

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
