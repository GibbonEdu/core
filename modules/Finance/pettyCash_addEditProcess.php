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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Domain\Finance\PettyCashGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'mode'                     => $_POST['mode'] ?? '',
    'gibbonFinancePettyCashID' => $_POST['gibbonFinancePettyCashID'] ?? '',
    'gibbonSchoolYearID'       => $_POST['gibbonSchoolYearID'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Finance/pettyCash_addEdit.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Finance/pettyCash_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $pettyCashGateway = $container->get(PettyCashGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $_POST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'gibbonPersonID'        => $_POST['gibbonPersonID'] ?? '',
        'amount'                => $_POST['amount'] ?? 0,
        'gibbonPersonIDCreated' => $session->get('gibbonPersonID'),
        'timestampCreated'      => date('Y-m-d H:i:s'),
        'actionRequired'        => $_POST['actionRequired'] ?? '',
        'status'                => $_POST['status'] ?? '',
        'reason'                => $_POST['reason'] ?? '',
        'notes'                 => $_POST['notes'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['gibbonSchoolYearID']) || empty($data['gibbonPersonID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($params['mode'] == 'add') {
        // Set the default status
        $data['status'] = $data['actionRequired'] == 'None' ? 'Complete' : 'Pending';

        // Insert the record
        $gibbonFinancePettyCashID = $pettyCashGateway->insert($data);

    } elseif ($params['mode'] == 'edit') {
        // Validate that this record exists
        if (!$pettyCashGateway->exists($params['gibbonFinancePettyCashID'])) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }

        // Update the record
        $gibbonFinancePettyCashID = $params['gibbonFinancePettyCashID'];
        $pettyCashGateway->update($gibbonFinancePettyCashID, $data);
    }

    $URL .= !$gibbonFinancePettyCashID
        ? "&return=error2"
        : "&return=success0&editID=$gibbonFinancePettyCashID";

    header("Location: {$URL}");
}
