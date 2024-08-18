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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ChoiceGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
    'gibbonActivityID' => $_REQUEST['gibbonActivityID'] ?? (!empty($_POST['choices'])? current($_POST['choices']) : ''),
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/explore_activity.php&sidebar=false&'.http_build_query($params);
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Activities/viewMyDL.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity_signUp.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $choiceGateway = $container->get(ChoiceGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $choices = $_POST['choices'] ?? [];

    // Only users with manage permission can sign up a different user
    $canManageChoice = isActionAccessible($guid, $connection2, '/modules/Activities/signUp_manage.php');
    if (!$canManageChoice) {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    // Validate the required values are present
    if (empty($choices) || empty($gibbonPersonID) || empty($params['gibbonActivityCategoryID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $event = $categoryGateway->getCategoryDetailsByID($params['gibbonActivityCategoryID']);
    if (empty($event)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check that sign up is open based on the date
    $signUpIsOpen = false;
    if (!empty($event['accessOpenDate']) && !empty($event['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    // Check access based on year group
    $signUpAccess = $categoryGateway->getEventSignUpAccess($params['gibbonActivityCategoryID'], $session->get('gibbonPersonID'));

    if (!$signUpIsOpen || !$signUpAccess) {
        $URL .= '&return=error4';
        header("Location: {$URL}");
        exit;
    }

    // Get experiences and choices
    $experiences = $activityGateway->selectExperiencesByEventAndPerson($params['gibbonActivityCategoryID'], $gibbonPersonID)->fetchKeyPair();
    $choicesSelected = $choiceGateway->selectChoicesByPerson($params['gibbonActivityCategoryID'], $gibbonPersonID)->fetchGroupedUnique();
    $signUpChoices = $settingGateway->getSettingByScope('Deep Learning', 'signUpChoices');

    // Lower the choice limit if there are less options
    if (count($experiences) < $signUpChoices) {
        $signUpChoices = count($experiences);
    }

    // Update the sign up choices
    $choiceIDs = [];
    foreach ($choices as $choice => $gibbonActivityID) {
        $choice = intval($choice);

        // Validate the experience selected
        if (!$activityGateway->exists($gibbonActivityID)) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        $signUpExperience = $activityGateway->getExperienceSignUpAccess($gibbonActivityID, $session->get('gibbonPersonID'));
        if (!$signUpExperience) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Validate the choice number selected
        if ($choice <= 0 || $choice > $signUpChoices) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Prepare data to insert or update
        $signUpData = [
            'gibbonActivityID' => $gibbonActivityID,
            'gibbonActivityCategoryID'      => $params['gibbonActivityCategoryID'],
            'gibbonPersonID'           => $gibbonPersonID,
            'choice'                   => $choice,
            'timestampModified'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),
        ];

        $deepLearningChoiceID = $choicesSelected[$choice]['deepLearningChoiceID'] ?? '';

        if (!empty($deepLearningChoiceID)) {
            $partialFail &= !$choiceGateway->update($deepLearningChoiceID, $signUpData);
        } else {
            $signUpData['timestampCreated'] = date('Y-m-d H:i:s');
            $signUpData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $deepLearningChoiceID = $choiceGateway->insert($signUpData);
            $partialFail &= !$deepLearningChoiceID;
        }

        $choiceIDs[] = str_pad($deepLearningChoiceID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup sign ups that have been deleted
    $choiceGateway->deleteChoicesNotInList($params['gibbonActivityCategoryID'], $gibbonPersonID, $choiceIDs);

    if ($partialFail) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    }

    $URLSuccess .= '&return=success1';
    header("Location: {$URLSuccess}");
}
