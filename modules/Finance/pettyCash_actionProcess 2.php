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
    'action'                   => $_POST['action'] ?? '',
    'gibbonFinancePettyCashID' => $_POST['gibbonFinancePettyCashID'] ?? '',
    'gibbonSchoolYearID'       => $_POST['gibbonSchoolYearID'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Finance/pettyCash.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Finance/pettyCash_action.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $pettyCashGateway = $container->get(PettyCashGateway::class);

    $data = [
        'gibbonPersonIDStatus' => $session->get('gibbonPersonID'),
        'notes'                => $_POST['notes'],
    ];

    if (!empty($_POST['statusDate'])) {
        $data['timestampStatus'] = Format::dateConvert($_POST['statusDate']).' '.($_POST['statusTime'] ?? '00:00');
    }

    // Validate the required values are present
    if (empty($params['gibbonFinancePettyCashID']) || empty($data['timestampStatus'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record exists
    $values = $pettyCashGateway->getByID($params['gibbonFinancePettyCashID']);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the status
    if ($values['actionRequired'] == 'Repay') {
        $data['status'] = 'Repaid';
    } elseif ($values['actionRequired'] == 'Refund') {
        $data['status'] = 'Refunded';
    } else {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $gibbonFinancePettyCashID = $params['gibbonFinancePettyCashID'];
    $pettyCashGateway->update($gibbonFinancePettyCashID, $data);
    

    $URL .= !$gibbonFinancePettyCashID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
