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
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if gibbonSchoolYearSpecialDayID and gibbonSchoolYearID specified
    $gibbonSchoolYearSpecialDayID = $_GET['gibbonSchoolYearSpecialDayID'] ?? '';
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Special Days'), 'schoolYearSpecialDay_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Special Day'));

    if (empty($gibbonSchoolYearSpecialDayID) && empty($gibbonSchoolYearID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } 

    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
    
    //Let's go!
    $values = $specialDayGateway->getByID($gibbonSchoolYearSpecialDayID);
    $values['gibbonYearGroupIDList'] = !empty($values['gibbonYearGroupIDList']) ? explode(',', $values['gibbonYearGroupIDList']) : '';
    $values['gibbonFormGroupIDList'] = !empty($values['gibbonFormGroupIDList']) ? explode(',', $values['gibbonFormGroupIDList']) : '';

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('specialDayAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/schoolYearSpecialDay_manage_editProcess.php?gibbonSchoolYearSpecialDayID='.$gibbonSchoolYearSpecialDayID.'&gibbonSchoolYearID='.$gibbonSchoolYearID);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);

    $row = $form->addRow();
        $row->addLabel('dateDisplay', __('Date'))->description(__('Must be unique.'));
        $row->addTextField('dateDisplay')->readonly()->setValue(Format::date($values['date']));

    $types = [
        'School Closure' => __('School Closure'),
        'Timing Change' => __('Timing Change'),
        'Off Timetable' => __('Off Timetable'),
    ];

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required()->placeholder();

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

    $hoursArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 23));
    $hours = implode(',', $hoursArray);

    $minutesArray = array_map(function($num) { return str_pad($num, 2, '0', STR_PAD_LEFT); }, range(0, 59));
    $minutes = implode(',', $minutesArray);

    if (!empty($values['schoolOpen']) && stripos($values['schoolOpen'], ':') !== false) {
        list($values['schoolOpenH'], $values['schoolOpenM'], $sec) = explode(':', $values['schoolOpen']);
    }

    $row = $form->addRow()->addClass('timingChange');
        $row->addLabel('schoolOpen', __('School Opens'));
        $col = $row->addColumn()->addClass('right inline');
        $col->addSelect('schoolOpenH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
        $col->addSelect('schoolOpenM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

    if (!empty($values['schoolStart']) && stripos($values['schoolStart'], ':') !== false) {
        list($values['schoolStartH'], $values['schoolStartM'], $sec) = explode(':', $values['schoolStart']);
    }

    $row = $form->addRow()->addClass('timingChange');
        $row->addLabel('schoolStart', __('School Starts'));
        $col = $row->addColumn()->addClass('right inline');
        $col->addSelect('schoolStartH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
        $col->addSelect('schoolStartM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

    if (!empty($values['schoolEnd']) && stripos($values['schoolEnd'], ':') !== false) {
        list($values['schoolEndH'], $values['schoolEndM'], $sec) = explode(':', $values['schoolEnd']);
    }

    $row = $form->addRow()->addClass('timingChange');
        $row->addLabel('schoolEnd', __('School Ends'));
        $col = $row->addColumn()->addClass('right inline');
        $col->addSelect('schoolEndH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
        $col->addSelect('schoolEndM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

    if (!empty($values['schoolClose']) && stripos($values['schoolClose'], ':') !== false) {
        list($values['schoolCloseH'], $values['schoolCloseM'], $sec) = explode(':', $values['schoolClose']);
    }

    $row = $form->addRow()->addClass('timingChange');
        $row->addLabel('schoolClose', __('School Closes'));
        $col = $row->addColumn()->addClass('right inline');
        $col->addSelect('schoolCloseH')->fromString($hours)->setClass('shortWidth')->placeholder(__('Hours'));
        $col->addSelect('schoolCloseM')->fromString($minutes)->setClass('shortWidth')->placeholder(__('Minutes'));

    $form->toggleVisibilityByClass('cancelActivities')->onSelect('type')->when(['Timing Change', 'Off Timetable']);
    $row = $form->addRow()->addClass('cancelActivities');
        $row->addLabel('cancelActivities', __('Cancel Activities?'))->description(__('Should activities scheduled for this day no longer appear on the timetable?'));
        $row->addYesNo('cancelActivities')->required();
        
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
    
    
}
