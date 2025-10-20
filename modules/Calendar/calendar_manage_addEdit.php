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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Calendar\CalendarGateway;
use Gibbon\Domain\Calendar\CalendarEditorGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_manage_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonCalendarID = $_GET['gibbonCalendarID'] ?? '';
    $action = !empty($gibbonCalendarID)? 'edit' : 'add';

    $page->breadcrumbs
        ->add(__('Manage Calendars'), 'calendar_manage.php')
        ->add($action == 'edit' ? __('Edit Calendar') : __('Add Calendar'));

    if (empty($gibbonCalendarID) && isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_manage_addEdit.php&gibbonCalendarID='.$_GET['editID']);
    }
    
    $calendarGateway = $container->get(CalendarGateway::class);
    $values = $calendarGateway->getByID($gibbonCalendarID);

    if (!empty($gibbonCalendarID) && empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // FORM
    $form = Form::create('calendar', $session->get('absoluteURL').'/modules/Calendar/calendar_manage_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addMeta()->addDefaultContent($action);
    $form->enableQuickSave($action == 'edit');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonCalendarID', $gibbonCalendarID);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255);
        

    // DISPLAY
    $form->addRow()->addHeading(__('Display'));

    $row = $form->addRow();
        $row->addLabel('color', __('Colour'));
        $row->addColor('color');
    
    $col = $form->addRow()->addColumn();
        $col->addLabel('summary', __('Summary'));
        $col->addEditor('summary', $guid)->showMedia(true);

    // ACCESS
    $form->addRow()->addHeading(__('Access'));

    $row = $form->addRow();
        $row->addLabel('public', __('Public'))->description(__('If yes, members of the public can see events on this calendar without logging in.'));
        $row->addYesNo('public')->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableStaff', __('Viewable to Staff'));
        $row->addYesNo('viewableStaff')->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableStudents', __('Viewable to Students'));
        $row->addYesNo('viewableStudents')->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableParents', __('Viewable to Parents'));
        $row->addYesNo('viewableParents')->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableOther', __('Viewable to Other'));
        $row->addYesNo('viewableOther')->selected('N');


    // EDITORS
    $form->addRow()->addHeading(__('Editors'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addSelectStaff('gibbonPersonID')->photo(false)->setClass('flex-1 mr-1')->required()->placeholder();
        $row->addCheckbox('editAllEvents')->setLabelClass('w-32')->alignLeft()->setValue('Y')->description(__('Edit All Events?'))
            ->append("<input type='hidden' id='gibbonCalendarEditorID' name='gibbonCalendarEditorID' value=''/>");

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('editors', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click'))
        ->placeholder(__('Add a person...'))
        ->addToolInput($addBlockButton);

    $editors = $container->get(CalendarEditorGateway::class)->selectEditorsByCalendar($gibbonCalendarID);
    while ($person = $editors->fetch()) {
        $customBlocks->addBlock($person['gibbonCalendarEditorID'], [
            'gibbonCalendarEditorID' => $person['gibbonCalendarEditorID'],
            'gibbonPersonID'         => $person['gibbonPersonID'],
            'editAllEvents'          => $person['editAllEvents'] ?? 'N',
        ]);
    }

    $row = $form->addRow();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
