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

use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;
use Gibbon\Domain\Students\FirstAidGateway;
use Gibbon\Domain\Students\FirstAidFollowupGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonFirstAidID = $_GET['gibbonFirstAidID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/firstAidRecord_edit.php&gibbonFirstAidID=$gibbonFirstAidID&gibbonFormGroupID=".$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (empty($gibbonFirstAidID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $firstAidGateway = $container->get(FirstAidGateway::class);
    $values = $firstAidGateway->getByID($gibbonFirstAidID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $gibbonPersonID = $values['gibbonPersonIDFirstAider'];
    $timeOut = !empty($_POST['timeOut']) ? $_POST['timeOut'] : null;
    $followUp = $_POST['followUp'] ?? '';

    // Only users with edit access can change this record
    if ($highestAction == 'First Aid Record_editAll') {
        $customRequireFail = false;
        $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('First Aid', [], $customRequireFail);

        if ($customRequireFail) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Update the record
        $data = ['timeOut' => $timeOut, 'fields' => $fields];
        $firstAidGateway->update($gibbonFirstAidID, $data);
    }

    // Add a new follow up log, if needed
    if (!empty($followUp)) {
        $firstAidFollowUpGateway = $container->get(FirstAidFollowupGateway::class);

        $data = [
            'gibbonFirstAidID' => $gibbonFirstAidID,
            'gibbonPersonID' => $session->get('gibbonPersonID'),
            'followUp' => $followUp,
        ];

        $inserted = $firstAidFollowUpGateway->insert($data);

        if (!$inserted) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");

}
