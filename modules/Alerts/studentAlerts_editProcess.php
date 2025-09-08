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
use Gibbon\Services\Format;
use Gibbon\Domain\Alerts\AlertGateway;
use Gibbon\Domain\System\AlertLevelGateway;

include '../../gibbon.php';
include './moduleFunctions.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonAlertID = $_POST['gibbonAlertID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Alerts/studentAlerts_edit.php&gibbonAlertID='.$gibbonAlertID;

if (!isActionAccessible($guid, $connection2, '/modules/Alerts/studentAlerts_edit.php')) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $alertGateway = $container->get(AlertGateway::class);
    $alertLevelGateway = $container->get(AlertLevelGateway::class);

    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    $canEditAlert = $alertGateway->getAlertEditAccess($gibbonAlertID, $session->get('gibbonPersonID'));
    
    if ($highestAction != 'Manage Student Alerts_all' && !$canEditAlert) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

     $data = [
        'type'                   => $_POST['type'] ?? '',
        'level'                  => $_POST['level'] ?? '',
        'dateStart'              => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'dateEnd'                => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'comment'                => $_POST['comment'] ?? '',
    ];

    if (!empty($data['level'])) {
        $alertLevel = $alertLevelGateway->selectBy(['name' => $data['level']])->fetch();

        if (!empty($alertLevel)) {
            $data['gibbonAlertLevelID'] = $alertLevel['gibbonAlertLevelID'];
        }
    }

    // Validate the required values are present
    if (empty($gibbonAlertID) || empty($data['type'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$alertGateway->exists($gibbonAlertID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $updated = $alertGateway->update($gibbonAlertID, $data);
    $partialFail = !$updated;

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
