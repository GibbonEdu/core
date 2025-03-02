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
use Gibbon\Module\FreeLearning\Domain\MentorGroupGateway;
use Gibbon\Module\FreeLearning\Domain\MentorGroupPersonGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/mentorGroups_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorGroups_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $mentorGroupGateway = $container->get(MentorGroupGateway::class);
    $mentorGroupPersonGateway = $container->get(MentorGroupPersonGateway::class);
    $partialFail = false;

    $mentors = $_POST['mentors'] ?? [];
    $students = $_POST['students'] ?? [];

    $data = [
        'name'                => $_POST['name'] ?? '',
        'assignment'          => $_POST['assignment'] ?? '',
        'gibbonCustomFieldID' => $_POST['gibbonCustomFieldID'] ?? null,
        'fieldValue'          => $_POST['fieldValue'] ?? $_POST['fieldValueSelect'] ?? null,
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['assignment'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$mentorGroupGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    $freeLearningMentorGroupID = $mentorGroupGateway->insert($data);

    // Insert one record per mentor
    foreach ($mentors as $gibbonPersonID) {
        $data = [
            'freeLearningMentorGroupID' => $freeLearningMentorGroupID,
            'gibbonPersonID' => $gibbonPersonID,
            'role' => 'Mentor',
        ];
        $inserted = $mentorGroupPersonGateway->insertAndUpdate($data, $data);
        $partialFail &= !$inserted;
    }

    // Insert one record per student (if any)
    foreach ($students as $gibbonPersonID) {
        $data = [
            'freeLearningMentorGroupID' => $freeLearningMentorGroupID,
            'gibbonPersonID' => $gibbonPersonID,
            'role' => 'Student',
        ];
        $inserted = $mentorGroupPersonGateway->insertAndUpdate($data, $data);
        $partialFail &= !$inserted;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$freeLearningMentorGroupID";

    header("Location: {$URL}");
}
