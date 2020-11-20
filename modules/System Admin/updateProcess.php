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

use Gibbon\Database\Updater;
use Gibbon\Database\Migrations\EngineUpdate;

include '../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/update.php';
$partialFail = false;
$_SESSION[$guid]['systemUpdateError'] = '';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $type = $_POST['type'] ?? '';

    // Validate Inputs
    if ($type != 'regularRelease' && $type != 'cuttingEdge' && $type != 'InnoDB') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    $updater = $container->get(Updater::class);

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

    if ($type == 'regularRelease' || $type == 'cuttingEdge') {
        // Do the update
        $errors = $updater->update();

        if (!empty($errors)) {
            $gibbon->session->set('systemUpdateError', $errors);

            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            // Update DB version for existing languages
            i18nCheckAndUpdateVersion($container, $updater->versionDB);

            // Clear the templates cache folder
            removeDirectoryContents($gibbon->session->get('absolutePath').'/uploads/cache');

            // Clear the var/log folder
            removeDirectoryContents($gibbon->session->get('absolutePath').'/var', true);

            // Reset cache to force top-menu reload
            $gibbon->session->forget('pageLoads');

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
