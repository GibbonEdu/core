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

    $sessionGateway = $container->get(SessionGateway::class);
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

    if ($type == 'regularRelease') { //Do regular release update

        foreach ($sql as $version) {
            if (version_compare($version[0], $updater->versionDB, '>') and version_compare($version[0], $updater->versionCode, '<=')) {
                $sqlTokens = explode(';end', $version[1]);
                foreach ($sqlTokens as $sqlToken) {
                    if (trim($sqlToken) != '') {
                        try {
                            $result = $connection2->query($sqlToken);
                        } catch (PDOException $e) {
                            $partialFail = true;
                            $_SESSION[$guid]['systemUpdateError'] .= htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
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
            $sessionGateway->updateSettingByScope('System', 'version', $updater->versionCode);

            // Update DB version for existing languages
            i18nCheckAndUpdateVersion($container, $updater->versionDB);

            // Clear the templates cache folder
            removeDirectoryContents($_SESSION[$guid]['absolutePath'].'/uploads/cache');

            // Clear the var/log folder
            removeDirectoryContents($_SESSION[$guid]['absolutePath'].'/var/log');

            // Reset cache to force top-menu reload
            $gibbon->session->forget('pageLoads');

            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
        
    } elseif ($type == 'cuttingEdge') { //Do cutting edge update

        
            if (version_compare($updater->cuttingEdgeVersion, $updater->versionDB, '>')) { //At least one whole verison needs to be done
                foreach ($sql as $version) {
                    $tokenCount = 0;
                    if (version_compare($version[0], $updater->versionDB, '>=') and version_compare($version[0], $updater->versionCode, '<=')) {
                        $sqlTokens = explode(';end', $version[1]);
                        if ($version[0] == $updater->versionDB) { //Finish current version
                            foreach ($sqlTokens as $sqlToken) {
                                if (version_compare($tokenCount, $updater->cuttingEdgeCodeLine, '>=')) {
                                    if (trim($sqlToken) != '') { //Decide whether this has been run or not
                                        try {
                                            $result = $connection2->query($sqlToken);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                            $_SESSION[$guid]['systemUpdateError'] .= htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                        }
                                    }
                                }
                                ++$tokenCount;
                            }
                        } else { //Update intermediate versions and max version
                            foreach ($sqlTokens as $sqlToken) {
                                if (trim($sqlToken) != '') { //Decide whether this has been run or not
                                    try {
                                        $result = $connection2->query($sqlToken);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                        $_SESSION[$guid]['systemUpdateError'] .= htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                    }
                                }
                            }
                        }
                    }
                }
            } else { //Less than one whole version
                //Get up to speed in max version
                foreach ($sql as $version) {
                    $tokenCount = 0;
                    if (version_compare($version[0], $updater->versionDB, '>=') and version_compare($version[0], $updater->versionCode, '<=')) {
                        $sqlTokens = explode(';end', $version[1]);
                        foreach ($sqlTokens as $sqlToken) {
                            if (version_compare($tokenCount, $updater->cuttingEdgeCodeLine, '>=')) {
                                if (trim($sqlToken) != '') { //Decide whether this has been run or not
                                    try {
                                        $result = $connection2->query($sqlToken);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                        $_SESSION[$guid]['systemUpdateError'] .= htmlPrep($sqlToken).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                    }
                                }
                            }
                            ++$tokenCount;
                        }
                    }
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                // Update DB version
                $sessionGateway->updateSettingByScope('System', 'version', $updater->cuttingEdgeVersion);
                $sessionGateway->updateSettingByScope('System', 'cuttingEdgeCodeLine', $updater->cuttingEdgeMaxLine);

                // Update DB version for existing languages
                i18nCheckAndUpdateVersion($container, $updater->versionDB);

                // Clear the templates cache folder
                removeDirectoryContents($_SESSION[$guid]['absolutePath'].'/uploads/cache');

                // Clear the var folder and remove it
                removeDirectoryContents($_SESSION[$guid]['absolutePath'].'/var', true);

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
