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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs
            ->add(__('First Aid Records'), 'firstAidRecord.php')
            ->add(__('Add'));

        $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonFirstAidID='.$_GET['editID'].'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID;
            $editID = $_GET['editID'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, array('warning1' => __('Your request was successful, but some data was not properly saved.'), 'success1' => __('Your request was completed successfully. You can now add extra information below if you wish.')));
        }

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_addProcess.php?gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID);

        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addHeading(__('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Patient'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder()->required();

        $row = $form->addRow();
            $row->addLabel('name', __('First Aider'));
            $row->addTextField('name')->setValue(Format::name('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Student'))->required()->readonly();

        $row = $form->addRow();
            $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            $row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required();

        $row = $form->addRow();
            $row->addLabel('timeIn', __('Time In'))->description("Format: hh:mm (24hr)");
            $row->addTime('timeIn')->setValue(date("H:i"))->required();

        $firstAidDescriptionTemplate = getSettingByScope($connection2, 'Students', 'firstAidDescriptionTemplate');
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('description', __('Description'));
            $column->addTextArea('description')->setRows(8)->setClass('fullWidth')->setValue($firstAidDescriptionTemplate);

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('actionTaken', __('Action Taken'));
            $column->addTextArea('actionTaken')->setRows(8)->setClass('fullWidth');

        $row = $form->addRow()->addHeading(__('Follow Up'));
        
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('followUp', __('Follow Up'));
            $column->addTextArea('followUp')->setRows(8)->setClass('fullWidth');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
