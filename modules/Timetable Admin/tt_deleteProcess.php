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

use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$gibbonTTID = $_GET['gibbonTTID'] ?? '';
$URL = Url::fromModuleRoute('Timetable Admin', 'tt_delete')
    ->withQueryParams([
        'gibbonTTID' => $gibbonTTID,
        'gibbonSchoolYearID' => $_GET['gibbonSchoolYearID'] ?? '',
    ]);
$URLDelete = Url::fromModuleRoute('Timetable Admin', 'tt')->withQueryParam('gibbonSchoolYearID', $_GET['gibbonSchoolYearID'] ?? '');

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_delete.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    //Proceed!
    //Check if gibbonTTID specified
    if ($gibbonTTID == '') {
        header('Location: ' . $URL->withReturn('error1'));
    } else {
        try {
            $data = array('gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            header('Location: ' . $URL->withReturn('error2'));
            exit();
        }

        if ($result->rowCount() != 1) {
            header('Location: ' . $URL->withReturn('error2'));
        } else {
            //Delete Course
            try {
                $data = array('gibbonTTID' => $gibbonTTID);
                $sql = 'DELETE FROM gibbonTT WHERE gibbonTTID=:gibbonTTID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            header('Location: ' . $URLDelete->withReturn('success0'));
        }
    }
}
