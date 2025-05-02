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
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
use Gibbon\Module\FreeLearning\Domain\MentorGroupPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == false) {
    // Access denied
    echo Format::alert(__('You do not have access to this action.'));
} else {
    $settingGateway = $container->get(SettingGateway::class);

    // Get enrolment settings
    $enableSchoolMentorEnrolment = $settingGateway->getSettingByScope('Free Learning', 'enableSchoolMentorEnrolment');
    $enableExternalMentorEnrolment = $settingGateway->getSettingByScope('Free Learning', 'enableExternalMentorEnrolment');
    $enableClassEnrolment = $roleCategory == 'Student'
        ? $settingGateway->getSettingByScope('Free Learning', 'enableClassEnrolment')
        : 'N';

    //Check whether any enrolment methods are available
    if ($enableSchoolMentorEnrolment != 'Y' && $enableExternalMentorEnrolment != 'Y' && $enableClassEnrolment != 'Y') {
        echo Format::alert(__m('Enrolment is currently disabled: units can be viewed but not joined.'), 'message');
        return;
    }

    // Check ability to enrol
    $proceed = false;
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonPersonID = $session->get('gibbonPersonID');

    if ($highestAction == 'Browse Units_all') {
        $proceed = true;
    } elseif ($highestAction == 'Browse Units_prerequisites') {
        if ($values['freeLearningUnitIDPrerequisiteList'] == null or $values['freeLearningUnitIDPrerequisiteList'] == '') {
            $proceed = true;
        } else {
            $prerequisitesActive = prerequisitesRemoveInactive($connection2, $values['freeLearningUnitIDPrerequisiteList']);
            $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive, true);
            if ($prerequisitesMet) {
                $proceed = true;
            }
        }
    }

    if ($proceed == false && $values['status'] != 'Exempt') {
        echo Format::alert(__m('You cannot enrol, as you have not fully met the prerequisites for this unit.'), 'warning');
        return;
    }

    // Check enrolment status
    $unitStudentGateway = $container->get(UnitStudentGateway::class);
    $rowEnrol = $unitStudentGateway->getUnitStudentDetailsByID($freeLearningUnitID, $gibbonPersonID);

    if (empty($rowEnrol)) {

        // ENROL NOW
        $form = Form::create('enrol', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_enrolProcess.php?'.http_build_query($urlParams));
        $form->setTitle(__m('Enrol Now'));
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);

        $enrolmentMethodSelected = '';
        $enrolmentMethods = [];

        if ($enableExternalMentorEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['externalMentor'] = __m('External Mentor');
        }
        if ($enableSchoolMentorEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['schoolMentor'] = __m('School Mentor');
        }
        if ($enableClassEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['class'] = __m('Timetable Class');
        }

        $row = $form->addRow();
            $row->addLabel('enrolmentMethod', __m('Enrolment Method'));
            $row->addRadio('enrolmentMethod')->fromArray(array_reverse($enrolmentMethods))->required();

        // CLASS ENROLMENT
        if ($enableClassEnrolment == 'Y') {
            $form->toggleVisibilityByClass('classEnrolment')->onRadio('enrolmentMethod')->when('class');

            $row = $form->addRow()->addClass('classEnrolment');
                $row->addLabel('gibbonCourseClassID', __m('Class'))->description(__m('Which class are you enroling for?'));

            $disableMyClasses = $settingGateway->getSettingByScope('Free Learning', 'disableMyClasses');
            $select = $row->addSelectClass('gibbonCourseClassID', $gibbonSchoolYearID, $gibbonPersonID, [
                'allClasses' => false,
                'courseFilter' => 'Free Learning',
                'departments' => $values['gibbonDepartmentIDList'],
            ])->required();

            if ($disableMyClasses == "Y") {
                $select->fromArray([__('My Classes') => []]);
            }
        }

        // SCHOOL MENTOR
        if ($enableSchoolMentorEnrolment == 'Y') {
            $form->toggleVisibilityByClass('schoolMentorEnrolment')->onRadio('enrolmentMethod')->when('schoolMentor');

            $params['schoolMentorCompletors'] = $values['schoolMentorCompletors'];
            $params['schoolMentorCustom'] = $values['schoolMentorCustom'];
            $params['schoolMentorCustomRole'] = $values['schoolMentorCustomRole'];
            $params['disableLearningAreaMentors'] = $settingGateway->getSettingByScope('Free Learning', 'disableLearningAreaMentors');

            // Check if there are pre-defined mentors first
            $mentorGroups = $container->get(MentorGroupPersonGateway::class)->selectMentorsByStudent($gibbonPersonID)->fetchGrouped();

            if (!empty($mentorGroups)) {
                $mentors = array_map(function ($list) {
                    return Format::nameListArray($list, 'Staff', true, true);
                }, $mentorGroups);
                $form->addHiddenValue('mentorGroup', 'Y');
            } else {
                // Otherwise, load the selection of mentors for this unit
                $mentors = $unitStudentGateway->selectUnitMentors($freeLearningUnitID, $gibbonPersonID, $params)->fetchAll();
                $mentors = Format::nameListArray($mentors, 'Staff', true, true);
            }

            $row = $form->addRow()->addClass('schoolMentorEnrolment');
                $row->addLabel('gibbonPersonIDSchoolMentor', __m('School Mentor'))->description(!empty($mentorGroups) ? __m('Mentors based on your assigned mentor groups.') : '');
                $row->addSelectPerson('gibbonPersonIDSchoolMentor')->fromArray($mentors)->required()->placeholder();
        }

        // EXTERNAL MENTOR
        if ($enableExternalMentorEnrolment == 'Y') {
            $form->toggleVisibilityByClass('externalMentorEnrolment')->onRadio('enrolmentMethod')->when('externalMentor');

            $row = $form->addRow()->addClass('externalMentorEnrolment');
                $row->addLabel('nameExternalMentor', __m('External Mentor Name'));
                $row->addTextField('nameExternalMentor')->required();

            $row = $form->addRow()->addClass('externalMentorEnrolment');
                $row->addLabel('emailExternalMentor', __m('External Mentor Email'));
                $row->addEmail('emailExternalMentor')->required();
        }

        // GROUPING
        $groupings = [];
        $extraSlots = 0;
        if (strpos($values['grouping'], 'Individual') !== false) {
            $groupings['Individual'] = __m('Individual');
        }
        if (strpos($values['grouping'], 'Pairs') !== false OR strpos($values['grouping'], 'Threes') !== false OR strpos($values['grouping'], 'Fours') !== false OR strpos($values['grouping'], 'Fives') !== false) {
            $form->toggleVisibilityByClass('group1')->onSelect('grouping')->when(['Pairs','Threes','Fours','Fives']);
            if (strpos($values['grouping'], 'Pairs') !== false) {
                $groupings['Pairs'] = __m('Pair');
            }
            $extraSlots = 1;
        }
        if (strpos($values['grouping'], 'Threes') !== false OR strpos($values['grouping'], 'Fours') !== false OR strpos($values['grouping'], 'Fives') !== false) {
            $form->toggleVisibilityByClass('group2')->onSelect('grouping')->when(['Threes','Fours','Fives']);
            if (strpos($values['grouping'], 'Threes') !== false) {
                $groupings['Threes'] = __m('Three');
            }
            $extraSlots = 2;
        }
        if (strpos($values['grouping'], 'Fours') !== false OR strpos($values['grouping'], 'Fives') !== false) {
            $form->toggleVisibilityByClass('group3')->onSelect('grouping')->when(['Fours','Fives']);
            if (strpos($values['grouping'], 'Fours') !== false) {
                $groupings['Fours'] = __m('Four');
            }
            $extraSlots = 3;
        }
        if (strpos($values['grouping'], 'Fives') !== false) {
            $form->toggleVisibilityByClass('group4')->onSelect('grouping')->when(['Fives']);
            if (strpos($values['grouping'], 'Fives') !== false) {
                $groupings['Fives'] = __m('Five');
            }
            $extraSlots = 4;
        }

        $row = $form->addRow();
            $row->addLabel('grouping', __m('Grouping'))->description(__m('How do you want to study this unit?'));
            $row->addSelect('grouping')->fromArray($groupings)->required()->placeholder();

        // COLLABORATORS
        if ($extraSlots > 0) {
            $prerequisitesActive = prerequisitesRemoveInactive($connection2, $roleCategory == 'Student' ? $values['freeLearningUnitIDPrerequisiteList'] : '');
            $prerequisiteCount = !empty($prerequisitesActive) ? count(explode(',', $prerequisitesActive)) : 0;

            $collaborators = $unitStudentGateway->selectPotentialCollaborators($gibbonSchoolYearID, $gibbonPersonID, $roleCategory, $prerequisiteCount, $values)->fetchAll();
            $collaborators = Format::nameListArray($collaborators, 'Student', true);

            for ($i = 1; $i <= $extraSlots; ++$i) {
                $row = $form->addRow()->addClass('group'.$i);
                    $row->addLabel('collaborators', __m('Collaborator {number}', ['number' => $i]));
                    $row->addSelect('collaborators[]')
                        ->setID('collaborator'.$i)
                        ->fromArray($collaborators)
                        ->required()
                        ->placeholder();
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__m('Enrol Now'));

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Current' or $rowEnrol['status'] == 'Current - Pending' or $rowEnrol['status'] == 'Evidence Not Yet Approved') {
        // Currently enrolled, allow to set status to complete and submit feedback...or previously submitted evidence not accepted

        $form = Form::create('enrolComment', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
        $form->setClass('blank');
        $form->setTitle(__m('Currently Enrolled'));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

        if ($rowEnrol['status'] == 'Current - Pending') {
            $form->setDescription(sprintf(__m('You are currently enrolled in %1$s, but your chosen mentor has yet to confirm their participation. You cannot submit evidence until they have done so.'), $values['name']));
        } else {
            $description = '';

            if (!empty($rowEnrol['collaborationKey'])) {
                $collaborators = $unitStudentGateway->selectUnitCollaboratorsByKey($rowEnrol['collaborationKey'])->fetchAll();
                $collaborators = Format::nameListArray($collaborators, 'Student');

                $description .=  Format::alert(__m('Your Group').': '. implode(', ', $collaborators), 'message');
                
                $collaborativeAssessment = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment');
                if ($collaborativeAssessment == 'Y') {
                    $description .= Format::alert(__m('Collaborative Assessment is enabled: by submitting this work, you will be submitting on behalf of your collaborators as well as yourself.'), 'message');
                }


            }

            if ($rowEnrol['status'] == 'Current') {
                $description .= '<p>'.sprintf(__m('You are currently enrolled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your class teacher or mentor will be notified, and will approve your unit completion in due course.'), $values['name']).'</p>';
            } elseif ($rowEnrol['status'] == 'Evidence Not Yet Approved') {
                $description .= Format::alert(__m('Your evidence has not been approved. Please read the feedback below, adjust your evidence, and submit again:'), 'warning');
            }
            $form->setDescription($description);

            // DISCUSSION
            $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();

            $logs = array_map(function ($item) {
                $item['comment'] = Format::hyperlinkAll($item['comment']);
                $item['type'] = __m($item['type']);
                return $item;
            }, $logs);

            $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'title' => __('Comments'),
                'discussion' => $logs
            ]));

            // ADD COMMENT
            if ($rowEnrol['enrolmentMethod'] != 'externalMentor') {
                $commentBox = $form->getFactory()->createColumn()->addClass('flex flex-col');
                $commentBox->addTextArea('addComment')
                    ->placeholder(__m('Leave a comment'))
                    ->setClass('flex w-full')
                    ->setRows(3);
                $commentBox->addSubmit(__m('Add Comment'))
                    ->setColor('gray')
                    ->setClass('text-right mt-2');

                $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                    'discussion' => [[
                        'surname' => $session->get('surname'),
                        'preferredName' => $session->get('preferredName'),
                        'image_240' => $session->get('image_240'),
                        'comment' => $commentBox->getOutput(),
                    ]]
                ]));
            }

            echo $form->getOutput();

            // SUBMIT EVIDENCE
            $form = Form::create('enrol', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_completePendingProcess.php?'.http_build_query($urlParams));

            $form->setAutocomplete(false); // Prevent students selecting links from autocomplete
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
            $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

            $form->addRow()->addHeading(__m('Submit Your Evidence'));

            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addTextField('status')->readonly()->setValue(__m('Complete - Pending'));

            $row = $form->addRow();
                $row->addLabel('commentStudent', __('Comment'))->description(!empty($values['studentReflectionText']) ? $values['studentReflectionText'] : __m('Leave a brief reflective comment on this unit<br/>and what you learned.'));
                $row->addTextArea('commentStudent')->setRows(4)->required();


            $availableSubmissionTypes = $settingGateway->getSettingByScope('Free Learning', 'availableSubmissionTypes');
            if ($availableSubmissionTypes == "Link/File") {
                $types = ['Link' => __('Link'), 'File' => __('File')];
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addRadio('type')->fromArray($types)->inline()->required()->checked('Link');

                $form->toggleVisibilityByClass('evidenceFile')->onRadio('type')->when('File');
                $form->toggleVisibilityByClass('evidenceLink')->onRadio('type')->when('Link');
            } else if ($availableSubmissionTypes == "File") {
                $form->addHiddenValue('type', 'File');
            } else if ($availableSubmissionTypes == "Link") {
                $form->addHiddenValue('type', 'Link');
            }

            // File
            if ($availableSubmissionTypes == "Link/File" or $availableSubmissionTypes == "File") {
                $fileUploader = $container->get(FileUploader::class);
                $row = $form->addRow()->addClass('evidenceFile');
                    $row->addLabel('file', __('Submit File'));
                    $row->addFileUpload('file')->accepts($fileUploader->getFileExtensions())->required();
            }

            // Link
            if ($availableSubmissionTypes == "Link/File" or $availableSubmissionTypes == "Link") {
                $row = $form->addRow()->addClass('evidenceLink');
                    $row->addLabel('link', __('Submit Link'));
                    $row->addURL('link')->maxLength(255)->required();
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
        }

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Complete - Pending') {
        // Waiting for teacher feedback

        $form = Form::create('enrolComment', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
        $form->setClass('blank');

        $form->setTitle(__m('Complete - Pending Approval'));
        $form->setDescription(__m('Your evidence, shown below, has been submitted to your teacher/mentor for approval. This screen will show a teacher comment, once approval has been given.'));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

        $evidenceLink = $rowEnrol['evidenceType'] == 'Link' ? $rowEnrol['evidenceLocation'] : './'.$rowEnrol['evidenceLocation'];

        // DISCUSSION
        $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();

        $logs = array_map(function ($item) {
            $item['comment'] = Format::hyperlinkAll($item['comment']);
            $item['type'] = __m($item['type']);
            return $item;
        }, $logs);

        $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
            'title' => __('Comments'),
            'discussion' => $logs
        ]));

        // ADD COMMENT
        if ($rowEnrol['enrolmentMethod'] != 'externalMentor') {
            $commentBox = $form->getFactory()->createColumn();
            $commentBox->addTextArea('addComment')
                ->placeholder(__m('Leave a comment'))
                ->setClass('flex w-full')
                ->setRows(3);
            $commentBox->addSubmit(__m('Add Comment'))
                ->setColor('gray')
                ->setClass('text-right mt-2');

            $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => [[
                    'surname' => $session->get('surname'),
                    'preferredName' => $session->get('preferredName'),
                    'image_240' => $session->get('image_240'),
                    'comment' => $commentBox->getOutput(),
                ]]
            ]));
        }

        echo $form->getOutput();

        // EVIDENCE DETAILS
        $form = Form::create('enrol', '');
        $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readonly()->setValue($values['status']);

        $row = $form->addRow();
            $row->addLabel('evidenceTypeLabel', __m('Evidence Type'));
            $row->addTextField('evidenceType')->readonly()->setValue($rowEnrol['evidenceType']);

        $row = $form->addRow();
            $row->addLabel('evidence', __m('Evidence'));
            $row->addContent(Format::link($evidenceLink, __m('View'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Complete - Approved') {
        // Complete, show status and feedback from teacher.

        $form = Form::create('enrolComment', $session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
        $form->setClass('blank');
        $form->setTitle(__m('Complete - Approved'));
        $form->setDescription(__m("Congratulations! Your evidence, shown below, has been accepted and approved by your teacher, and so you have successfully completed this unit. Please look below for your teacher's comment."));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

        // DISCUSSION
        $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();

        $logs = array_map(function ($item) {
            $item['comment'] = Format::hyperlinkAll($item['comment']);
            $item['type'] = __m($item['type']);
            return $item;
        }, $logs);

        $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
            'title' => __('Comments'),
            'discussion' => $logs
        ]));

        // ADD COMMENT
        $commentBox = $form->getFactory()->createColumn()->addClass('flex flex-col');
        $commentBox->addTextArea('addComment')
            ->placeholder(__m('Leave a comment'))
            ->setClass('flex w-full')
            ->setRows(3);
        $commentBox->addSubmit(__m('Add Comment'))
            ->setColor('gray')
            ->setClass('text-right mt-2');

        $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
            'discussion' => [[
                'surname' => $session->get('surname'),
                'preferredName' => $session->get('preferredName'),
                'image_240' => $session->get('image_240'),
                'comment' => $commentBox->getOutput(),
            ]]
        ]));

        echo $form->getOutput();

        $evidenceLink = $rowEnrol['evidenceType'] == 'Link' ? $rowEnrol['evidenceLocation']: './'.$rowEnrol['evidenceLocation'];

        $form = Form::create('enrol', '');

        $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readonly()->setValue($values['status']);

        $row = $form->addRow();
            $row->addLabel('evidenceTypeLabel', __m('Evidence Type'));
            $row->addTextField('evidenceType')->readonly()->setValue($rowEnrol['evidenceType']);

        $row = $form->addRow();
            $row->addLabel('evidence', __m('Evidence'));
            $row->addContent(Format::link($evidenceLink, __m('View'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

        if ($settingGateway->getSettingByScope('Free Learning', 'certificatesAvailable') == "Y") {
            $certificateLink = './modules/Free Learning/units_browse_details_enrol_certificate.php?freeLearningUnitID='.$freeLearningUnitID;
            $row = $form->addRow();
                $row->addLabel('certificate', __m('Certificate of Completion'));
                $row->addContent(Format::link($certificateLink, __m('Print Certificate'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));
        }

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Exempt') {
        // Exempt, let student know

        $form = Form::create('enrol', '');
        $form->addClass('blank');
        $form->setTitle(__m('Exempt'));
        $form->setDescription(__m('You are exempt from completing this unit, which means you get the status of completion, without needing to submit any evidence.'));

        echo $form->getOutput();
    }

}
