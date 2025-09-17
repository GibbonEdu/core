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
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/alertType_add.php';

if (!isActionAccessible($guid, $connection2, '/modules/School Admin/alertLevelSettings.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $alertTypeGateway = $container->get(AlertTypeGateway::class);
    
    $data = [
        'name'                   => $_POST['name'] ?? '',
        'tag'                    => $_POST['tag'] ?? '',
        'useLevels'              => $_POST['useLevels'] ?? '',
        'color'                  => $_POST['color'] ?? null,
        'colorBG'                => $_POST['colorBG'] ?? null,
        'description'            => $_POST['description'] ?? '',
        'gibbonPersonIDCreator'  => $session->get('gibbonPersonID') ?? '',
        'sequenceNumber'         => $alertTypeGateway->getNextSequenceNumber(),
    ];

    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$alertTypeGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonAlertTypeID = $alertTypeGateway->insert($data);

    if (empty($gibbonAlertTypeID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $URL .= "&return=success0&editID=$gibbonAlertTypeID";
    header("Location: {$URL}");
}
