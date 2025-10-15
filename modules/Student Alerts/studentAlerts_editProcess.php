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

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

$gibbonAlertID = $_POST['gibbonAlertID'] ?? '';

$URL = Url::fromModuleRoute('Student Alerts', 'studentAlerts_edit')->withQueryParams(['gibbonAlertID' => $gibbonAlertID]);

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_edit.php')) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $alertGateway = $container->get(AlertGateway::class);
    $alertLevelGateway = $container->get(AlertLevelGateway::class);
    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    $action = Access::get('Student Alerts', 'studentAlerts_edit');
    $canEditAlert = $action->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear') || $alertGateway->getAlertEditAccess($gibbonAlertID, $session->get('gibbonPersonID'));
    
    if (!$canEditAlert) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'status'    => $_POST['status'] ?? 'Pending',
        'level'     => $_POST['level'] ?? '',
        'dateStart' => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'dateEnd'   => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'comment'   => $_POST['comment'] ?? '',
    ];

    // Validate the required values are present
    $values = $alertGateway->getByID($gibbonAlertID);
    if (empty($gibbonAlertID) || empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $alertType = $alertTypeGateway->getByID($values['gibbonAlertTypeID']);
    if (empty($alertType)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Ensure levels are turned off if not in use
    $data['gibbonAlertTypeID'] = $alertType['gibbonAlertTypeID'];
    if ($alertType['useLevels'] == 'N') {
        $data['gibbonAlertLevelID'] = null;
        $data['level'] = null;
    }

    // Set level ID based on level selected
    if (!empty($data['level'])) {
        if ($alertLevel = $alertLevelGateway->selectBy(['name' => $data['level']])->fetch()) {
            $data['gibbonAlertLevelID'] = $alertLevel['gibbonAlertLevelID'];
        }
    }

    $updated = $alertGateway->update($gibbonAlertID, $data);
    $partialFail = !$updated;

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
