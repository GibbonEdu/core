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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_scopes_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Reporting Cycles'), 'reporting_cycles_manage.php')
        ->add(__('Reporting Scopes'), 'reporting_scopes_manage.php', ['gibbonReportingCycleID' => $gibbonReportingCycleID])
        ->add(__('Add Scope'));

    if (empty($gibbonReportingCycleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_scopes_manage_edit.php&gibbonReportingCycleID='.$gibbonReportingCycleID.'&gibbonReportingScopeID='.$_GET['editID'];
    }

    $page->return->setEditLink($editLink);

    $form = Form::create('archiveManage', $session->get('absoluteURL').'/modules/Reports/reporting_scopes_manage_addProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingCycleID', $gibbonReportingCycleID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $types = ['Year Group' => __('Year Group'), 'Form Group' => __('Form Group'), 'Course' => __('Course')];
    $row = $form->addRow();
        $row->addLabel('scopeType', __('Type'));
        $row->addSelect('scopeType')->fromArray($types)->required()->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
