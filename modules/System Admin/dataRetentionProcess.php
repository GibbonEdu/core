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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DataRetentionGateway;

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/dataRetention.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/dataRetention.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $data = [
        'category' => $_POST['category'] ?? null,
        'date' => (!empty($_POST['date'])) ? Format::dateConvert($_POST['date']) : null,
        'tables' => $_POST['tables'] ?? []
    ];

    // Validate Inputs
    if ($data['category'] == '' OR $data['date'] == '' OR count($data['tables']) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        // Ensure table selection persists
        $container->get(SettingGateway::class)->updateSettingByScope('System', 'dataRetentionTables', implode(",", $data['tables']));

        // Prepare data retention gateway
        $dataRetentionGateway = $container->get(DataRetentionGateway::class);

        // Locate Users
        $userGateway = $container->get(UserGateway::class);
        $users = $userGateway->selectUserNamesByStatus(['Left'], $data['category'])->fetchAll();

        // Cycle through users
        $partialFail = false;

        $processCount = 0;
        foreach ($users as $user) {
            //Check dateEnd and last login
            $proceed = false;
            if (!empty($user['dateEnd'])) {
                $proceed = ($user['dateEnd'] < $data['date']);
            } else if (!empty($user['lastTimestamp'])) {
                $proceed = (substr($user['lastTimestamp'], 0, 10) < $data['date']);
            } else {
                $proceed = true;
            }

            if ($proceed) {
                if (!$dataRetentionGateway->runUserScrub($gibbon, $connection2, $user['gibbonPersonID'], $data['tables'])){
                    $partialFail = true;
                }
                $processCount ++;
            }
        }

        //Write to log
        setLog($connection2, $gibbon->session->get('gibbonSchoolYearID'), getModuleID($connection2, $_POST["address"]), $gibbon->session->get('gibbonPersonID'), 'Data Retention', array('Status' => (!$partialFail) ? "Success" : "Partial Failure", 'Count' => $processCount));
        //Return
        if ($partialFail == true) {
           $URL .= '&return=warning1';
           header("Location: {$URL}");
       } else {
           $URL .= '&return=success0';
           header("Location: {$URL}");
       }
    }
}
