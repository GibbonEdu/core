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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

$page->breadcrumbs
    ->add(__('Manage Special Days'), 'schoolYearSpecialDay_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
    ->add(__('Batch Add Special Days'));

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

$form = Form::create('specialDayAddMulti', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/schoolYearSpecialDay_manage_addMultiProcess.php');
$form->setFactory(DatabaseFormFactory::create($pdo));
$form->setTitle(__('Batch Add Special Days'));
$form->setDescription(__('All school days in the selected date range will be added as special days of the chosen type. Weekends, holidays, dates outside terms, and dates with an existing special day are skipped.'));

$form->addHiddenValue('address', $session->get('address'));
$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

$row = $form->addRow();
    $row->addLabel('dateStart', __('Start Date'));
    $row->addDate('dateStart')->setValue(Format::date($schoolYear['firstDay']))->required();

$row = $form->addRow();
    $row->addLabel('dateEnd', __('End Date'));
    $row->addDate('dateEnd')->setValue(Format::date($schoolYear['lastDay']))->required();

$types = [
    'School Closure' => __('School Closure'),
    'Timing Change'  => __('Timing Change'),
    'Off Timetable'  => __('Off Timetable'),
];

$row = $form->addRow();
    $row->addLabel('type', __('Type'));
    $row->addSelect('type')->fromArray($types)->selected('School Closure')->required();

$row = $form->addRow();
    $row->addLabel('name', __('Name'));
    $row->addTextField('name')->required()->maxLength(20);

$row = $form->addRow();
    $row->addLabel('description', __('Description'));
    $row->addTextField('description')->maxLength(255);

$form->toggleVisibilityByClass('offTimetable')->onSelect('type')->when('Off Timetable');

$row = $form->addRow()->addClass('offTimetable');
    $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
    $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple();

$row = $form->addRow()->addClass('offTimetable');
    $row->addLabel('gibbonFormGroupIDList', __('Form Groups'));
    $row->addSelectFormGroup('gibbonFormGroupIDList', $session->get('gibbonSchoolYearID'))->selectMultiple();

$form->toggleVisibilityByClass('timingChange')->onSelect('type')->when('Timing Change');

$hoursArray = array_map(function ($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 23));
$hours = implode(',', $hoursArray);

$minutesArray = array_map(function ($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 59));
$minutes = implode(',', $minutesArray);

$row = $form->addRow()->addClass('timingChange');
    $row->addLabel('schoolOpen', __('School Opens'));
    $col = $row->addColumn()->addClass('right inline');
    $col->addSelect('schoolOpenH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
    $col->addSelect('schoolOpenM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

$row = $form->addRow()->addClass('timingChange');
    $row->addLabel('schoolStart', __('School Starts'));
    $col = $row->addColumn()->addClass('right inline');
    $col->addSelect('schoolStartH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
    $col->addSelect('schoolStartM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

$row = $form->addRow()->addClass('timingChange');
    $row->addLabel('schoolEnd', __('School Ends'));
    $col = $row->addColumn()->addClass('right inline');
    $col->addSelect('schoolEndH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
    $col->addSelect('schoolEndM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

$row = $form->addRow()->addClass('timingChange');
    $row->addLabel('schoolClose', __('School Closes'));
    $col = $row->addColumn()->addClass('right inline');
    $col->addSelect('schoolCloseH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
    $col->addSelect('schoolCloseM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

$form->toggleVisibilityByClass('cancelTT')->onSelect('type')->when(['Timing Change', 'Off Timetable']);

$row = $form->addRow()->addClass('cancelTT');
    $row->addLabel('cancelClasses', __('Cancel {event}?', ['event' => __('Classes')]))->description(__('Should all {event} scheduled for this day no longer appear on the timetable?', ['event' => __('Classes')]));
    $row->addYesNo('cancelClasses')->required()->selected('N');

$row = $form->addRow()->addClass('cancelTT');
    $row->addLabel('cancelActivities', __('Cancel Activities?'))->description(__('Should activities scheduled for this day no longer appear on the timetable?'));
    $row->addYesNo('cancelActivities')->required()->selected('N');

$row = $form->addRow()->addClass('cancelTT');
    $row->addLabel('cancelDuty', __('Cancel {event}?', ['event' => __('Staff Duty')]))->description(__('Should all {event} scheduled for this day no longer appear on the timetable?', ['event' => __('Staff Duty')]));
    $row->addYesNo('cancelDuty')->required()->selected('N');

$row = $form->addRow()->addClass('cancelTT');
    $row->addLabel('cancelBookings', __('Cancel {event}?', ['event' => __('Bookings')]))->description(__('Should all {event} scheduled for this day no longer appear on the timetable?', ['event' => __('Bookings')]));
    $row->addYesNo('cancelBookings')->required()->selected('N');

$row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

echo $form->getOutput();
