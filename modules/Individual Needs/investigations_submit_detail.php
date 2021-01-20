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
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_submit_detail.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Submit Contributions'), 'investigations_submit.php')
        ->add(__('Edit'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonINInvestigationID = $_GET['gibbonINInvestigationID'] ?? '';
    $gibbonINInvestigationContributionID = $_GET['gibbonINInvestigationContributionID'] ?? '';
    if ($gibbonINInvestigationContributionID == '' || $gibbonINInvestigationID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        // Validate the database records exist
        $investigationGateway = $container->get(INInvestigationGateway::class);
        $investigation = $investigationGateway->getInvestigationByID($gibbonINInvestigationID);

        $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
        $contribution = $contributionsGateway->getContributionByID($gibbonINInvestigationContributionID);

        if (empty($investigation) || empty($contribution) || $contribution['gibbonPersonID'] != $gibbon->session->get('gibbonPersonID')) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $form = Form::create('addform', $_SESSION[$guid]['absoluteURL']."/modules/Individual Needs/investigations_submit_detailProcess.php");
            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->addHiddenValue('address', "/modules/Individual Needs/investigations_manage_edit.php");
            $form->addHiddenValue('gibbonINInvestigationID', $gibbonINInvestigationID);
            $form->addHiddenValue('gibbonINInvestigationContributionID', $gibbonINInvestigationContributionID);
            $form->addRow()->addHeading(__('Basic Information'));

            //Student
            $row = $form->addRow();
            	$row->addLabel('gibbonPersonIDStudent', __('Student'));
            	$row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->placeholder(__('Please select...'))->selected($investigation['gibbonPersonIDStudent'])->required()->readonly();

            //Status
            $row = $form->addRow();
            	$row->addLabel('statusText', __('Status'));
            	$row->addTextField('statusText')->setValue(__($investigation['status']))->required()->readonly();

            //Date
            $row = $form->addRow();
            	$row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            	$row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required()->readonly();

    		//Reason
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('reason', __('Reason'))->description(__('Why should this student\'s individual needs be investigated?'));;
            	$column->addTextArea('reason')->setRows(5)->setClass('fullWidth')->required()->readonly();

            //Strategies Tried
            $row = $form->addRow();
            	$column = $row->addColumn();
            	$column->addLabel('strategiesTried', __('Strategies Tried'));
            	$column->addTextArea('strategiesTried')->setRows(5)->setClass('fullWidth')->readonly();

            //Parents Informed?
            $row = $form->addRow();
                $row->addLabel('parentsInformed', __('Parents Informed?'))->description(_('For example, via a phone call, email, Markbook, meeting or other means.'));
                $row->addYesNo('parentsInformed')->selected('N')->required()->readonly();

            $form->toggleVisibilityByClass('parentsInformedYes')->onSelect('parentsInformed')->when('Y');
            $form->toggleVisibilityByClass('parentsInformedNo')->onSelect('parentsInformed')->when('N');

            //Parent Response
            $row = $form->addRow()->addClass('parentsInformedYes');
            	$column = $row->addColumn();
            	$column->addLabel('parentsResponseYes', __('Parent Response'));
            	$column->addTextArea('parentsResponseYes')->setName('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly();

            $row = $form->addRow()->addClass('parentsInformedNo');
                $column = $row->addColumn();
                $column->addLabel('parentsResponseNo', __('Reason'))->description(__('Reasons why parents are not aware of the situation.'));
                $column->addTextArea('parentsResponseNo')->setName('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly()->required();

            $form->addRow()->addHeading(__('Contributor Input'));

            //Type
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addTextField('type')->setValue(__($contribution['type']))->required()->readonly();

            //Type
            if ($contribution['type'] == 'Teacher') {
                $row = $form->addRow();
                    $row->addLabel('class', __('Class'));
                    $row->addTextField('class')->setValue($contribution['course'].'.'.$contribution['class'])->required()->readonly();
            }

            //Cognition
            $options = getInvestigationCriteriaArray('Cognition') ;
            $row = $form->addRow();
                $row->addLabel('cognition', __('Cognition'))->description(__('Please choose one description that is most relevant to the student in your subject.'));
                $row->addRadio('cognition')
                    ->fromArray($options)
                    ->required()
                    ->checked(false)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Memory
            $options = getInvestigationCriteriaArray('Memory') ;
            $row = $form->addRow();
                $row->addLabel('memory', __('Memory'))->description(__('Please tick any areas that you think the student struggles with'));
                $row->addCheckbox('memory')
                    ->fromArray($options)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Self-Management
            $options = getInvestigationCriteriaArray('Self-Management') ;
            $row = $form->addRow();
                $row->addLabel('selfManagement', __('Self-Management'))->description(__('Please tick any areas that you think the student struggles with.'));
                $row->addCheckbox('selfManagement')
                    ->fromArray($options)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Attention
            $options = getInvestigationCriteriaArray('Attention') ;
            $row = $form->addRow();
                $row->addLabel('attention', __('Attention'))->description(__('Please tick any areas that you think the student struggles with.'));
                $row->addCheckbox('attention')
                    ->fromArray($options)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Social Interaction
            $options = getInvestigationCriteriaArray('Social Interaction') ;
            $row = $form->addRow();
                $row->addLabel('socialInteraction', __('Social Interaction'))->description(__('Please tick any areas that you think the student struggles with.'));
                $row->addCheckbox('socialInteraction')
                    ->fromArray($options)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Communication
            $options = getInvestigationCriteriaArray('Communication') ;
            $row = $form->addRow();
                $row->addLabel('communication', __('Communication'))->description(__('Please tick any areas that you think the student struggles with.'));
                $row->addCheckbox('communication')
                    ->fromArray($options)
                    ->addClass('md:max-w-md')
                    ->alignLeft();

            //Comment
            $row = $form->addRow();
            	$column = $row->addColumn();
            	$column->addLabel('comment', __('Comment'));
            	$column->addTextArea('comment')->setRows(5)->setClass('fullWidth');

            $row = $form->addRow();
            	$row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($investigation);

            echo $form->getOutput();
        }
    }
}
