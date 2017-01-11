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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
   
    $photoPathStaff = $_SESSION[$guid]['absolutePath'] .'/uploads/photosStaff';
    if (is_dir($photoPathStaff)==FALSE) {
        exit('Missing staff photo folder');
    }


    $files = glob($photoPathStaff.'/*');
    $photos =  preg_grep('/\.jpg$/i', $files);

    if (empty($photos) || !is_array($photos)) {
        exit('No contents in staff photo folder');
    }

    echo "Photos found: " . count($photos) . "\n";

    $countRenamed = 0;

    foreach ($photos as $filename) {

        $photoName = basename($filename);

        $breakPos = stripos($photoName, ',');
        if ($breakPos === false) continue; // Skip if no comma found (already renamed?)

        $surname = substr($photoName, 0, $breakPos);
        $firstname = str_ireplace('.jpg', '', substr($photoName, $breakPos+1));

        echo "Lastname: ".$surname."  Firstname: ".$firstname."\n";

        try {
            $data = array( 'surname' => $surname, 'firstname' => $firstname );
            $sql = "SELECT username, surname, firstName, preferredName FROM gibbonPerson WHERE surname=:surname AND :firstname LIKE CONCAT('%', preferredName, '%') AND (gibbonRoleIDPrimary<>003 AND gibbonRoleIDPrimary<>004)";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            die("Your request failed due to a database error. ".$e->getMessage()."\n");
        }

        if ($result->rowCount() == 1) {
            $row = $result->fetch();

            $newFilename = $photoPathStaff.'/'.$row['username'].'.jpg';
            rename($filename, $newFilename);

            $countRenamed++;
        }
        else if ($result->rowCount() > 1) {
            echo "Ambiguous results for: " . $filename . "\n"; 
        }
    }

    echo "\n";
    echo "Photos renamed: " . $countRenamed . "\n";
}
