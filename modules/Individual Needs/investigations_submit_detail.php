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
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
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
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        // Validate the database records exist
        $investigationGateway = $container->get(INInvestigationGateway::class);
        $criteria = $investigationGateway->newQueryCriteria();
        $investigation = $investigationGateway->queryInvestigationsByID($criteria, $gibbonINInvestigationID, $_SESSION[$guid]['gibbonSchoolYearID']);
        $investigation = $investigation->getRow(0);

        $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
        $criteria2 = $contributionsGateway->newQueryCriteria();
        $contribution = $contributionsGateway->queryContributionsByID($criteria2, $gibbonINInvestigationContributionID);
        $contribution = $contribution->getRow(0);

        if (empty($investigation) || empty($contribution) || $contribution['gibbonPersonID'] != $gibbon->session->get('gibbonPersonID')) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
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
            	$row->addLabel('status', __('Status'));
            	$row->addTextField('status')->setValue(__('Referral'))->required()->readonly();

            //Date
            $row = $form->addRow();
            	$row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            	$row->addDate('date')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP']))->required()->readonly();

    		//Reason
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('reason', __('Reason'))->description(__('Why should this student\'s individual needs should be investigated?'));;
            	$column->addTextArea('reason')->setRows(5)->setClass('fullWidth')->required()->readonly();

            //Strategies Tried
            $row = $form->addRow();
            	$column = $row->addColumn();
            	$column->addLabel('strategiesTried', __('Strategies Tried'));
            	$column->addTextArea('strategiesTried')->setRows(5)->setClass('fullWidth')->readonly();

            //Parents Informed?
            $row = $form->addRow();
                $row->addLabel('parentsInformed', __('Parents Informed?'));
                $row->addYesNo('parentsInformed')->selected('N')->required()->readonly();

            $form->toggleVisibilityByClass('parentsInformed')->onSelect('parentsInformed')->when('Y');

            //Parent Response
            $row = $form->addRow()->addClass('parentsInformed');
            	$column = $row->addColumn();
            	$column->addLabel('parentsResponse', __('Parent Response'));
            	$column->addTextArea('parentsResponse')->setRows(5)->setClass('fullWidth')->readonly();


            $form->addRow()->addHeading(__('Contributor Input'));

            //Type
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addTextField('type')->setValue($contribution['type'])->required()->readonly();

            //Type
            if ($contribution['type'] == 'Teacher') {
                $row = $form->addRow();
                    $row->addLabel('class', __('Class'));
                    $row->addTextField('class')->setValue($contribution['course'].'.'.$contribution['class'])->required()->readonly();
            }

            //Cognition
            $options = array(
                'There is limited understanding of concepts generally' => __('There is limited understanding of concepts generally'),
                'Learning new concepts is a challenge' => __('Learning new concepts is a challenge'),
                'Simple concepts and processes with one or two steps can be understood and applied to classroom learning' => __('Simple concepts and processes with one or two steps can be understood and applied to classroom learning'),
                'New concepts can be understood with ease and used consistently in a structured learning environment' => __('New concepts can be understood with ease and used consistently in a structured learning environment')
            );
            $row = $form->addRow();
                $row->addLabel('cognition', __('Cognition'))->description(__('Please choose one description that is most relevant to the student in your subject.'));
                $row->addRadio('cognition')->fromArray($options)->required()->addClass('py-4');

            //Memory
            $options = array(
                'The ability to hold and use important information in work­ing mem­ory over a short period of time' => __('The ability to hold and use important information in work­ing mem­ory over a short period of time'),
                'The ability to hold several ideas in mind at once' => __('The ability to hold several ideas in mind at once'),
                'The ability to remember what has been learnt in recent lessons' => __('The ability to remember what has been learnt in recent lessons'),
                'The ability to retrieve information from long-term memory' => __('The ability to retrieve information from long-term memory'),
            );
            $row = $form->addRow();
                $row->addLabel('memory', __('Memory'))->description(__('Please tick any areas that you think the student struggles with'));
                $column = $row->addColumn()->setClass('flex-col items-end');
                $count = 0;
                foreach ($options AS $option) {
                    $column->addCheckbox('memory'.$count)
                        ->setName('memory[]')
                        ->setValue($option)
                        ->description(__($option));
                    $count++;
                }

            //Self-Management
            $options = array(
                'Setting goals' => __('Setting goals'),
                'Managing time' => __('Managing time'),
                'Being organised' => __('Being organised'),
                'Monitoring, controlling and self directing aspects of learning for themselves' => __('Monitoring, controlling and self directing aspects of learning for themselves'),
                'Emotional self-regulation' => __('Emotional self-regulation'),
            );
            $row = $form->addRow();
                $row->addLabel('selfManagement', __('Self-Management'))->description(__('Please tick any areas that you think the student struggles with.'));
                $column = $row->addColumn()->setClass('flex-col items-end');
                $count = 0;
                foreach ($options AS $option) {
                    $column->addCheckbox('selfManagement'.$count)
                        ->setName('selfManagement[]')
                        ->setValue($option)
                        ->description(__($option));
                    $count++;
                }

            //Attention
            $options = array(
                'Sus­taining con­cen­tra­tion and attention in lessons' => __('Sus­taining con­cen­tra­tion and attention in lessons'),
                'Paying attention to relevant information' => __('Paying attention to relevant information'),
                'Shifting attention when needed' => __('Shifting attention when needed'),
                'Monitoring, controlling and self directing aspects of learning for themselves' => __('Monitoring, controlling and self directing aspects of learning for themselves'),
                'Resisting distraction and internal urges to do other things than the task at hand' => __('Resisting distraction and internal urges to do other things than the task at hand'),
            );
            $row = $form->addRow();
                $row->addLabel('attention', __('Attention'))->description(__('Please tick any areas that you think the student struggles with.'));
                $column = $row->addColumn()->setClass('flex-col items-end');
                $count = 0;
                foreach ($options AS $option) {
                    $column->addCheckbox('attention'.$count)
                        ->setName('attention[]')
                        ->setValue($option)
                        ->description(__($option));
                    $count++;
                }
            //Social Interaction
            $options = array(
                'Approaching others and making friends' => __('Approaching others and making friends'),
                'Ability to hold a conversation' => __('Ability to hold a conversation'),
                'Ability to use appropriate non-verbal communication (eye contact, facial expressions, gestures, body language)' => __('Ability to use appropriate non-verbal communication (eye contact, facial expressions, gestures, body language)'),
                'Adjusting behaviour to suit contexts' => __('Adjusting behaviour to suit contexts'),
            );
            $row = $form->addRow();
                $row->addLabel('socialInteraction', __('Social Interaction'))->description(__('Please tick any areas that you think the student struggles with.'));
                $column = $row->addColumn()->setClass('flex-col items-end');
                $count = 0;
                foreach ($options AS $option) {
                    $column->addCheckbox('socialInteraction'.$count)
                        ->setName('socialInteraction[]')
                        ->setValue($option)
                        ->description(__($option));
                    $count++;
                }

            //Communication
            $options = array(
                'Word reading accuracy (ability to sound out words)' => __('Word reading accuracy (ability to sound out words)'),
                'Reading rate or fluency (speed)' => __('Reading rate or fluency (speed)'),
                'Reading comprehension (understanding of texts)' => __('Reading comprehension (understanding of texts)'),
                'Spelling accuracy' => __('Spelling accuracy'),
                'Grammar and punctuation accuracy' => __('Grammar and punctuation accuracy'),
                'Clarity or organization of written expression' => __('Clarity or organization of written expression'),
                'Verbal articulation of meaning with an awareness of audience' => __('Verbal articulation of meaning with an awareness of audience')
            );
            $row = $form->addRow();
                $row->addLabel('communication', __('Communication'))->description(__('Please tick any areas that you think the student struggles with.'));
                $column = $row->addColumn()->setClass('flex-col items-end');
                $count = 0;
                foreach ($options AS $option) {
                    $column->addCheckbox('communication'.$count)
                        ->setName('communication[]')
                        ->setValue($option)
                        ->description(__($option));
                    $count++;
                }

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
?>
