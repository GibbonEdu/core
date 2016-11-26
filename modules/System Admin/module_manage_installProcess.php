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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Get URL from calling page, and set returning URL
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/module_manage.php';
$_SESSION[$guid]['moduleInstallError'] = '';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $moduleName = null;
    if (isset($_GET['name'])) {
        $moduleName = $_GET['name'];
    }

    if ($moduleName == null or $moduleName == '') {
        $URL .= '&return=error5';
        header("Location: {$URL}");
    } else {
        if (!(include $_SESSION[$guid]['absolutePath']."/modules/$moduleName/manifest.php")) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            if ($name == '' or $description == '' or $type == '' or $type != 'Additional' or $version == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Lock module table
                try {
                    $sql = 'LOCK TABLES gibbonModule WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Check for existence of module
                try {
                    $dataModule = array('name' => $name);
                    $sqlModule = 'SELECT * FROM gibbonModule WHERE name=:name';
                    $resultModule = $connection2->prepare($sqlModule);
                    $resultModule->execute($dataModule);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($resultModule->rowCount() > 0) {
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    //Insert new module row
                    try {
                        $dataModule = array('name' => $name, 'description' => $description, 'entryURL' => $entryURL, 'type' => $type, 'category' => $category, 'version' => $version, 'author' => $author, 'url' => $url);
                        $sqlModule = "INSERT INTO gibbonModule SET name=:name, description=:description, entryURL=:entryURL, type=:type, category=:category, active='N', version=:version, author=:author, url=:url";
                        $resultModule = $connection2->prepare($sqlModule);
                        $resultModule->execute($dataModule);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $gibbonModuleID = $connection2->lastInsertID();

                    //Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Create module tables
                    //Whilst this area is intended for use setting up module tables, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
                    $partialFail = false;
                    if (isset($moduleTables)) {
                        for ($i = 0;$i < count($moduleTables);++$i) {
                            try {
                                $sql = $moduleTables[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                $partialFail = true;
                            }
                        }
                    }
                    //Create gibbonSetting entries
                    //Whilst this area is intended for use setting up gibbonSetting entries, arbitrary sql can be run at the wish of the module developer. However, such actions are not cleaned up by the uninstaller.
                    $partialFail = false;
                    if (isset($gibbonSetting)) {
                        for ($i = 0;$i < count($gibbonSetting);++$i) {
                            try {
                                $sql = $gibbonSetting[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                $partialFail = true;
                            }
                        }
                    }
                    //Create module actions
                    if (is_null(@$actionRows) == false) {
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

                            try {
                                $dataModule = array('gibbonModuleID' => $gibbonModuleID, 'name' => $actionRows[$i]['name'], 'precedence' => $actionRows[$i]['precedence'], 'category' => $actionRows[$i]['category'], 'description' => $actionRows[$i]['description'], 'URLList' => $actionRows[$i]['URLList'], 'entryURL' => $actionRows[$i]['entryURL'], 'entrySidebar' => $entrySidebar, 'menuShow' => $menuShow, 'defaultPermissionAdmin' => $actionRows[$i]['defaultPermissionAdmin'], 'defaultPermissionTeacher' => $actionRows[$i]['defaultPermissionTeacher'], 'defaultPermissionStudent' => $actionRows[$i]['defaultPermissionStudent'], 'defaultPermissionParent' => $actionRows[$i]['defaultPermissionParent'], 'defaultPermissionSupport' => $actionRows[$i]['defaultPermissionSupport'], 'categoryPermissionStaff' => $categoryPermissionStaff, 'categoryPermissionStudent' => $categoryPermissionStudent, 'categoryPermissionParent' => $categoryPermissionParent, 'categoryPermissionOther' => $categoryPermissionOther);
                                $sqlModule = 'INSERT INTO gibbonAction SET gibbonModuleID=:gibbonModuleID, name=:name, precedence=:precedence, category=:category, description=:description, URLList=:URLList, entryURL=:entryURL, entrySidebar=:entrySidebar, menuShow=:menuShow, defaultPermissionAdmin=:defaultPermissionAdmin, defaultPermissionTeacher=:defaultPermissionTeacher, defaultPermissionStudent=:defaultPermissionStudent, defaultPermissionParent=:defaultPermissionParent, defaultPermissionSupport=:defaultPermissionSupport, categoryPermissionStaff=:categoryPermissionStaff, categoryPermissionStudent=:categoryPermissionStudent, categoryPermissionParent=:categoryPermissionParent, categoryPermissionOther=:categoryPermissionOther';
                                $resultModule = $connection2->prepare($sqlModule);
                                $resultModule->execute($dataModule);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlModule.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                    }

                    try {
                        $dataActions = array('gibbonModuleID' => $gibbonModuleID);
                        $sqlActions = 'SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID';
                        $resultActions = $connection2->prepare($sqlActions);
                        $resultActions->execute($dataActions);
                    } catch (PDOException $e) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                        exit();
                    }

                    while ($rowActions = $resultActions->fetch()) {
                        if ($rowActions['defaultPermissionAdmin'] == 'Y') {
                            try {
                                $dataPermissions = array('gibbonActionID' => $rowActions['gibbonActionID']);
                                $sqlPermissions = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=001';
                                $resultPermissions = $connection2->prepare($sqlPermissions);
                                $resultPermissions->execute($dataPermissions);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlPermissions.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                        if ($rowActions['defaultPermissionTeacher'] == 'Y') {
                            try {
                                $dataPermissions = array('gibbonActionID' => $rowActions['gibbonActionID']);
                                $sqlPermissions = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=002';
                                $resultPermissions = $connection2->prepare($sqlPermissions);
                                $resultPermissions->execute($dataPermissions);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlPermissions.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                        if ($rowActions['defaultPermissionStudent'] == 'Y') {
                            try {
                                $dataPermissions = array('gibbonActionID' => $rowActions['gibbonActionID']);
                                $sqlPermissions = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=003';
                                $resultPermissions = $connection2->prepare($sqlPermissions);
                                $resultPermissions->execute($dataPermissions);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlPermissions.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                        if ($rowActions['defaultPermissionParent'] == 'Y') {
                            try {
                                $dataPermissions = array('gibbonActionID' => $rowActions['gibbonActionID']);
                                $sqlPermissions = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=004';
                                $resultPermissions = $connection2->prepare($sqlPermissions);
                                $resultPermissions->execute($dataPermissions);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlPermissions.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                        if ($rowActions['defaultPermissionSupport'] == 'Y') {
                            try {
                                $dataPermissions = array('gibbonActionID' => $rowActions['gibbonActionID']);
                                $sqlPermissions = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=006';
                                $resultPermissions = $connection2->prepare($sqlPermissions);
                                $resultPermissions->execute($dataPermissions);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= $sqlPermissions.'<br/><b>'.$e->getMessage().'</b></br><br/>';
                                $partialFail = true;
                            }
                        }
                    }

                    //Create hook entries
                    if (isset($hooks)) {
                        for ($i = 0;$i < count($hooks);++$i) {
                            try {
                                $sql = $hooks[$i];
                                $result = $connection2->query($sql);
                            } catch (PDOException $e) {
                                $_SESSION[$guid]['moduleInstallError'] .= htmlPrep($sql).'<br/><b>'.$e->getMessage().'</b><br/><br/>';
                                $partialFail = true;
                            }
                        }
                    }

                    //The reckoning!
                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        //Set module to active
                        try {
                            $data = array('gibbonModuleID' => $gibbonModuleID);
                            $sql = "UPDATE gibbonModule SET active='Y' WHERE gibbonModuleID=:gibbonModuleID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=warning2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Update main menu
                        $mainMenu = new Gibbon\MenuMain($gibbon, $pdo);
                        $mainMenu->setMenu();

                        //We made it!
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
