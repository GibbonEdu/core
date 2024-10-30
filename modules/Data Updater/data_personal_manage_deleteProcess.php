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

use Gibbon\Domain\User\PersonalDocumentGateway;

include '../../gibbon.php';

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonPersonUpdateID = $_POST['gibbonPersonUpdateID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/data_personal_manage_delete.php&gibbonPersonUpdateID=$gibbonPersonUpdateID&gibbonSchoolYearID=$gibbonSchoolYearID";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/data_personal_manage.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonPersonUpdateID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = 'SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
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
            //Write to database
            try {
                $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
                $sql = 'DELETE FROM gibbonPersonUpdate WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            // Personal Documents
            $container->get(PersonalDocumentGateway::class)->deletePersonalDocuments('gibbonPersonUpdate', $gibbonPersonUpdateID);

            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
