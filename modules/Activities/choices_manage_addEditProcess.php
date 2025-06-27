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
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'mode'                => $_POST['mode'] ?? '',
    'gibbonActivityCategoryID' => $_POST['gibbonActivityCategoryID'] ?? '',
    'gibbonPersonID'      => $_POST['gibbonPersonID'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/choices_manage_addEdit.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Activities/choices_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $choiceGateway = $container->get(ActivityChoiceGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $choices = $_POST['choices'] ?? [];

    // Validate the required values are present
    if (empty($choices) || empty($params['gibbonPersonID']) || empty($params['gibbonActivityCategoryID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $category = $categoryGateway->getCategoryDetailsByID($params['gibbonActivityCategoryID']);
    if (empty($category)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $existingChoices = $choiceGateway->selectChoicesByPerson($params['gibbonActivityCategoryID'], $params['gibbonPersonID'])->fetchGroupedUnique();

    $category = $categoryGateway->getByID($params['gibbonActivityCategoryID']);
    $signUpChoices = $category['signUpChoices'] ?? 3;

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

        // Validate the choice number selected
        if ($choice <= 0 || $choice > $signUpChoices) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Prepare data to insert or update
        $choicesData = [
            'gibbonActivityID'         => $gibbonActivityID,
            'gibbonActivityCategoryID' => $params['gibbonActivityCategoryID'],
            'gibbonPersonID'           => $params['gibbonPersonID'],
            'choice'                   => $choice,
            'timestampModified'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),
        ];

        $gibbonActivityChoiceID = $existingChoices[$choice]['gibbonActivityChoiceID'] ?? '';

        if (!empty($gibbonActivityChoiceID)) {
            $partialFail &= !$choiceGateway->update($gibbonActivityChoiceID, $choicesData);
        } else {
            $choicesData['timestampCreated'] = date('Y-m-d H:i:s');
            $choicesData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $gibbonActivityChoiceID = $choiceGateway->insert($choicesData);
            $partialFail &= !$gibbonActivityChoiceID;
        }

        $choiceIDs[] = str_pad($gibbonActivityChoiceID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup sign ups that have been deleted
    $choiceGateway->deleteChoicesNotInList($params['gibbonActivityCategoryID'], $params['gibbonPersonID'], $choiceIDs);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header($params['mode'] == 'add'
        ? "Location: {$URL}&editID={$params['gibbonActivityCategoryID']}&editID2={$params['gibbonPersonID']}"
        : "Location: {$URL}"
    );
}
