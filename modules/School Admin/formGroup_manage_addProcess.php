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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['website' => 'URL']);

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/formGroup_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'] ?? '';
    $nameShort = $_POST['nameShort'] ?? '';
    $gibbonPersonIDTutor = !empty($_POST['gibbonPersonIDTutor']) ? $_POST['gibbonPersonIDTutor'] : null;
    $gibbonPersonIDTutor2 = !empty($_POST['gibbonPersonIDTutor2']) ? $_POST['gibbonPersonIDTutor2'] : null;
    $gibbonPersonIDTutor3 = !empty($_POST['gibbonPersonIDTutor3']) ? $_POST['gibbonPersonIDTutor3'] : null;
    $gibbonPersonIDEA = !empty($_POST['gibbonPersonIDEA']) ? $_POST['gibbonPersonIDEA'] : null;
    $gibbonPersonIDEA2 = !empty($_POST['gibbonPersonIDEA2']) ? $_POST['gibbonPersonIDEA2'] : null;
    $gibbonPersonIDEA3 = !empty($_POST['gibbonPersonIDEA3']) ? $_POST['gibbonPersonIDEA3'] : null;
    $gibbonSpaceID = !empty($_POST['gibbonSpaceID']) ? $_POST['gibbonSpaceID'] : null;
    $gibbonFormGroupIDNext = !empty($_POST['gibbonFormGroupIDNext']) ? $_POST['gibbonFormGroupIDNext'] : null;
    $website = $_POST['website'] ?? '';

    $attendance = $_POST['attendance'] ?? NULL;

    if ($gibbonSchoolYearID == '' or $name == '' or $nameShort == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness in current school year
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonFormGroup WHERE (name=:name OR nameShort=:nameShort) AND gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'nameShort' => $nameShort, 'gibbonPersonIDTutor' => $gibbonPersonIDTutor, 'gibbonPersonIDTutor2' => $gibbonPersonIDTutor2, 'gibbonPersonIDTutor3' => $gibbonPersonIDTutor3, 'gibbonPersonIDEA' => $gibbonPersonIDEA, 'gibbonPersonIDEA2' => $gibbonPersonIDEA2, 'gibbonPersonIDEA3' => $gibbonPersonIDEA3, 'gibbonSpaceID' => $gibbonSpaceID, 'gibbonFormGroupIDNext' => $gibbonFormGroupIDNext, 'attendance' => $attendance, 'website' => $website);
                $sql = 'INSERT INTO gibbonFormGroup SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, gibbonPersonIDTutor=:gibbonPersonIDTutor, gibbonPersonIDTutor2=:gibbonPersonIDTutor2, gibbonPersonIDTutor3=:gibbonPersonIDTutor3, gibbonPersonIDEA=:gibbonPersonIDEA, gibbonPersonIDEA2=:gibbonPersonIDEA2, gibbonPersonIDEA3=:gibbonPersonIDEA3, gibbonSpaceID=:gibbonSpaceID, gibbonFormGroupIDNext=:gibbonFormGroupIDNext, attendance=:attendance, website=:website';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 5, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
