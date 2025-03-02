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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__('Manage Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $session->get('absoluteURL').'/modules/Free Learning/settings_manageProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading(__m('General Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'bigDataSchool', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'publicUnits', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'learningAreaRestriction', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'difficultyOptions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addTextField($setting['name'])->required()->setValue($setting['value'])->maxLength(50);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'unitOutlineTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'smartBlocksTemplate', true);
    $sql = 'SELECT freeLearningUnitID as value, name FROM freeLearningUnit ORDER BY name';
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql, array())->selected($setting['value'])->placeholder();;

    $sql = "SELECT gibbonCustomFieldID as value, name FROM gibbonCustomField WHERE context='User' AND active='Y'";
    $setting = $settingGateway->getSettingByScope('Free Learning', 'customField', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql)->selected($setting['value'])->placeholder();

    $setting = $settingGateway->getSettingByScope('Free Learning', 'maxMapSize', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addNumber($setting['name'])->required()->setValue($setting['value'])->maxLength(3);

    $form->addRow()->addHeading(__m('Enrolment Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableClassEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableSchoolMentorEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableExternalMentorEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'autoAcceptMentorGroups', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'showContentOnEnrol', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $form->addRow()->addHeading(__m('Submissions Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'availableSubmissionTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray(array('Link' => __('Link'), 'File' => __('File'), 'Link/File' => __('Link/File')))->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'certificatesAvailable', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $form->toggleVisibilityByClass('certificate')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Free Learning', 'certificateOrientation', true);
    $orientations = ['P' => __('Portrait'), 'L' => __('Landscape')];
    $row = $form->addRow()->addClass('certificate');
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray($orientations)->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'certificateTemplate', true);
    $col = $form->addRow()->addClass('certificate')->addColumn();
        $col->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $col->addCodeEditor($setting['name'])->setMode('twig')->setValue($setting['value']);

    $form->addRow()->addHeading(__('Display Settings'));

    $viewOptions = [
        'map' => __('Map'),
        'grid' => __('Grid'),
        'list' => __('List'),
    ];
    $setting = $settingGateway->getSettingByScope('Free Learning', 'defaultBrowseView', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray($viewOptions)->required()->selected($setting['value']);

    $courses = $container->get(UnitGateway::class)->selectAllCourses()->fetchKeyPair();
    $setting = $settingGateway->getSettingByScope('Free Learning', 'defaultBrowseCourse', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray($courses)->placeholder()->selected($setting['value']);
    
    $setting = $settingGateway->getSettingByScope('Free Learning', 'collapsedSmartBlocks', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableOutcomes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);
    
    $setting = $settingGateway->getSettingByScope('Free Learning', 'outcomesIntroduction', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableExemplarWork', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableParentEvidence', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableLearningAreas', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableLearningAreaMentors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'disableMyClasses', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);
    
    $setting = $settingGateway->getSettingByScope('Free Learning', 'unitHistoryChart', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray(array('Doughnut' => __m('Doughnut'), 'Stacked Bar Chart' => __m('Stacked Bar Chart'), 'None' => __('None')))->required()->selected($setting['value']);

    $form->addRow()->addHeading(__m('Approval Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableManualBadges', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'genderOnFeedback', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $form->addRow()->addHeading(__m('CLI Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'studentEvidencePrompt', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addNumber($setting['name'])->required()->setValue($setting['value'])->maxLength(2)->minimum(1)->maximum(99);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'mentorshipAcceptancePrompt', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addNumber($setting['name'])->required()->setValue($setting['value'])->maxLength(2)->minimum(1)->maximum(99);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'evidenceOutstandingPrompt', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addNumber($setting['name'])->required()->setValue($setting['value'])->maxLength(2)->minimum(1)->maximum(99);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
