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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Data\Validator;
use Gibbon\Domain\StudentAlerts\AlertGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonAlertID = $_POST['gibbonAlertID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Student Alerts/studentAlerts_manage_approval.php&gibbonAlertID='.$gibbonAlertID;
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Student Alerts/studentAlerts_manage.php';

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_manage_approval.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
}  else {
    // Proceed!
    if (empty($gibbonAlertID) || empty($status)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $alertGateway = $container->get(AlertGateway::class);
    $alert = $alertGateway->getByID($gibbonAlertID);

    if (empty($alert)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($alert['status'] != 'Pending') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'status'            => $status,
        'timestampApproval' => date('Y-m-d H:i:s'),
        'notesApproval'     => $_POST['notesApproval'],
        'gibbonPersonIDApproval' => $session->get('gibbonPersonID') ?? '',
    ];

    $updated = $alertGateway->update($gibbonAlertID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $URLSuccess .= '&return=success0';
    header("Location: {$URLSuccess}");
}




