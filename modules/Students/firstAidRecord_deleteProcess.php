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

use Gibbon\Data\Validator;
use Gibbon\Contracts\Filesystem\FileHandler;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\Students\FirstAidGateway;
use Gibbon\Domain\Students\FirstAidFollowupGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonFirstAidID = $_POST['gibbonFirstAidID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Students/firstAidRecord.php&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonSchoolYearID='.$gibbonSchoolYearID.'&gibbonYearGroupID='.$gibbonYearGroupID;

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonFirstAidID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction != 'First Aid Record_editAll') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $firstAidGateway = $container->get(FirstAidGateway::class);
    $firstAidFollowupGateway = $container->get(FirstAidFollowupGateway::class);
    $customFieldGateway = $container->get(CustomFieldGateway::class);
    $fileHandler = $container->get(FileHandler::class);
    $values = $firstAidGateway->getByID($gibbonFirstAidID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $existingFields = !empty($values['fields']) ? json_decode($values['fields'], true) : [];
    $customFields = $customFieldGateway->selectCustomFields('First Aid')->fetchAll();

    foreach ($customFields as $field) {
        if (($field['type'] !== 'file' && $field['type'] !== 'image') || empty($field['gibbonCustomFieldID'])) {
            continue;
        }

        if (empty($existingFields[$field['gibbonCustomFieldID']])) {
            continue;
        }

        $fileHandler->deleteFile('gibbonFirstAid', $gibbonFirstAidID, "fields[{$field['gibbonCustomFieldID']}]" );
    }

    $firstAidFollowupGateway->deleteWhere(['gibbonFirstAidID' => $gibbonFirstAidID]);

    $deleted = $firstAidGateway->delete($gibbonFirstAidID);

    if (!$deleted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
}