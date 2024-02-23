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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs
            ->add(__('First Aid Records'), 'firstAidRecord.php')
            ->add(__('Add'));

        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonFirstAidID='.$_GET['editID'].'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID;
            $editID = $_GET['editID'] ?? '';
        }
        $page->return->setEditLink($editLink);
        $page->return->addReturns(['warning1' => __('Your request was successful, but some data was not properly saved.'), 'success1' => __('Your request was completed successfully. You can now add extra information below if you wish.')]);

        $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/firstAidRecord_addProcess.php?gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID);

        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow()->addHeading('Basic Information', __('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Patient'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->required();

        $row = $form->addRow();
            $row->addLabel('name', __('First Aider'));
            $row->addTextField('name')->setValue(Format::name('', $session->get('preferredName'), $session->get('surname'), 'Student'))->required()->readonly();

        $row = $form->addRow();
            $row->addLabel('date', __('Date'));
            $row->addDate('date')->setValue(date($session->get('i18n')['dateFormatPHP']))->required();

        $row = $form->addRow();
            $row->addLabel('timeIn', __('Time In'))->description("Format: hh:mm (24hr)");
            $row->addTime('timeIn')->setValue(date("H:i"))->required();

        $firstAidDescriptionTemplate = $container->get(SettingGateway::class)->getSettingByScope('Students', 'firstAidDescriptionTemplate');
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('description', __('Description'));
            $column->addTextArea('description')->setRows(8)->setClass('fullWidth')->setValue($firstAidDescriptionTemplate);

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('actionTaken', __('Action Taken'));
            $column->addTextArea('actionTaken')->setRows(8)->setClass('fullWidth');

        $row = $form->addRow()->addHeading('Follow Up', __('Follow Up'));
        
        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDFollowUp', __('Follow up Request'))->description(__('If selected, this user will be notified to enter follow up details about the first aid incident.'));
            $row->addSelectStaff('gibbonPersonIDFollowUp')->photo(true, 'small')->placeholder();

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('followUp', __('Follow Up'))->description(__('If you are the student\'s teacher, please include details such as: the location & lesson, what lead up to the incident, what was the incident, what did you do.'));
            $column->addTextArea('followUp')->setRows(8)->setClass('fullWidth');

        // CUSTOM FIELDS
        $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'First Aid', []);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
