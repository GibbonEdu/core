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

use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonModuleID = $_GET['gibbonModuleID'] ?? '';  
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/module_manage_update.php&gibbonModuleID='.$gibbonModuleID;
$session->set('moduleUpdateError', '');


if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Check if module specified
    if (empty($gibbonModuleID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $moduleGateway = $container->get(ModuleGateway::class);
        $module = $moduleGateway->getByID($gibbonModuleID);

        if (empty($module)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            // Inputs
            $versionDB = $_POST['versionDB'] ?? '';
            $versionCode = $_POST['versionCode'] ?? '';

            // Validate Inputs
            if (empty($versionDB) or empty($versionCode) or version_compare($versionDB, $versionCode) != -1) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                include $session->get('absolutePath').'/modules/'.$module['name'].'/CHANGEDB.php';

                $partialFail = false;
                foreach ($sql as $version) {
                    if (version_compare($version[0], $versionDB, '>') and version_compare($version[0], $versionCode, '<=')) {
                        $sqlTokens = explode(';end', $version[1]);
                        foreach ($sqlTokens as $sqlToken) {
                            if (trim($sqlToken) != '') {
                                try {
                                    $result = $connection2->query($sqlToken);
                                } catch (PDOException $e) {
                                    $session->set('moduleUpdateError', $session->get('moduleUpdateError').htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/><br/>');
                                    $partialFail = true;
                                }
                            }
                        }
                    }
                }

                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    // Update DB version
                    $moduleGateway->update($module['gibbonModuleID'], ['version' => $versionCode]);

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
