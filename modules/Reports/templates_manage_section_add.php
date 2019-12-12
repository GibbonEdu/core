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
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_section_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $type = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php', ['search' => $search])
        ->add(__('Edit Template'), 'templates_manage_edit.php', ['gibbonReportTemplateID' => $gibbonReportTemplateID, 'search' => $search, 'sidebar' => 'false'])
        ->add(__('Add Section'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (empty($gibbonReportTemplateID) || empty($type)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

    $form = Form::create('templatesManage', $gibbon->session->get('absoluteURL').'/modules/Reports/templates_manage_section_addProcess.php');

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);
    $form->addHiddenValue('type', $type);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(90)->required();

    $prototypeSections = $templateSectionGateway->selectPrototypeSectionsByType($type);

    $row = $form->addRow();
        $row->addLabel('gibbonReportPrototypeSectionID', __('Section'));
        $row->addSelect('gibbonReportPrototypeSectionID')->fromResults($prototypeSections, 'category')->required()->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
