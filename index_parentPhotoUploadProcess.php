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

//Gibbon system-wide includes

use Gibbon\Http\Url;

include './gibbon.php';

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$URL = Url::fromRoute();

//Proceed!
//Check if planner specified
if ($gibbonPersonID == '' or $gibbonPersonID != $session->get('gibbonPersonID') or $_FILES['file1']['tmp_name'] == '') {
    header("Location: {$URL->withReturn('error1')}");
    exit();
} else {
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        header("Location: {$URL->withReturn('error2')}");
        exit();
    }

    if ($result->rowCount() != 1) {
        header("Location: {$URL->withReturn('error2')}");
        exit();
    } else {
        $attachment1 = null;
        if (!empty($_FILES['file1']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $session);
            $fileUploader->setFileSuffixType(Gibbon\FileUploader::FILE_SUFFIX_INCREMENTAL);

            $file = $_FILES['file1'] ?? null;

            // Upload the file, return the /uploads relative path
            $attachment1 = $fileUploader->uploadFromPost($file, $session->get('username').'_240');

            if (empty($attachment1)) {
                header("Location: {$URL->withReturn('warning1')}");
                exit();
            }
        }

        $path = $session->get('absolutePath');

        //Check for reasonable image
        $size = getimagesize($path.'/'.$attachment1);
        $width = $size[0];
        $height = $size[1];
        if ($width < 240 or $height < 320) {
            header("Location: {$URL->withReturn('error6')}");
            exit();
        } elseif ($width > 480 or $height > 640) {
            header("Location: {$URL->withReturn('error6')}");
            exit();
        } elseif (($width / $height) < 0.60 or ($width / $height) > 0.8) {
            header("Location: {$URL->withReturn('error6')}");
            exit();
        } else {
            //UPDATE
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'attachment1' => $attachment1);
                $sql = 'UPDATE gibbonPerson SET image_240=:attachment1 WHERE gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header("Location: {$URL->withReturn('error2')}");
                exit();
            }

            //Update session variables
            $session->set('image_240', $attachment1);

            //Clear cusotm sidebar
            $session->remove('index_customSidebar.php');

            header("Location: {$URL->withReturn('success0')}");
        }
    }
}
