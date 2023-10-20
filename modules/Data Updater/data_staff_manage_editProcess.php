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
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\DataUpdater\StaffUpdateGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffUpdateID = $_GET['gibbonStaffUpdateID'] ?? '';
$gibbonStaffID = $_POST['gibbonStaffID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/data_staff_manage_edit.php&gibbonStaffUpdateID=$gibbonStaffUpdateID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $staffUpdateGateway = $container->get(StaffUpdateGateway::class);
    $staffGateway = $container->get(StaffGateway::class);

    // Check required values
    if (empty($gibbonStaffUpdateID) || empty($gibbonStaffID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        return;
    }

    // Check database records exist
    $values = $staffUpdateGateway->getByID($gibbonStaffUpdateID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        return;
    }

    // Begin checking data update
    $compare = [
        'initials'              => __('Initials'),
        'type'                  => __('Type'),
        'jobTitle'              => __('Job Title'),
        'firstAidQualified'     => __('First Aid Qualified?'),
        'firstAidQualification' => __('First Aid Qualification'),
        'firstAidExpiry'        => __('First Aid Expiry'),
        'countryOfOrigin'       => __('Country Of Origin'),
        'qualifications'        => __('Qualifications'),
        'biography'             => __('Biography'),
    ];

    $data = [];
    foreach ($compare as $field => $name) {
        if (isset($_POST["new{$field}On"]) && $_POST["new{$field}On"] = 'on') {
            $data[$field] = $_POST["new{$field}"] ?? '';
        }
    }

    // CUSTOM FIELDS
    $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromDataUpdate('Staff', [], $values['fields']);
    if (!empty($fields)) {
        $data['fields'] = $fields;
    }

    // Update the staff record
    if (!empty($data)) {
        $updated = $staffGateway->update($gibbonStaffID, $data);
        if (!$updated) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            return;
        }
    }

    // Update the update
    $staffUpdateGateway->update($gibbonStaffUpdateID, ['status' => 'Complete']);

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
