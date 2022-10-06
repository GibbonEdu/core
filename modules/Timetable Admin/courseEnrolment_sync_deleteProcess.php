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

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync')
    ->withQueryParams([
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
    ]);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_delete.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
    exit();
} else {
    //Proceed!
    $gibbonYearGroupID = (isset($_POST['gibbonYearGroupID']))? $_POST['gibbonYearGroupID'] : null;

    if (empty($gibbonYearGroupID)) {
        header('Location: ' . $URL->withReturn('error1'));
        exit;
    } else {
        $data = array('gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "DELETE FROM gibbonCourseClassMap WHERE gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID";

        $pdo->executeQuery($data, $sql);

        if ($pdo->getQuerySuccess() == false) {
            header('Location: ' . $URL->withReturn('error2'));
            exit;
        } else {
            header('Location: ' . $URL->withReturn('success0'));
            exit;
        }
    }
}
