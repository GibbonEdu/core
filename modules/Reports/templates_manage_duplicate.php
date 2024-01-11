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
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Duplicate Template'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_edit.php&sidebar=false&gibbonReportTemplateID='.$_GET['editID']);
    }

    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $reportTemplateGateway = $container->get(ReportTemplateGateway::class);

    if (empty($gibbonReportTemplateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportTemplateGateway->getByID($gibbonReportTemplateID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }


    $form = Form::create('$reportingTemplates', $session->get('absoluteURL').'/modules/Reports/templates_manage_duplicateProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->maxLength(90)->required()->setValue($values['name'].' '.__('Copy'));

    $contexts = [
        'Reporting Cycle'   => __('Reporting Cycle'),
        'Student Enrolment' => __('Student Enrolment'),
        // 'Custom Query' => __('Custom Query'),
    ];
    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addSelect('context')->fromArray($contexts)->required()->placeholder()->selected($values['context']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
