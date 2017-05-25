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

//Module includes for User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonFamilyID = $_GET['gibbonFamilyID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_family_photos.php&gibbonFamilyID=$gibbonFamilyID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_photos.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFamilyID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        $attachments = (isset($_POST['attachment']))? $_POST['attachment'] : null;
        $photoPath = 'uploads/photosFamily';

        // Upload photos and update image_240 in accounts
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $id => $attachment) {
                if (empty($attachment)) continue; // Skip empty attachments

                // Upload the data URI
                $binary = file_get_contents( 'data://' . substr($attachment, 5) );
                $filename = $id.'.jpg';
                if (file_put_contents( $_SESSION[$guid]['absolutePath'].'/'.$photoPath.'/'.$filename, $binary ) !== false) {

                    // Update the photo link for this family member
                    try {
                        $data = array('image_240' => $photoPath.'/'.$filename, 'username' => $id, 'gibbonFamilyID' => $gibbonFamilyID );
                        $sql = "UPDATE gibbonPerson, gibbonFamilyAdult SET gibbonPerson.image_240=:image_240 WHERE gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.username=:username";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                } else {
                    $partialFail = true;
                }
            }
        }


        $additionalPhotos = (isset($_POST['attachmentAdditional']))? $_POST['attachmentAdditional'] : null;
        $additionalName = (isset($_POST['additionalName']))? $_POST['additionalName'] : null;
        $additionalRelationship = (isset($_POST['additionalRelationship']))? $_POST['additionalRelationship'] : null;

        // Upload photos and create/update gibbonFamilyAdditionalPeople
        if (is_array($additionalName) && count($additionalName) > 0) {
            foreach ($additionalName as $id => $name) {

                if (empty($name)) {
                    // Delete existing details
                    try {
                        $data = array('sequenceNumber' => $id, 'gibbonFamilyID' => $gibbonFamilyID );
                        $sql = "DELETE FROM gibbonFamilyAdditionalPerson WHERE gibbonFamilyID=:gibbonFamilyID AND sequenceNumber=:sequenceNumber";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                } else {
                    $relationship = (isset($additionalRelationship[$id]))? $additionalRelationship[$id] : '';
                    $image_240 = '';

                    if (!empty($additionalPhotos[$id])) {
                        // Upload the data URI
                        $binary = file_get_contents( 'data://' . substr($additionalPhotos[$id], 5) );
                        $filename = $gibbonFamilyID.'-'.$id.'.jpg';
                        if (file_put_contents( $_SESSION[$guid]['absolutePath'].'/'.$photoPath.'/'.$filename, $binary ) === false) {
                            $partialFail = true;
                        }

                        $image_240 = $photoPath.'/'.$filename;
                    }

                    // Update the photo link for this family member
                    try {
                        $data = array('image_240' => $image_240, 'name' => $name, 'relationship' => $relationship, 'sequenceNumber' => $id, 'gibbonFamilyID' => $gibbonFamilyID );
                        $sql = "INSERT INTO gibbonFamilyAdditionalPerson SET gibbonFamilyID=:gibbonFamilyID, sequenceNumber=:sequenceNumber, name=:name, relationship=:relationship, image_240=:image_240, timestamp=CURRENT_TIMESTAMP ON DUPLICATE KEY UPDATE name=:name, relationship=:relationship, image_240=:image_240";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
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
