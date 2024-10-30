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

use Gibbon\Domain\Timetable\TimetableDayGateway;

include '../../gibbon.php';

$gibbonTTID = $_GET['gibbonTTID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$action = $_POST['action'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else if ($action == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    $days = isset($_POST['gibbonTTDayIDList']) ? $_POST['gibbonTTDayIDList'] : array();

    //Proceed!
    if (count($days) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $timetableDayGateway = $container->get(TimetableDayGateway::class);
        $partialFail = false;

        foreach ($days as $gibbonTTDayID) {
            $data = $timetableDayGateway->getByID($gibbonTTDayID);
            $data['name'] .= " Copy";

            //Copy gibbonTTDay
            $inserted = $timetableDayGateway->insert($data);
            $partialFail &= !$inserted;

            //Copy gibbonTTDayRowClass
            $classes = $timetableDayGateway->selectTTDayRowClassesByID($gibbonTTDayID)->fetchAll();

            if (empty($classes)) continue;

            foreach ($classes as $class) {
                $insertedClass = $timetableDayGateway->insertDayRowClass([
                    'gibbonTTDayID' => $inserted,
                    'gibbonTTColumnRowID' => $class['gibbonTTColumnRowID'],
                    'gibbonCourseClassID' => $class['gibbonCourseClassID'],
                    'gibbonSpaceID' => $class['gibbonSpaceID'],
                ]);
                $partialFail &= !$insertedClass;

                //Copy gibbonTTDayRowClassException
                $exceptions = $timetableDayGateway->selectTTDayRowClassExceptionsByID($class['gibbonTTDayRowClassID'])->fetchAll();

                if (empty($exceptions)) continue;

                foreach ($exceptions as $exception) {
                    $insertedExceptions = $timetableDayGateway->insertDayRowClassException([
                        'gibbonTTDayRowClassID' => $insertedClass,
                        'gibbonPersonID' => $exception['gibbonPersonID']
                    ]);
                    $partialFail &= !$insertedExceptions;
                }
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
