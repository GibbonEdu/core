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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs
            ->add(__('Manage Investigations'), 'investigations_manage.php')
            ->add(__('Add'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $editLink = '';
        $editID = '';
        if (isset($_GET['editID'])) {
            $editID = $_GET['editID'];
            $editLink = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$editID&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID";
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink);
        }

        if ($gibbonPersonID != '' or $gibbonRollGroupID != '' or $gibbonYearGroupID != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_manage.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID'>".__('Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('addform', $_SESSION[$guid]['absoluteURL']."/modules/Individual Needs/investigations_manage_addProcess.php?gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID");
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addHiddenValue('address', "/modules/Individual Needs/investigations_manage_add.php");
        $form->addRow()->addHeading(__('Basic Information'));

        //Student
        $row = $form->addRow();
        	$row->addLabel('gibbonPersonIDStudent', __('Student'));
        	$row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder(__('Please select...'))->selected($gibbonPersonID)->required();

        //Status
        $row = $form->addRow();
        	$row->addLabel('status', __('Status'));
        	$row->addTextField('status')->setValue(__('Referral'))->required()->readonly();

        //Date
        $row = $form->addRow();
        	$row->addLabel('date', __('Date'));
        	$row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required();

		//Reason
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('reason', __('Reason'))->description(__('Why should this student\'s individual needs be investigated?'));
        	$column->addTextArea('reason')->setRows(5)->setClass('fullWidth')->required();

        //Strategies Tried
        $row = $form->addRow();
        	$column = $row->addColumn();
        	$column->addLabel('strategiesTried', __('Strategies Tried'));
        	$column->addTextArea('strategiesTried')->setRows(5)->setClass('fullWidth');

        //Parents Informed?
        $row = $form->addRow();
            $row->addLabel('parentsInformed', __('Parents Informed?'))->description(_('For example, via a phone call, email, Markbook, meeting or other means.'));
            $row->addYesNo('parentsInformed')->required()->placeholder();

        $form->toggleVisibilityByClass('parentsInformedYes')->onSelect('parentsInformed')->when('Y');
        $form->toggleVisibilityByClass('parentsInformedNo')->onSelect('parentsInformed')->when('N');

        //Parent Response
        $row = $form->addRow()->addClass('parentsInformedYes');
        	$column = $row->addColumn();
        	$column->addLabel('parentsResponseYes', __('Parent Response'));
        	$column->addTextArea('parentsResponseYes')->setName('parentsResponse')->setRows(5)->setClass('fullWidth');

        $row = $form->addRow()->addClass('parentsInformedNo');
        	$column = $row->addColumn();
        	$column->addLabel('parentsResponseNo', __('Reason'))->description(__('Reasons why parents are not aware of the situation.'));
        	$column->addTextArea('parentsResponseNo')->setName('parentsResponse')->setRows(5)->setClass('fullWidth')->required();

        $form->addRow()->addAlert(__("Submitting this referral will notify the student's form tutor for further investigation."), 'message');

        $row = $form->addRow();
        	$row->addFooter();
        	$row->addSubmit();

        echo $form->getOutput();
    }
}
