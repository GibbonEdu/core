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

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonDepartmentID = $_GET['gibbonDepartmentID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID";

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
                        $resourceName =isset( $_POST["name$i"])? $_POST["name$i"] : '';
                        $resourceType = isset($_POST["type$i"])? $_POST["type$i"] : '';
                        $resourceURL = isset($_POST["url$i"])? $_POST["url$i"] : '';

                        if ($resourceName != '' and $resourceType != '' and ($resourceType == 'File' or $resourceType == 'Link')) {
                            if (($resourceType == 'Link' and $resourceURL != '') or ($resourceType == 'File' and !empty($_FILES['file'.$i]['tmp_name']))) {
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
                                    if (!empty($_FILES['file'.$i]['tmp_name'])) {
                                        $file = (isset($_FILES['file'.$i]))? $_FILES['file'.$i] : null;

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
