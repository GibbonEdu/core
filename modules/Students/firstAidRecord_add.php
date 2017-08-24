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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/firstAidRecord.php'>".__($guid, 'Manage First Aid Records')."</a> > </div><div class='trailEnd'>".__($guid, 'Add').'</div>';
        echo '</div>';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/firstAidRecord_edit.php&gibbonFirstAidID='.$_GET['editID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];
            $editID = $_GET['editID'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'success1' => 'Your request was completed successfully. You can now add extra information below if you wish.'));
        }

        $gibbonFirstAidID = null;
        if (isset($_GET['gibbonFirstAidID'])) {
            $gibbonFirstAidID = $_GET['gibbonFirstAidID'];
        }

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/firstAidRecord_addProcess.php?gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID']);

        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Patient'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder()->isRequired();

        $row = $form->addRow();
            $row->addLabel('name', __('First Aider'));
            $row->addTextField('name')->setValue(formatName('', $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Student'))->isRequired()->readonly();

        $row = $form->addRow();
            $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            $row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->isRequired();

        $row = $form->addRow();
            $row->addLabel('timeIn', __('Time In'))->description("Format: hh:mm (24hr)");
            $row->addTime('timeIn')->setValue(date("H:i"))->isRequired();

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('description', __('Description'));
            $column->addTextArea('description')->setRows(8)->setClass('fullWidth');

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('actionTaken', __('Action Taken'));
            $column->addTextArea('actionTaken')->setRows(8)->setClass('fullWidth');

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
?>
