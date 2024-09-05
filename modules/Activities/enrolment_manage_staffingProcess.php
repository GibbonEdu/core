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

    $assigned = [];

    // Update staffing
    foreach ($staffingList as $gibbonPersonID => $personActivities) {

        foreach ($personActivities as $listIndex => $gibbonActivityID) {
            if (empty($gibbonActivityID)) {
                continue;
            }

            $staffing = $staffGateway->selectBy(['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID])->fetch();

            if (!empty($staffing)) {
                // Update and existing staffing
                $updated = $staffGateway->update($staffing['gibbonActivityStaffID'], [
                    'role' => $roleList[$gibbonPersonID][$listIndex] ?? 'Assistant',
                ]);
            } else {
                // Add a new staffing
                $inserted = $staffGateway->insert([
                    'gibbonActivityID' => $gibbonActivityID,
                    'gibbonPersonID'   => $gibbonPersonID,
                    'role'             => $roleList[$gibbonPersonID][$listIndex] ?? 'Assistant',
                ]);
                $partialFail &= !$inserted;
            }

            $assigned[$gibbonActivityID][] = $gibbonPersonID;
        }
    }

    // Remove staffing that have been unassigned
    $activitiesByCategory = $activityGateway->selectActivitiesByCategory($params['gibbonActivityCategoryID'])->fetchKeyPair();

    foreach ($activitiesByCategory as $gibbonActivityID => $activityName) {
        $staffList = $assigned[$gibbonActivityID] ?? [];
        $staffGateway->deleteStaffNotInList($gibbonActivityID, $staffList);
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";
    header("Location: {$URL}");
}
