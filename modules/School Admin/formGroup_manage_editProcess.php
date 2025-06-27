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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['website' => 'URL']);

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/formGroup_manage_edit.php&gibbonFormGroupID=$gibbonFormGroupID&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonFormGroupID and gibbonSchoolYearID specified
    if ($gibbonFormGroupID == '' or $gibbonSchoolYearID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
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
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                    $sql = 'SELECT * FROM gibbonFormGroup WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonFormGroupID=:gibbonFormGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
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
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonPersonIDTutor' => $gibbonPersonIDTutor, 'gibbonPersonIDTutor2' => $gibbonPersonIDTutor2, 'gibbonPersonIDTutor3' => $gibbonPersonIDTutor3, 'gibbonPersonIDEA' => $gibbonPersonIDEA, 'gibbonPersonIDEA2' => $gibbonPersonIDEA2, 'gibbonPersonIDEA3' => $gibbonPersonIDEA3, 'gibbonSpaceID' => $gibbonSpaceID, 'gibbonFormGroupIDNext' => $gibbonFormGroupIDNext, 'attendance' => $attendance, 'website' => $website, 'gibbonFormGroupID' => $gibbonFormGroupID);
                        $sql = 'UPDATE gibbonFormGroup SET name=:name, nameShort=:nameShort, gibbonPersonIDTutor=:gibbonPersonIDTutor, gibbonPersonIDTutor2=:gibbonPersonIDTutor2, gibbonPersonIDTutor3=:gibbonPersonIDTutor3, gibbonPersonIDEA=:gibbonPersonIDEA, gibbonPersonIDEA2=:gibbonPersonIDEA2, gibbonPersonIDEA3=:gibbonPersonIDEA3, gibbonSpaceID=:gibbonSpaceID, gibbonFormGroupIDNext=:gibbonFormGroupIDNext, attendance=:attendance, website=:website WHERE gibbonFormGroupID=:gibbonFormGroupID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
