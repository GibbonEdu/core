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

use Gibbon\Database\Updater;
use Gibbon\Database\Migrations\EngineUpdate;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/update.php';
$partialFail = false;
$session->set('systemUpdateError', '');

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $type = $_REQUEST['type'] ?? '';

    // Validate Inputs
    if ($type != 'regularRelease' && $type != 'cuttingEdge' && $type != 'InnoDB') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    $updater = $container->get(Updater::class);

    if ($type == 'regularRelease' || $type == 'cuttingEdge') {
        if (!$updater->isVersionValid()) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        }
    
        if (!$updater->isUpdateRequired()) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        }

        // Do the update
        $errors = $updater->update();

        if (!empty($errors)) {
            $session->set('systemUpdateError', $errors);

            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            // Update DB version for existing languages
            i18nCheckAndUpdateVersion($container, $updater->versionDB);

            // Clear the templates cache folder
            removeDirectoryContents($session->get('absolutePath').'/uploads/cache');

            // Clear the var/log folder
            removeDirectoryContents($session->get('absolutePath').'/var', true);

            // Reset cache to force top-menu reload
            $session->forget('pageLoads');

            // Insert/update current session record to attach it to this user (prevent logout after update)
            // TODO: This can likely be removed in v24+
            $data = [
                'gibbonSessionID' => session_id(),
                'gibbonPersonID' => $session->get('gibbonPersonID'),
                'sessionStatus' => 'Logged In',
                'timestampModified' => date('Y-m-d H:i:s'),
            ];
            $container->get(SessionGateway::class)->insertAndUpdate($data, $data);

            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
        
    } elseif ($type == 'InnoDB') {
        // Do InnoDB migration work
        $success = $container->get(EngineUpdate::class)->migrate();

        $URL .= !$success
            ? '&return=error2'
            : '&return=success0';
        header("Location: {$URL}");
    }
}
