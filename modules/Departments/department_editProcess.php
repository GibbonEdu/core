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

//Module includes
include './moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonDepartmentID = $_GET['gibbonDepartmentID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID";

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        $blurb = $_POST['blurb'];

        if ($gibbonDepartmentID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Check access to specified course
            try {
                $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Get role within learning area
                $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);

                if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)' and $role != 'Director' and $role != 'Manager') {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                } else {
                    //Scan through resources
                    $partialFail = false;
                    for ($i = 1; $i < 4; ++$i) {
                        $resourceName = $_POST["name$i"];
                        $resourceType = null;
                        if (isset($_POST["type$i"])) {
                            $resourceType = $_POST["type$i"];
                        }
                        $resourceURL = $_POST["url$i"];

                        if ($resourceName != '' and $resourceType != '' and ($resourceType == 'File' or $resourceType == 'Link')) {
                            if (($resourceType == 'Link' and $resourceURL != '') or ($resourceType == 'File' and $_FILES['file'.$i]['tmp_name'] != '')) {
                                if ($resourceType == 'Link') {
                                    try {
                                        $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'resourceType' => $resourceType, 'resourceName' => $resourceName, 'resourceURL' => $resourceURL);
                                        $sql = 'INSERT INTO gibbonDepartmentResource SET gibbonDepartmentID=:gibbonDepartmentID, type=:resourceType, name=:resourceName, url=:resourceURL';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                } elseif ($resourceType == 'File') {
                                    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                                    // Handle the attached file, if there is one
                                    if ($_FILES['file'.$i]['tmp_name'] != '') {
                                        $file = $_FILES['file'.$i];

                                        // Upload the file, return the /uploads relative path
                                        $attachment = $fileUploader->uploadFromPost($file, $resourceName);

                                        if (empty($attachment)) {
                                            $URL .= '&return=warning1';
                                            header("Location: {$URL}");
                                            exit();
                                        } else {
                                            try {
                                                $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'resourceType' => $resourceType, 'resourceName' => $resourceName, 'attachment' => $attachment);
                                                $sql = 'INSERT INTO gibbonDepartmentResource SET gibbonDepartmentID=:gibbonDepartmentID, type=:resourceType, name=:resourceName, url=:attachment';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $partialFail = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //Write to database
                    try {
                        $data = array('blurb' => $blurb, 'gibbonDepartmentID' => $gibbonDepartmentID);
                        $sql = 'UPDATE gibbonDepartment SET blurb=:blurb WHERE gibbonDepartmentID=:gibbonDepartmentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
