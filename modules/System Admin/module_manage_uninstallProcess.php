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
use Gibbon\Domain\System\ActionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$orphaned = $_GET['orphaned'] ?? '';

$gibbonModuleID = $_GET['gibbonModuleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/module_manage_uninstall.php&gibbonModuleID='.$gibbonModuleID;
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/module_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_uninstall.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Check if role specified
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
            $moduleName = $module['name'];
            $partialFail = false;

            // Check for tables and views to remove, and remove them
            $tables = null;
            if (isset($_POST['remove'])) {
                $tables = $_POST['remove'] ?? [];
            }
            if (is_array($tables)) {
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        $type = null;
                        $name = null;
                        if (substr($table, 0, 5) == 'Table') {
                            $type = 'TABLE';
                            $name = substr($table, 6);
                        } elseif (substr($table, 0, 4) == 'View') {
                            $type = 'VIEW';
                            $name = substr($table, 5);
                        }
                        if ($type != null and $name != null) {
                            $sqlDelete = "DROP $type $name";
                            $partialFail &= !$pdo->statement($sqlDelete);
                        }
                    }
                }
            }

            // Get actions to remove permissions
            $actionGateway = $container->get(ActionGateway::class);
            $actions = $actionGateway->selectBy(['gibbonModuleID' => $module['gibbonModuleID']]);

            foreach ($actions as $action) {
                // Remove permissions
                $actionGateway->deletePermissionByAction($action['gibbonActionID']);
            }

            // Remove actions
            $actionGateway->deleteWhere(['gibbonModuleID' => $module['gibbonModuleID']]);

            // Remove module
            $moduleGateway->delete($module['gibbonModuleID']);

            // Remove hooks
            $hookGateway = $container->get(HookGateway::class);
            $hookGateway->deleteWhere(['gibbonModuleID' => $module['gibbonModuleID']]);

            // Remove settings
            $settingGateway = $container->get(SettingGateway::class);
            $settingGateway->deleteWhere(['scope' => $moduleName]);

            // Remove notification events
            $notificationGateway = $container->get(NotificationGateway::class);
            $notificationGateway->deleteCascadeNotificationByModuleName($moduleName);

            // Clear the main menu from session cache
            $session->forget('menuMainItems');

            $URLDelete .= $orphaned != 'true'
                ? '&return=warning0'
                : '&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
