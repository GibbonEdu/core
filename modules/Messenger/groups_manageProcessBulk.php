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

use Gibbon\Domain\Messenger\GroupGateway;

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage.php";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $action = $_POST['action'] ?? '';
    $gibbonGroupIDList = $_POST['gibbonGroupIDList'] ?? '';
    $gibbonSchoolYearIDCopyTo = $_POST['gibbonSchoolYearIDCopyTo'] ?? array();

    if (empty($action) || empty($gibbonSchoolYearIDCopyTo) || empty($gibbonGroupIDList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $groupGateway = $container->get(GroupGateway::class);
        $partialFail = false;

        foreach ($gibbonGroupIDList as $gibbonGroupID) {
            $data = $groupGateway->getByID($gibbonGroupID);
            $data['gibbonSchoolYearID'] = $gibbonSchoolYearIDCopyTo;

            // Copy groups to selected year
            $inserted = $groupGateway->insert($data);
            $partialFail &= !$inserted;

            // Optionally add members to new group
            if ($inserted && $action == 'CopyMembers') {
                $members = $groupGateway->selectPersonIDsByGroup($gibbonGroupID)->fetchAll();
                if (empty($members)) continue;

                foreach ($members as $member) {
                    $insertedMember = $groupGateway->insertGroupPerson([
                        'gibbonGroupID' => $inserted,
                        'gibbonPersonID' => $member['gibbonPersonID'],
                    ]);
                    $partialFail &= !$insertedMember;
                }
            }
        }

        $URL .= $partialFail
            ? "&return=warning1"
            : "&return=success0";

        header("Location: {$URL}");
    }
}
