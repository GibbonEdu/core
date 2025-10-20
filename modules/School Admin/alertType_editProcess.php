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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonAlertTypeID = $_POST['gibbonAlertTypeID'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/School Admin/alertType_edit.php&gibbonAlertTypeID=".$gibbonAlertTypeID;

if (!isActionAccessible($guid, $connection2, '/modules/School Admin/alertLevelSettings.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $data = [
        'description'   => $_POST['description'] ?? '',
        'tag'           => $_POST['tag'] ?? '',
        'color'         => $_POST['color'] ?? null,
        'colorBG'       => $_POST['colorBG'] ?? null,
        'active'        => $_POST['active'] ?? 'Y',
        'adminOnly'     => $_POST['adminOnly'] ?? 'N',
        'automatic'     => $_POST['automatic'] ?? 'N',
        'thresholdLow'  => $_POST['thresholdLow'] ?? null,
        'thresholdMed'  => $_POST['thresholdMed'] ?? null,
        'thresholdHigh' => $_POST['thresholdHigh'] ?? null,
    ];

    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    // Validate the required values are present
    if (empty($data['active'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $values = $alertTypeGateway->getByID($gibbonAlertTypeID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $alertTypeGateway->update($gibbonAlertTypeID, $data);

    $URL .= !$updated ? "&return=error2" : "&return=success0";
    header("Location: {$URL}");
}
