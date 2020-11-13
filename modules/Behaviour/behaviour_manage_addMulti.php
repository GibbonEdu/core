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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $page->breadcrumbs
        ->add(__('Manage Behaviour Records'), 'behaviour_manage.php')
        ->add(__('Add Multiple'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo "<div class='linkTop'>";
    $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
    if ($policyLink != '') {
        echo "<a target='_blank' href='$policyLink'>".__('View Behaviour Policy').'</a>';
    }
    if ($_GET['gibbonPersonID'] != '' or $_GET['gibbonRollGroupID'] != '' or $_GET['gibbonYearGroupID'] != '' or $_GET['type'] != '') {
        if ($policyLink != '') {
            echo ' | ';
        }
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']."'>".__('Back to Search Results').'</a>';
    }
    echo '</div>';


    $form = Form::create('addform', $_SESSION[$guid]['absoluteURL'].'/modules/Behaviour/behaviour_manage_addMultiProcess.php?gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']);
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', "/modules/Behaviour/behaviour_manage_addMulti.php");
    $form->addRow()->addHeading(__('Step 1'));

    //Student
    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDMulti', __('Students'));
        $row->addSelectStudent('gibbonPersonIDMulti', $_SESSION[$guid]['gibbonSchoolYearID'], array('byName' => true, 'byRoll' => true))->selectMultiple()->required();

    //Date
    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required();

    //Type
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray(array('Positive' => __('Positive'), 'Negative' => __('Negative')))->required();

    //Descriptor
    if ($enableDescriptors == 'Y') {
        $negativeDescriptors = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors');
        $negativeDescriptors = (!empty($negativeDescriptors))? explode(',', $negativeDescriptors) : array();
        $positiveDescriptors = getSettingByScope($connection2, 'Behaviour', 'positiveDescriptors');
        $positiveDescriptors = (!empty($positiveDescriptors))? explode(',', $positiveDescriptors) : array();

        $chainedToNegative = array_combine($negativeDescriptors, array_fill(0, count($negativeDescriptors), 'Negative'));
        $chainedToPositive = array_combine($positiveDescriptors, array_fill(0, count($positiveDescriptors), 'Positive'));
        $chainedTo = array_merge($chainedToNegative, $chainedToPositive);

        $row = $form->addRow();
            $row->addLabel('descriptor', __('Descriptor'));
            $row->addSelect('descriptor')
                ->fromArray($positiveDescriptors)
                ->fromArray($negativeDescriptors)
                ->chainedTo('type', $chainedTo)
                ->required()
                ->placeholder();
    }

    //Level
    if ($enableLevels == 'Y') {
        $optionsLevels = getSettingByScope($connection2, 'Behaviour', 'levels');
        if ($optionsLevels != '') {
            $optionsLevels = explode(',', $optionsLevels);
        }
        $row = $form->addRow();
            $row->addLabel('level', __('Level'));
            $row->addSelect('level')->fromArray($optionsLevels)->placeholder();
    }

    //Incident
    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('comment', __('Incident'));
        $column->addTextArea('comment')->setRows(5)->setClass('fullWidth');

    //Follow Up
    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('followup', __('Follow Up'));
        $column->addTextArea('followup')->setRows(5)->setClass('fullWidth');

    //Copy to Notes
    $row = $form->addRow();
        $row->addLabel('copyToNotes', __('Copy To Notes'));
        $row->addCheckbox('copyToNotes');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
