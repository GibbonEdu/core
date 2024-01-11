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
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Reporting Cycles'), 'reporting_cycles_manage.php')
        ->add(__('Edit Reporting Cycle'));

    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    if (empty($gibbonReportingCycleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportingCycleGateway->getByID($gibbonReportingCycleID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('archiveManage', $session->get('absoluteURL').'/modules/Reports/reporting_cycles_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingCycleID', $gibbonReportingCycleID);
    $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->maxLength(20)->required();

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->required();

    $row = $form->addRow();
        $row->addLabel('cycleNumber', __('Cycle Number'));
        $row->addNumber('cycleNumber')->onlyInteger(true)->required();

    $row = $form->addRow();
        $row->addLabel('cycleTotal', __('Total Cycles'));
        $row->addNumber('cycleTotal')->onlyInteger(true)->required();

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('notes', __('Notes'));
        $column->addTextArea('notes')->setRows(5)->setClass('w-full');

    // MILESTONES
    $form->addRow()->addHeading('Milestones', __('Milestones'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__('Add Milestone'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow();
        $row->addTextField('milestoneName')->setClass('w-2/3 pr-10 title')->required()->placeholder(__('Name'));
    $row = $blockTemplate->addRow();
        $row->addDate('milestoneDate')->setClass('w-48 mt-1')->required()->placeholder(__('Date'));

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('milestones', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true))
        ->placeholder(__('Milestones will be listed here...'))
        ->addToolInput($addBlockButton);

    // Add existing milestones
    $milestones = json_decode($values['milestones'], true);
    foreach ($milestones ?? [] as $index => $milestone) {
        $milestone['milestoneDate'] = Format::date($milestone['milestoneDate']);
        $customBlocks->addBlock($index, $milestone);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function () {
    $('input[id^="milestoneDate"]').removeClass('hasDatepicker').datepicker({onSelect: function(){$(this).blur();}, onClose: function(){$(this).change();} });
});
$(document).on('click', '.addBlock', function () {
    $('input[id^="milestoneDate"]').removeClass('hasDatepicker').datepicker({onSelect: function(){$(this).blur();}, onClose: function(){$(this).change();} });
});
</script>

