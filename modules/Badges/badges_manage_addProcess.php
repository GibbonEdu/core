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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../gibbon.php';

include './moduleFunctions.php';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$URL = $gibbon->session->get('absoluteURL','').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/badges_manage_add.php&search='. $search .'&category='.$category ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_manage_add.php') == false) {
    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $name = $_POST['name'];
    $active = $_POST['active'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $logoLicense = $_POST['logoLicense'];

    if ($name == '' or $active == '' or $category == '') {
        //Fail 3
        $URL = $URL.'&return=error3';
        header("Location: {$URL}");
    } else {
        $partialFail = false;
        $logo = null;

        //Move attached image  file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
            $fileUploader->getFileExtensions('Graphics/Design');

            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $logo = $fileUploader->uploadFromPost($file, $name);

            if (empty($logo)) {
                $partialFail = true;
            }
        }

        //Write to database
        try {
            $data = array('name' => $name, 'active' => $active, 'category' => $category, 'description' => $description, 'logo' => $logo, 'logoLicense' => $logoLicense, 'gibbonPersonIDCreator' => $gibbon->session->get('gibbonPersonID'), 'timestampCreated' => date('Y-m-d H:i:s'));
            $sql = 'INSERT INTO badgesBadge SET name=:name, active=:active, category=:category, description=:description, logo=:logo, logoLicense=:logoLicense, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreated=:timestampCreated';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit;
            //Fail 2
            $URL = $URL.'&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
