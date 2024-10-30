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
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
    'gibbonActivityID' => $_REQUEST['gibbonActivityID'] ?? (!empty($_POST['choices'])? current($_POST['choices']) : ''),
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/explore_activity.php&sidebar=false&'.http_build_query($params);
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Activities/activities_my.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity_signUp.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $activityGateway = $container->get(ActivityGateway::class);
    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $choiceGateway = $container->get(ActivityChoiceGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $choices = $_POST['choices'] ?? [];

    // Only users with manage permission can sign up a different user
    $canManageChoice = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');
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
    $category = $categoryGateway->getCategoryDetailsByID($params['gibbonActivityCategoryID'] ?? '');
    if (empty($category)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check that sign up is open based on the date
    $signUpIsOpen = false;
    if (!empty($category['accessOpenDate']) && !empty($category['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    // Check the student's sign up access based on their year group
    $signUpCategory = $categoryGateway->getCategorySignUpAccess($params['gibbonActivityCategoryID'], $gibbonPersonID);

    if (!$signUpIsOpen || !$signUpCategory) {
        $URL .= '&return=error4';
        header("Location: {$URL}");
        exit;
    }

    // Get experiences and choices
    $activities = $activityGateway->selectActivitiesByCategoryAndPerson($params['gibbonActivityCategoryID'], $gibbonPersonID)->fetchKeyPair();
    $choicesSelected = $choiceGateway->selectChoicesByPerson($params['gibbonActivityCategoryID'], $gibbonPersonID)->fetchGroupedUnique();

    $category = $categoryGateway->getByID($params['gibbonActivityCategoryID']);
    $signUpChoices = $category['signUpChoices'] ?? 3;

    // Lower the choice limit if there are less options
    if (count($activities) < $signUpChoices) {
        $signUpChoices = count($activities);
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

        $signUpActivity = $activityGateway->getActivitySignUpAccess($gibbonActivityID, $gibbonPersonID);
        if (!$signUpActivity) {
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
            'gibbonActivityID'         => $gibbonActivityID,
            'gibbonActivityCategoryID' => $params['gibbonActivityCategoryID'],
            'gibbonPersonID'           => $gibbonPersonID,
            'choice'                   => $choice,
            'timestampModified'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),
        ];

        $gibbonActivityChoiceID = $choicesSelected[$choice]['gibbonActivityChoiceID'] ?? '';

        if (!empty($gibbonActivityChoiceID)) {
            $partialFail &= !$choiceGateway->update($gibbonActivityChoiceID, $signUpData);
        } else {
            $signUpData['timestampCreated'] = date('Y-m-d H:i:s');
            $signUpData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $gibbonActivityChoiceID = $choiceGateway->insert($signUpData);
            $partialFail &= !$gibbonActivityChoiceID;
        }

        $choiceIDs[] = str_pad($gibbonActivityChoiceID, 12, '0', STR_PAD_LEFT);
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
