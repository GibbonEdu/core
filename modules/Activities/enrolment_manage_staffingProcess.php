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

use Gibbon\Data\Validator;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'gibbonActivityCategoryID' => $_POST['gibbonActivityCategoryID'] ?? '',
    'sidebar'             => 'false',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/enrolment_manage_staffing.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Activities/enrolment_manage_staffing.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $activityGateway = $container->get(ActivityGateway::class);
    $staffGateway = $container->get(ActivityStaffGateway::class);

    $staffingList = $_POST['person'] ?? [];
    $roleList = $_POST['role'] ?? [];

    if (empty($params['gibbonActivityCategoryID']) || empty($staffingList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $unassigned = [];

    // Update staffing
    foreach ($staffingList as $gibbonPersonID => $gibbonActivityID) {

        $staffing = $staffGateway->selectStaffByCategoryAndPerson($params['gibbonActivityCategoryID'], $gibbonPersonID)->fetch();

        if (empty($gibbonActivityID)) {
            if (!empty($staffing)) {
                $unassigned[] = $gibbonPersonID;
            }
            continue;
        }

        if (!empty($staffing)) {
            // Update and existing staffing
            $data = [
                'gibbonActivityID' => $gibbonActivityID,
                'role'             => $roleList[$gibbonPersonID] ?? 'Assistant',
            ];

            $updated = $staffGateway->update($staffing['gibbonActivityStaffID'], $data);
        } else {
            // Add a new staffing
            $data = [
                'gibbonActivityID' => $gibbonActivityID,
                'gibbonPersonID'   => $gibbonPersonID,
                'role'             => $roleList[$gibbonPersonID] ?? 'Assistant',
            ];

            $inserted = $staffGateway->insert($data);
            $partialFail &= !$inserted;
        }
    }

    // Remove staffing that have been unassigned
    foreach ($unassigned as $gibbonPersonID) {
        $staffGateway->deleteStaffByCategory($params['gibbonActivityCategoryID'], $gibbonPersonID);
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";
    header("Location: {$URL}");
}
