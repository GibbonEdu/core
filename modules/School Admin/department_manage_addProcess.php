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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/department_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    //Proceed!
    $type = $_POST['type'];
    $name = $_POST['name'];
    $nameShort = $_POST['nameShort'];
    $subjectListing = $_POST['subjectListing'];
    $blurb = $_POST['blurb'];
    
    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
    $fileUploader->getFileExtensions();

    //Lock table
    try {
        $sql = 'LOCK TABLES gibbonDepartment WRITE, gibbonDepartmentStaff WRITE';
        $result = $connection2->query($sql);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    //Get next autoincrement
    try {
        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonDepartment'";
        $resultAI = $connection2->query($sqlAI);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $rowAI = $resultAI->fetch();
    $AI = str_pad($rowAI['Auto_increment'], 4, '0', STR_PAD_LEFT);

    if ($type == '' or $name == '' or $nameShort == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit();
    } else {
        $partialFail = false;
        
        //Move attached file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $attachment = $fileUploader->uploadFromPost($file, $name);

            if (empty($attachment)) {
                $partialFail = true;
            }
        } else {
            $attachment = '';
        }

        //Scan through staff
        $staff = array();
        if (isset($_POST['staff'])) {
            $staff = $_POST['staff'];
        }
        if ($type == 'Learning Area') {
            $role = $_POST['roleLA'];
        } elseif ($type == 'Administration') {
            $role = $_POST['roleAdmin'];
        }
        if ($role == '') {
            $role = 'Other';
        }
        if (count($staff) > 0) {
            foreach ($staff as $t) {
                //Check to see if person is already registered in this activity
                try {
                    $dataGuest = array('gibbonPersonID' => $t, 'gibbonDepartmentID' => $AI);
                    $sqlGuest = 'SELECT * FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonDepartmentID=:gibbonDepartmentID';
                    $resultGuest = $connection2->prepare($sqlGuest);
                    $resultGuest->execute($dataGuest);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultGuest->rowCount() == 0) {
                    try {
                        $data = array('gibbonPersonID' => $t, 'gibbonDepartmentID' => $AI, 'role' => $role);
                        $sql = 'INSERT INTO gibbonDepartmentStaff SET gibbonPersonID=:gibbonPersonID, gibbonDepartmentID=:gibbonDepartmentID, role=:role';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }

        //Write to database
        try {
            $data = array('gibbonDepartmentID' => $AI, 'type' => $type, 'name' => $name, 'nameShort' => $nameShort, 'subjectListing' => $subjectListing, 'blurb' => $blurb, 'logo' => $attachment);
            $sql = 'INSERT INTO gibbonDepartment SET gibbonDepartmentID=:gibbonDepartmentID, type=:type, name=:name, nameShort=:nameShort, subjectListing=:subjectListing, blurb=:blurb, logo=:logo';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        try {
            $sql = 'UNLOCK TABLES';
            $result = $connection2->query($sql);
        } catch (PDOException $e) {
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
