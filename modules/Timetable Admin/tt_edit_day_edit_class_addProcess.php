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
use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
$gibbonTTID = $_GET['gibbonTTID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';

$gibbonSpaceID = !empty($_POST['gibbonSpaceID']) ? $_POST['gibbonSpaceID'] : null;

if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = Url::fromModuleRoute('Timetable Admin', 'tt_edit_day_edit_class_add')
        ->withQueryParams([
            'gibbonTTDayID' => $gibbonTTDayID,
            'gibbonTTID' => $gibbonTTID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonTTColumnRowID' => $gibbonTTColumnRowID,
        ]);

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_add.php') == false) {
        header('Location: ' . $URL->withReturn('error0'));
    } else {
        //Proceed!
        //Check if gibbonTTDayID specified
        if ($gibbonTTDayID == '') {
            header('Location: ' . $URL->withReturn('error1'));
        } else {

                $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                $sql = 'SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                header('Location: ' . $URL->withReturn('error2'));
            } else {
                //Write to database
                try {
                    $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonTTDayID' => $gibbonTTDayID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSpaceID' => $gibbonSpaceID);
                    $sql = 'INSERT INTO gibbonTTDayRowClass SET gibbonTTColumnRowID=:gibbonTTColumnRowID, gibbonTTDayID=:gibbonTTDayID, gibbonCourseClassID=:gibbonCourseClassID, gibbonSpaceID=:gibbonSpaceID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    header('Location: ' . $URL->withReturn('error2'));
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                header('Location: ' . $URL->withReturn('success0')->withQueryParams([
                    'editID' => $AI,
                    'gibbonCourseClassID' => 'gibbonCourseClassID',
                ]));
            }
        }
    }
}
