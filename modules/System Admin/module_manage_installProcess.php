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

use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\System\ActionGateway;

include '../../gibbon.php';

//Get URL from calling page, and set returning URL
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/System Admin/module_manage.php';
$gibbon->session->set('moduleInstallError', '');

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $moduleName = $_GET['name'] ?? '';

    if (empty($moduleName)) {
        $URL .= '&return=error5';
        header("Location: {$URL}");
    } else {
        if (!(include $gibbon->session->get('absolutePath')."/modules/$moduleName/manifest.php")) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            // Validate manifest
            if (empty($name) or empty($description) or empty($type) or $type != 'Additional' or empty($version)) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $moduleGateway = $container->get(ModuleGateway::class);
                
                // Lock module table
                try {
                    $sql = 'LOCK TABLES gibbonModule WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                // Check for existence of module
                $module = $moduleGateway->selectBy(['name' => $name])->fetch();

                if (!empty($module)) {
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    // Insert new module row
                    $dataModule = ['name' => $name, 'description' => $description, 'entryURL' => $entryURL, 'type' => $type, 'category' => $category, 'version' => $version, 'author' => $author, 'url' => $url];
                    $gibbonModuleID = $moduleGateway->insertAndUpdate($dataModule, $dataModule);

                    // Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                        exit();
                    }

                    // Create module tables
                    // Whilst this area is intended for use setting up module tables, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
                    $partialFail = false;
                    if (isset($moduleTables)) {
                        for ($i = 0;$i < count($moduleTables);++$i) {
                            try {
                                $sql = $moduleTables[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $gibbon->session->set('moduleInstallError', $gibbon->session->get('moduleInstallError').htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>');
                                $partialFail = true;
                            }
                        }
                    }
                    // Create gibbonSetting entries
                    // Whilst this area is intended for use setting up gibbonSetting entries, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
                    $partialFail = false;
                    if (isset($gibbonSetting)) {
                        for ($i = 0;$i < count($gibbonSetting);++$i) {
                            try {
                                $sql = $gibbonSetting[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $gibbon->session->set('moduleInstallError', "Y".$gibbon->session->get('moduleInstallError').htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>');
                                $partialFail = true;
                            }
                        }
                    }

                    $actionGateway = $container->get(ActionGateway::class);

                    // Create module actions
                    if (!empty($actionRows)) {
  
                        for ($i = 0;$i < count($actionRows);++$i) {
                            $categoryPermissionStaff = 'Y';
                            $categoryPermissionStudent = 'Y';
                            $categoryPermissionParent = 'Y';
                            $categoryPermissionOther = 'Y';
                            if (isset($actionRows[$i]['categoryPermissionStaff'])) {
                                if ($actionRows[$i]['categoryPermissionStaff'] == 'N') {
                                    $categoryPermissionStaff = 'N';
                                }
                            }
                            if (isset($actionRows[$i]['categoryPermissionStudent'])) {
                                if ($actionRows[$i]['categoryPermissionStudent'] == 'N') {
                                    $categoryPermissionStudent = 'N';
                                }
                            }
                            if (isset($actionRows[$i]['categoryPermissionParent'])) {
                                if ($actionRows[$i]['categoryPermissionParent'] == 'N') {
                                    $categoryPermissionParent = 'N';
                                }
                            }
                            if (isset($actionRows[$i]['categoryPermissionOther'])) {
                                if ($actionRows[$i]['categoryPermissionOther'] == 'N') {
                                    $categoryPermissionOther = 'N';
                                }
                            }
                            $entrySidebar = 'Y';
                            if (isset($actionRows[$i]['entrySidebar'])) {
                                if ($actionRows[$i]['entrySidebar'] == 'N') {
                                    $entrySidebar = 'N';
                                }
                            }
                            $menuShow = 'Y';
                            if (isset($actionRows[$i]['menuShow'])) {
                                if ($actionRows[$i]['menuShow'] == 'N') {
                                    $menuShow = 'N';
                                }
                            }

                            $dataModule = ['gibbonModuleID' => $gibbonModuleID, 'name' => $actionRows[$i]['name'], 'precedence' => $actionRows[$i]['precedence'], 'category' => $actionRows[$i]['category'], 'description' => $actionRows[$i]['description'], 'URLList' => $actionRows[$i]['URLList'], 'entryURL' => $actionRows[$i]['entryURL'], 'entrySidebar' => $entrySidebar, 'menuShow' => $menuShow, 'defaultPermissionAdmin' => $actionRows[$i]['defaultPermissionAdmin'], 'defaultPermissionTeacher' => $actionRows[$i]['defaultPermissionTeacher'], 'defaultPermissionStudent' => $actionRows[$i]['defaultPermissionStudent'], 'defaultPermissionParent' => $actionRows[$i]['defaultPermissionParent'], 'defaultPermissionSupport' => $actionRows[$i]['defaultPermissionSupport'], 'categoryPermissionStaff' => $categoryPermissionStaff, 'categoryPermissionStudent' => $categoryPermissionStudent, 'categoryPermissionParent' => $categoryPermissionParent, 'categoryPermissionOther' => $categoryPermissionOther];
                            $actionGateway->insert($dataModule);
                            }
                        }

                    $dataActions = $actionGateway->selectBy(['gibbonModuleID' => $gibbonModuleID]);

                    while ($rowActions = $dataActions->fetch()) {
                        if ($rowActions['defaultPermissionAdmin'] == 'Y') {
                            $actionGateway->insertPermissionByAction($rowActions['gibbonActionID'], '001');
                            }
                        if ($rowActions['defaultPermissionTeacher'] == 'Y') {
                            $actionGateway->insertPermissionByAction($rowActions['gibbonActionID'], '002');
                            }
                        if ($rowActions['defaultPermissionStudent'] == 'Y') {
                            $actionGateway->insertPermissionByAction($rowActions['gibbonActionID'], '003');
                            }
                        if ($rowActions['defaultPermissionParent'] == 'Y') {
                            $actionGateway->insertPermissionByAction($rowActions['gibbonActionID'], '004');
                            }
                        if ($rowActions['defaultPermissionSupport'] == 'Y') {
                            $actionGateway->insertPermissionByAction($rowActions['gibbonActionID'], '006');
                            }
                        }

                    // Create hook entries
                    if (isset($hooks)) {
                        for ($i = 0;$i < count($hooks);++$i) {
                            try {
                                $sql = $hooks[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $gibbon->session->set('moduleInstallError', $gibbon->session->get('moduleInstallError').htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>');
                                $partialFail = true;
                            }
                        }
                    }

                    // The reckoning!
                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        // Set module to active
                        $moduleGateway->update($gibbonModuleID, ['active' => 'Y']);

                        // Clear the main menu from session cache
                        $gibbon->session->forget('menuMainItems');

                        // We made it!
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
