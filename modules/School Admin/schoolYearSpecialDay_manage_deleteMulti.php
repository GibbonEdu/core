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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

$page->breadcrumbs
    ->add(__('Manage Special Days'), 'schoolYearSpecialDay_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
    ->add(__('Batch Delete'));

if (empty($gibbonSchoolYearID)) {
    $page->addError(__('You have not specified one or more required parameters.'));
    return;
}

$schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID);
if (empty($schoolYear)) {
    $page->addError(__('The specified record does not exist.'));
    return;
}

$page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

$specialDays = $container->get(SchoolYearSpecialDayGateway::class)->selectSpecialDaysBySchoolYear($gibbonSchoolYearID);
if ($specialDays->rowCount() < 1) {
    echo Format::alert(__('There are no records to display.'), 'empty');
} else {
    $table = DataTable::create('specialDays');
    $table->setTitle(__('Current Special Days'));
    $table->setDescription(__('These are the special days currently recorded for this school year.'));

    $table->addColumn('date', __('Date'))->format(function ($row) {
        return Format::date($row['date']);
    });
    $table->addColumn('type', __('Type'))->format(function ($row) {
        return __($row['type']);
    });
    $table->addColumn('name', __('Name'));
    $table->addColumn('description', __('Description'));
    $table->addColumn('termName', __('Term'));

    $actions = $table->addActionColumn()
        ->addParam('gibbonSchoolYearSpecialDayID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/School Admin/schoolYearSpecialDay_manage_edit.php')
                ->addParam('gibbonSchoolYearTermID', $row['gibbonSchoolYearTermID']);
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/School Admin/schoolYearSpecialDay_manage_delete.php')
                ->modalWindow(650, 135);
        });

    echo $table->render($specialDays->toDataSet());
}

$form = Form::create('specialDayDeleteMulti', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/schoolYearSpecialDay_manage_deleteMultiProcess.php');
$form->setFactory(DatabaseFormFactory::create($pdo));
$form->setTitle(__('Batch Delete Special Days'));
$form->setDescription(__('Delete special days in the selected date range. This cannot be undone, so please confirm the range and type before submitting.'));

$form->addHiddenValue('address', $session->get('address'));
$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

$row = $form->addRow();
    $row->addLabel('dateStart', __('Start Date'));
    $row->addDate('dateStart')->setValue(Format::date($schoolYear['firstDay']))->required();

$row = $form->addRow();
    $row->addLabel('dateEnd', __('End Date'));
    $row->addDate('dateEnd')->setValue(Format::date($schoolYear['lastDay']))->required();

$types = [
    ''               => __('All Types'),
    'School Closure' => __('School Closure'),
    'Timing Change'  => __('Timing Change'),
    'Off Timetable'  => __('Off Timetable'),
];

$row = $form->addRow();
    $row->addLabel('type', __('Type'))->description(__('Only delete this type. Selecting All Types deletes every special day in the range.'));
    $row->addSelect('type')->fromArray($types)->required();

$row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

echo $form->getOutput();
