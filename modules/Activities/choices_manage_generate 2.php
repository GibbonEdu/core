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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Module\Activities\EnrolmentGenerator;
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/choices_manage_generate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $params = [
        'step' => $_REQUEST['step'] ?? 1,
        'sidebar' => 'false',
        'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
    ];

    $page->breadcrumbs
        ->add(__('Manage Choices'), 'choices_manage.php')
        ->add(__('Generate Enrolment'));
     
    $page->return->addReturns([
        'error4' => __(''),
    ]);

    $step = $_REQUEST['step'] ?? 1;

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $activityChoiceGateway = $container->get(ActivityChoiceGateway::class);
    
    $category = $categoryGateway->getByID($params['gibbonActivityCategoryID']);
    $signUpChoices = $category['signUpChoices'] ?? 3;

    $choiceList = [1 => __('First Choice'), 2 => __('Second Choice'), 3 => __('Third Choice'), 4 => __('Fourth Choice'), 5 => __('Fifth Choice')];

    $pageUrl = Url::fromModuleRoute('Activities', 'choices_manage_generate.php')->withQueryParams($params);
    
    // FORM
    $form = MultiPartForm::create('generate', (string)$pageUrl);
   
    $form->setCurrentPage($params['step']);
    $form->addPage(1, __('Select an Category'), $pageUrl);
    $form->addPage(2, __('Confirm Activities'), $pageUrl);
    $form->addPage(3, __('Create Groups'), $pageUrl);
    $form->addPage(4, __('View Results'), $pageUrl);

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('step', $params['step'] + 1);

    if ($form->getCurrentPage() == 1) {
        // STEP 1
        $categories = $categoryGateway->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();

        $row = $form->addRow();
        $row->addLabel('gibbonActivityCategoryID', __('Category'));
        $row->addSelect('gibbonActivityCategoryID')
            ->fromArray($categories)
            ->required()
            ->placeholder()
            ->selected($params['gibbonActivityCategoryID']);

        $form->addRow()->addSubmit(_('Next'));

    } elseif ($form->getCurrentPage() == 2) {
        // STEP 2
        if (empty($params['gibbonActivityCategoryID'])) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $options = [
            'newStudentPriority' => __('Prioritise new students getting their first choice'),
            'yearGroupPriority'  => __('Prioritise younger year groups getting their first choice'),
            'includePastChoices' => __('Include past choices when balancing groups'),
            'includeTimestamps'  => __('Include sign-up time when balancing groups (semi-competitive)'),
        ];

        $row = $form->addRow();
        $row->addLabel('options', __('Options'));
        $row->addCheckbox('options')->fromArray($options)->checked(['newStudentPriority', 'yearGroupPriority', 'includePastChoices']);

        $activities = $activityGateway->selectActivityDetailsByCategory($params['gibbonActivityCategoryID'])->fetchGroupedUnique();
        $choiceCounts = $activityChoiceGateway->selectChoiceCountsByCategory($params['gibbonActivityCategoryID'])->fetchGroupedUnique();

        $table = $form->addRow()->addTable()->setClass('mini w-full');
        $table->addClass('bulkActionForm colorOddEven');

        $header = $table->addHeaderRow();
            $header->addTableCell(__('Include'));
            $header->addTableCell(__('Activity'));
            $header->addTableCell(__('Maximum Enrolment'));
            for ($i = 1; $i <= $signUpChoices; $i++) {
                $header->addTableCell($choiceList[$i]);
            }
        
        $totalMax = $totalChoice = 0;

        foreach ($activities as $activity) {
            $index = $activity['gibbonActivityID'];

            $totalMax += $activity['maxParticipants'] ?? 0;
            $totalChoice += $choiceCounts[$index]['choice1'] ?? 0;

            $row = $table->addRow();
            $row->addCheckbox("activity[{$index}][generate]")
                ->setClass('w-12 bulkCheckbox')
                ->alignCenter()
                ->setValue('Y')
                ->checked('Y');
            $row->addLabel("activity{$index}", $activity['name']);
            $row->addNumber("activity[{$index}][maxParticipants]")
                ->setClass('w-24')
                ->onlyInteger(true)
                ->minimum(0)
                ->maximum(999)
                ->maxLength(3)
                ->required()
                ->setValue($activity['maxParticipants']);

                for ($i = 1; $i <= $signUpChoices; $i++) {
                $row->addTextField("activity[{$index}][choice{$i}]")
                    ->setClass('w-24 text-black opacity-100')
                    ->readOnly()
                    ->disabled()
                    ->setValue($choiceCounts[$index]["choice{$i}"] ?? '');
            }
        }

        $row = $table->addRow();
            $row->addTableCell();
            $row->addTableCell(__('{count} Activity(s)', ['count' => count($activities)]));   
            $row->addTableCell(__('Max').': '.$totalMax)->setClass('text-center');  
            $row->addTableCell(__('{count} Sign-up(s)', ['count' => $totalChoice]))->setClass('text-center');  

        if ($totalChoice > $totalMax) {
            $form->addRow()->addContent(Format::alert(__('The maximum available spaces is {max}, which is less than the total sign ups. {difference} participants will not be automatically added to groups, but can still be added manually.', ['max' => $totalMax, 'difference' => $totalChoice - $totalMax]), 'warning'));
        }
        $form->addRow()->addSubmit(_('Next'));

    } elseif ($form->getCurrentPage() == 3) {
        // STEP 3
        $form->setClass('blank w-full');
        $form->setAction((string)$pageUrl->withQueryParam('sidebar', 'true'));

        // Collect only the activities that were submitted for generation
        $activityList = array_filter($_POST['activity'] ?? [], function($item) {
            return !empty($item['generate']) && $item['generate'] == 'Y';
        });

        // Use the generator class to handle turning choices into groups for each activity
        $generator = $container->get(EnrolmentGenerator::class);

        $generator
            ->setOptions($_POST['options'] ?? [])
            ->loadActivities($params['gibbonActivityCategoryID'], $activityList)
            ->loadEnrolments($params['gibbonActivityCategoryID'])
            ->loadChoices($params['gibbonActivityCategoryID'])
            ->generateGroups();
    
        // Display the drag-drop group editor
        $form->addRow()->addContent($page->fetchFromTemplate('generate.twig.html', [
            'signUpChoices' => $signUpChoices,
            'activities' => $generator->getActivities(),
            'groups'      => $generator->getGroups(),
            'mode' => 'student',
        ]));

        $table = $form->addRow()->addTable()->setClass('smallIntBorder w-full');
        $row = $table->addRow()->addSubmit(__('Submit'));
    } elseif ($form->getCurrentPage() >= 3) {
        // STEP 4
        $enrolmentList = $_POST['person'] ?? [];
        
        $generator = $container->get(EnrolmentGenerator::class);
        $results = $generator->createEnrolments($params['gibbonActivityCategoryID'], $enrolmentList, $session->get('gibbonPersonID'));

        $form->addRow()->addContent(Format::alert(__('Enrolment generation has completed successfully, creating {total} enrolments with {unassigned} left unassigned.', $results), 'success'));

        if (!empty($results['error'])) {
            $form->addRow()->addContent(Format::alert(__('There was an error creating {error} enrolments, which likely already have duplicate enrolments for this Activities categories.', $results), 'error'));
        }

        $table = $form->addRow()->addTable()->setClass('smallIntBorder w-full max-w-lg mx-auto');

        $row = $table->addRow();
            $row->addLabel('total', __('Total Enrolments'));
            $row->addTextField('totalValue')->setClass('w-24')->readonly()->setValue($results['total'] ?? 0);
            $row->addContent();

        for ($i = 1; $i <= $signUpChoices; $i++) {
            $row = $table->addRow();
            $row->addLabel("choice{$i}", $choiceList[$i]);
            $row->addTextField("choice{$i}Value")->setClass('w-24')->readonly()->setValue( ($results["choice{$i}"] ?? 0) );
            $row->addContent( !empty($results['total']) ? round( ($results["choice{$i}"]/$results['total'])*100).'%' : '');
        }

        $row = $table->addRow();
            $row->addLabel('unassigned', __('Unassigned'));
            $row->addTextField('unassignedValue')->setClass('w-24')->readonly()->setValue( ($results['unassigned'] ?? 0) );
            $row->addContent( !empty($results['total']) ? round( ($results['unassigned']/$results['total'])*100).'%' : '');
    }

    echo $form->getOutput();
}
