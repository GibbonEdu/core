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

include '../../functions.php';
include '../../config.php';

@session_start();

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/update.php';
$partialFail = false;
$_SESSION[$guid]['systemUpdateError'] = '';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $type = $_GET['type'];
    if ($type != 'regularRelease' and $type != 'cuttingEdge') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } elseif ($type == 'regularRelease') { //Do regular release update
        $versionDB = $_POST['versionDB'];
        $versionCode = $_POST['versionCode'];

        //Validate Inputs
        if ($versionDB == '' or $versionCode == '' or version_compare($versionDB, $versionCode) != -1) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            include '../../CHANGEDB.php';

            foreach ($sql as $version) {
                if (version_compare($version[0], $versionDB, '>') and version_compare($version[0], $versionCode, '<=')) {
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
                //Update DB version
                try {
                    $data = array('value' => $versionCode);
                    $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    } elseif ($type == 'cuttingEdge') { //Do cutting edge update
        $versionDB = $_POST['versionDB'];
        $versionCode = $_POST['versionCode'];
        $cuttingEdgeCodeLine = getSettingByScope($connection2, 'System', 'cuttingEdgeCodeLine');

        include '../../CHANGEDB.php';
        $versionMax = $sql[(count($sql))][0];
        $sqlTokens = explode(';end', $sql[(count($sql))][1]);
        $versionMaxLinesMax = (count($sqlTokens) - 1);
        $update = false;
        if (version_compare($versionMax, $versionDB, '>')) {
            $update = true;
        } else {
            if (version_compare($versionMaxLinesMax, $cuttingEdgeCodeLine, '>')) {
                $update = true;
            }
        }

        if ($update == false) { //Something went wrong...abandon!
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        } else { //Let's do it
            if (version_compare($versionMax, $versionDB, '>')) { //At least one whole verison needs to be done
                foreach ($sql as $version) {
                    $tokenCount = 0;
                    if (version_compare($version[0], $versionDB, '>=') and version_compare($version[0], $versionCode, '<=')) {
                        $sqlTokens = explode(';end', $version[1]);
                        if ($version[0] == $versionDB) { //Finish current version
                            foreach ($sqlTokens as $sqlToken) {
                                if (version_compare($tokenCount[0], $cuttingEdgeCodeLine, '>=')) {
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
                    if (version_compare($version[0], $versionDB, '>=') and version_compare($version[0], $versionCode, '<=')) {
                        $sqlTokens = explode(';end', $version[1]);
                        foreach ($sqlTokens as $sqlToken) {
                            if (version_compare($tokenCount, $cuttingEdgeCodeLine, '>=')) {
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
                //Update DB version
                try {
                    $data = array('value' => $versionMax);
                    $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Update DB line count
                try {
                    $data = array('value' => $versionMaxLinesMax);
                    $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='cuttingEdgeCodeLine'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Reset cache to force top-menu reload
                $_SESSION[$guid]['pageLoads'] = null;

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
