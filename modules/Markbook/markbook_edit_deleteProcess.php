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

include '../../gibbon.php';

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonMarkbookColumnID = $_POST['gibbonMarkbookColumnID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/markbook_edit_delete.php&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonMarkbookColumnID and gibbonCourseClassID specified
    if ($gibbonMarkbookColumnID == '' or $gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonCourseClassID=:gibbonCourseClassID';
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
                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                $sql = 'DELETE FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
