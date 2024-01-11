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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\DataUpdater\StaffUpdateGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/data_staff.php&gibbonStaffID=$gibbonStaffID&gibbonPersonID=$gibbonPersonID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $staffGateway = $container->get(StaffGateway::class);
    $staffUpdateGateway = $container->get(StaffUpdateGateway::class);

    // Check required values
    if (empty($gibbonStaffID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        return;
    }

    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0";
        header("Location: {$URL}");
        return;
    }

    // Check database records exist
    $values = $staffGateway->getByID($gibbonStaffID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        return;
    }

    // Check access to staff record
    if ($highestAction == 'Update Staff Data_my' && $values['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        return;
    }

    // Proceed!
    $data = [
        'gibbonStaffID'         => $gibbonStaffID,
        'initials'              => $_POST['initials'] ?? '',
        'type'                  => $_POST['type'] ?? '',
        'jobTitle'              => $_POST['jobTitle'] ?? '',
        'firstAidQualified'     => $_POST['firstAidQualified'] ?? '',
        'firstAidQualification' => $_POST['firstAidQualification'] ?? '',
        'firstAidExpiry'        => !empty($_POST['firstAidExpiry']) ? Format::dateConvert($_POST['firstAidExpiry']) : null,
        'countryOfOrigin'       => $_POST['countryOfOrigin'] ?? '',
        'qualifications'        => $_POST['qualifications'] ?? '',
        'biography'             => $_POST['biography'] ?? '',
    ];

    // COMPARE VALUES: Has the data changed?
    $dataChanged = false;
    foreach ($values as $key => $value) {
        if (!isset($data[$key])) continue; // Skip fields we don't plan to update
        if (empty($data[$key]) && empty($value)) continue; // Nulls, false and empty strings should cause no change

        if ($data[$key] != $value) {
            $dataChanged = true;
        }
    }

    // CUSTOM FIELDS
    $customRequireFail = false;
    $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Staff', ['dataUpdater' => 1], $customRequireFail);

    // Check for data changed
    $existingFields = json_decode($values['fields'], true);
    $newFields = json_decode($fields, true);
    foreach ($newFields as $key => $fieldValue) {
        if ($existingFields[$key] != $fieldValue) {
            $dataChanged = true;
        }
    }

    // Auto-accept updates where no data had changed
    $data['status'] = $dataChanged ? 'Pending' : 'Complete';
    $data['fields'] = $fields ?? '';

    // Write to database
    $gibbonStaffUpdateID = $_POST['existing'] ?? 'N';
    $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
    $data['gibbonPersonIDUpdater'] = $session->get('gibbonPersonID');
    $data['timestamp'] = date('Y-m-d H:i:s');

    if ($gibbonStaffUpdateID != 'N') {
        $success = $staffUpdateGateway->update($gibbonStaffUpdateID, $data);
    } else {
        $success = $staffUpdateGateway->insert($data);
    }

    if (!$success || !$pdo->getQuerySuccess()) {
        echo $pdo->getErrorMessage();
        die();
    }

    if ($dataChanged) {
        // Raise a new notification event
        $event = new NotificationEvent('Data Updater', 'Staff Data Updates');

        $event->addRecipient($session->get('organisationDBA'));
        $event->setNotificationText(__('A staff data update request has been submitted.'));
        $event->setActionLink('/index.php?q=/modules/Data Updater/data_staff_manage.php');

        $event->sendNotifications($pdo, $session);
    }

    $URLSuccess = $highestAction == 'Update Staff Data_any'
        ? $session->get('absoluteURL')."/index.php?q=/modules/Data Updater/data_staff.php&gibbonStaffID=$gibbonStaffID&gibbonPersonID=$gibbonPersonID"
        : $session->get('absoluteURL')."/index.php?q=/modules/Data Updater/data_updates.php&gibbonStaffID=$gibbonStaffID&gibbonPersonID=$gibbonPersonID";

    $URLSuccess .= '&return=success0';
    header("Location: {$URLSuccess}");
}
