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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$dateStamp = $_GET['dateStamp'] ?? '';
$gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';

if ($gibbonSchoolYearID == '' or $dateStamp == '') { echo 'Fatal error loading this page!';
} else {
    $URL = Url::fromModuleRoute('Timetable Admin', 'ttDates_edit')
        ->withQueryParams([
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'dateStamp' => $dateStamp,
        ]);

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_delete.php') == false) {
        header('Location: ' . $URL->withReturn('error0'));
    } else {
        //Proceed!
        //Check if gibbonTTDayID specified
        if ($gibbonTTDayID == '') {
            header('Location: ' . $URL->withReturn('error1'));
        } else {
            try {
                $data = array('date' => date('Y-m-d', $dateStamp), 'gibbonTTDayID' => $gibbonTTDayID);
                $sql = 'SELECT * FROM gibbonTTDayDate WHERE gibbonTTDayID=:gibbonTTDayID AND date=:date';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            if ($result->rowCount() < 1) {
                header('Location: ' . $URL->withReturn('error2'));
            } else {
                //Write to database
                try {
                    $data = array('date' => date('Y-m-d', $dateStamp), 'gibbonTTDayID' => $gibbonTTDayID);
                    $sql = 'DELETE FROM gibbonTTDayDate WHERE gibbonTTDayID=:gibbonTTDayID AND date=:date';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    header('Location: ' . $URL->withReturn('error2'));
                    exit();
                }

                header('Location: ' . $URL->withReturn('success0'));
            }
        }
    }
}
