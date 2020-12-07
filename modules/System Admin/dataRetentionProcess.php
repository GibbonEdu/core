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
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DataRetentionGateway;

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/dataRetention.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/dataRetention.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $data = [
        'date' => (!empty($_POST['date'])) ? Format::dateConvert($_POST['date']) : null,
        'domains' => $_POST['domains'] ?? []
    ];

    // Validate Inputs
    if ($data['date'] == '' OR count($data['domains']) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        return;
    } 

    // Ensure table selection persists
    $container->get(SettingGateway::class)->updateSettingByScope('System', 'dataRetentionDomains', implode(',', $data['domains']));

    // Prepare data retention gateway
    $dataRetentionGateway = $container->get(DataRetentionGateway::class);

    $allDomains = $dataRetentionGateway->getDomains();
    $selectedDomains = array_filter($allDomains, function ($key) use (&$data) {
        return in_array($key, $data['domains']);
    }, ARRAY_FILTER_USE_KEY);


    $gatewayFail = false;
    $partialFail = false;

    // Cycle through each selected domain
    foreach ($selectedDomains as $domain) {
        if (empty($domain['gateways'])) continue;

        // Cycle through each gateway and scrub data
        foreach ($domain['gateways'] as $gatewayClass) {
            $gateway = $container->get($gatewayClass);

            if (!$gateway instanceof ScrubbableGateway) {
                $gatewayFail = true;
            }

            $scrubbed = $gateway->scrub($data['date'], $domain['context'] ?? []);
            $partialFail &= !$scrubbed;
        }
    }

    echo '<pre>';
    print_r($gatewayFail);
    print_r($partialFail);
    echo '</pre>';
    exit;


    // Todo: write the results to a table?

    // Write to log
    setLog($connection2, $gibbon->session->get('gibbonSchoolYearID'), getModuleID($connection2, $_POST["address"]), $gibbon->session->get('gibbonPersonID'), 'Data Retention', array('Status' => (!$partialFail) ? "Success" : "Partial Failure", 'Count' => $processCount));

    // Return
    if ($gatewayFail == true) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
    } elseif ($partialFail == true) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
