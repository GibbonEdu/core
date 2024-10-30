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

use Gibbon\Services\Format;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DataRetentionGateway;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$logGateway = $container->get(LogGateway::class);
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/dataRetention.php';

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

    $partialFail = false;
    $scrubbedList = [];

    // Validate each of the domains is scrubbable before proceeding
    foreach ($selectedDomains as $domain) {
        foreach ($domain['gateways'] as $gatewayClass) {
            $gateway = $container->get($gatewayClass);
            if (!$gateway instanceof ScrubbableGateway) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            }
        }
    }

    // Cycle through each selected domain
    foreach ($selectedDomains as $domain) {
        if (empty($domain['gateways'])) continue;

        // Cycle through each gateway and scrub data
        foreach ($domain['gateways'] as $gatewayClass) {
            $gateway = $container->get($gatewayClass);

            $scrubbed = $gateway->scrub($data['date'], $domain['context'] ?? []);
            $scrubbedList = array_merge_recursive($scrubbedList, $scrubbed);
            $partialFail &= !empty($scrubbed);
        }
    }

    // Store a record of which users and which tables were scrubbed
    foreach ($scrubbedList as $gibbonPersonID => $tables) {
        if (empty($gibbonPersonID)) continue;
        $successfulTables = array_filter($tables);

        $data = [
            'gibbonPersonID'            => $gibbonPersonID,
            'tables'                    => json_encode(array_keys($tables)),
            'status'                    => count($successfulTables) ==  count($tables)? 'Success' : 'Partial Failure',
            'gibbonPersonIDOperator'    => $session->get('gibbonPersonID'),
        ];

        // Update existing records to merge the list of scrubbed tables
        $existing = $dataRetentionGateway->selectBy(['gibbonPersonID' => $gibbonPersonID], ['gibbonDataRetentionID', 'tables'])->fetch();
        if (!empty($existing)) {
            $updatedTables = array_merge(array_keys($tables), json_decode($existing['tables']));
            $data['tables'] = json_encode(array_values(array_unique($updatedTables)));
            $dataRetentionGateway->update($existing['gibbonDataRetentionID'], $data);
        } else {
            $dataRetentionGateway->insert($data);
        }
    }

    // Write to log
    $logGateway->addLog($session->get('gibbonSchoolYearID'), getModuleID($connection2, $_POST["address"]), $session->get('gibbonPersonID'), 'Data Retention', array('Status' => (!$partialFail) ? "Success" : "Partial Failure", 'Count' => count($scrubbedList, COUNT_RECURSIVE)));

    $URL .= $partialFail
        ?'&return=warning2'
        : '&return=success0&count='.count($scrubbedList, COUNT_RECURSIVE);
    header("Location: {$URL}");
    
}
