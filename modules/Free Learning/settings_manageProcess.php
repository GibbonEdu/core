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

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/settings_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $bigDataSchool = $_POST['bigDataSchool'] ?? '';
    $publicUnits = $_POST['publicUnits'] ?? '';
    $difficultyOptions = $_POST['difficultyOptions'] ?? '';
    $unitOutlineTemplate = $_POST['unitOutlineTemplate'] ?? '';
    $smartBlocksTemplate = $_POST['smartBlocksTemplate'] ?? '';
    $learningAreaRestriction = $_POST['learningAreaRestriction'] ?? '';
    $customField = $_POST['customField'] ?? '';
    $maxMapSize = $_POST['maxMapSize'] ?? '';
    $enableClassEnrolment = $_POST['enableClassEnrolment'] ?? '';
    $enableSchoolMentorEnrolment = $_POST['enableSchoolMentorEnrolment'] ?? '';
    $enableExternalMentorEnrolment = $_POST['enableExternalMentorEnrolment'] ?? '';
    $autoAcceptMentorGroups = $_POST['autoAcceptMentorGroups'] ?? '';
    $showContentOnEnrol = $_POST['showContentOnEnrol'] ?? '';
    $collaborativeAssessment = $_POST['collaborativeAssessment'] ?? '';
    $availableSubmissionTypes = $_POST['availableSubmissionTypes'] ?? 'Link/File';
    $certificatesAvailable = $_POST['certificatesAvailable'] ?? '';
    $certificateTemplate = $_POST['certificateTemplate'] ?? '';
    $certificateOrientation = $_POST['certificateOrientation'] ?? '';
    $collapsedSmartBlocks = $_POST['collapsedSmartBlocks'] ?? '';
    $disableOutcomes = $_POST['disableOutcomes'] ?? '';
    $outcomesIntroduction = $_POST['outcomesIntroduction'] ?? '';
    $disableExemplarWork = $_POST['disableExemplarWork'] ?? '';
    $disableParentEvidence = $_POST['disableParentEvidence'] ?? '';
    $disableLearningAreas = $_POST['disableLearningAreas'] ?? '';
    $disableLearningAreaMentors = $_POST['disableLearningAreaMentors'] ?? '';
    $disableMyClasses = $_POST['disableMyClasses'] ?? '';
    $unitHistoryChart = $_POST['unitHistoryChart'] ?? '';
    $enableManualBadges = $_POST['enableManualBadges'] ?? '';
    $genderOnFeedback = $_POST['genderOnFeedback'] ?? '';
    $studentEvidencePrompt = $_POST['studentEvidencePrompt'] ?? '';
    $mentorshipAcceptancePrompt = $_POST['mentorshipAcceptancePrompt'] ?? '';
    $evidenceOutstandingPrompt = $_POST['evidenceOutstandingPrompt'] ?? '';
    $defaultBrowseView = $_POST['defaultBrowseView'] ?? 'Map';
    $defaultBrowseCourse = $_POST['defaultBrowseCourse'] ?? '';


    $settingGateway = $container->get(SettingGateway::class);

    //Validate Inputs
    if ($difficultyOptions == '' or $publicUnits == '' or $learningAreaRestriction == ''or $enableClassEnrolment == '' or $enableSchoolMentorEnrolment == '' or $enableExternalMentorEnrolment == '' or $autoAcceptMentorGroups == '' or $showContentOnEnrol == '' or $collaborativeAssessment == '' or $availableSubmissionTypes == '' or $certificatesAvailable == '' or $disableOutcomes == '' or $disableExemplarWork == '' or $disableParentEvidence == '' or $disableLearningAreas == '' or $disableLearningAreaMentors == '' or $disableMyClasses == '' or $unitHistoryChart == '' or $enableManualBadges == '' or $genderOnFeedback == '' or $studentEvidencePrompt == '' or $mentorshipAcceptancePrompt == '' or $evidenceOutstandingPrompt == '') {
        //Fail 3
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } else {
        //Write to database
        $partialFail = false;

        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'bigDataSchool', $bigDataSchool);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'publicUnits', $publicUnits);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'difficultyOptions', $difficultyOptions);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'unitOutlineTemplate', $unitOutlineTemplate);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'smartBlocksTemplate', $smartBlocksTemplate);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'learningAreaRestriction', $learningAreaRestriction);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'customField', $customField);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'maxMapSize', $maxMapSize);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableClassEnrolment', $enableClassEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableSchoolMentorEnrolment', $enableSchoolMentorEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableExternalMentorEnrolment', $enableExternalMentorEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'autoAcceptMentorGroups', $autoAcceptMentorGroups);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'showContentOnEnrol', $showContentOnEnrol);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'collaborativeAssessment', $collaborativeAssessment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'availableSubmissionTypes', $availableSubmissionTypes);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificatesAvailable', $certificatesAvailable);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificateTemplate', $certificateTemplate);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificateOrientation', $certificateOrientation);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'collapsedSmartBlocks', $collapsedSmartBlocks);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableOutcomes', $disableOutcomes);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'outcomesIntroduction', $outcomesIntroduction);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableExemplarWork', $disableExemplarWork);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableParentEvidence', $disableParentEvidence);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableLearningAreas', $disableLearningAreas);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableLearningAreaMentors', $disableLearningAreaMentors);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableMyClasses', $disableMyClasses);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'unitHistoryChart', $unitHistoryChart);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableManualBadges', $enableManualBadges);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'genderOnFeedback', $genderOnFeedback);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'studentEvidencePrompt', $studentEvidencePrompt);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'mentorshipAcceptancePrompt', $mentorshipAcceptancePrompt);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'evidenceOutstandingPrompt', $evidenceOutstandingPrompt);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'defaultBrowseView', $defaultBrowseView);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'defaultBrowseCourse', $defaultBrowseCourse);

        $URL .= $partialFail
            ? '&return=error2'
            : '&return=success0';
        header("Location: {$URL}");
    }
}
