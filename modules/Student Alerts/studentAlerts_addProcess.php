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
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

include '../../gibbon.php';
include './moduleFunctions.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Student Alerts/studentAlerts_add.php';

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_add.php')) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $partialFail = false;
    $alertGateway = $container->get(AlertGateway::class);
    $alertLevelGateway = $container->get(AlertLevelGateway::class);
    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID') ?? '',
        'gibbonPersonID'        => $_POST['gibbonPersonID'] ?? '',
        'type'                  => $_POST['type'] ?? '',
        'level'                 => $_POST['level'] ?? null,
        'comment'               => $_POST['comment'] ?? '',
        'status'                => 'Pending',
        'context'               => 'Manual',
        'dateStart'             => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'dateEnd'               => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID') ?? '',
    ];

    $highestAction = getHighestGroupedAction($guid, '/modules/Student Alerts/studentAlerts_add.php', $connection2);
    if ($highestAction == 'Manage Student Alerts_all' || $highestAction == 'Manage Student Alerts_headOfYear') {
        $data['status'] =  $_POST['status'] ?? 'Pending';
    }

    $alertType = $alertTypeGateway->selectBy(['name' => $data['type']])->fetch();

    if (empty($alertType) || empty($data['type']) || empty($data['gibbonPersonID'])) {
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

    $gibbonAlertID = $alertGateway->insert($data);

    if (empty($gibbonAlertID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

     $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonAlertID";

    header("Location: {$URL}");     
}
