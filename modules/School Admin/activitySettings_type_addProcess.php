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

use Gibbon\Domain\Activities\ActivityTypeGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/activitySettings_type_add.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings_type_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $data = [
        'name' =>  $_POST['name'] ?? '',
        'description' =>  $_POST['description'] ?? '',
        'access' =>  $_POST['access'] ?? '',
        'enrolmentType' =>  $_POST['enrolmentType'] ?? '',
        'maxPerStudent' =>  $_POST['maxPerStudent'] ?? 0,
        'waitingList' =>  $_POST['waitingList'] ?? 'Y',
        'backupChoice' =>  $_POST['backupChoice'] ?? 'Y',
    ];

    $activityTypeGateway = $container->get(ActivityTypeGateway::class);

    // Validate the required values are present
    if (empty($data['name']) || empty($data['access']) || empty($data['enrolmentType'] || empty($data['backupChoice']))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$activityTypeGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Write to database
    $gibbonActivityTypeID = $activityTypeGateway->insert($data);

    $URL .= $gibbonActivityTypeID
        ? "&return=success0&editID=$gibbonActivityTypeID"
        : "&return=error2";

    header("Location: {$URL}");
}
