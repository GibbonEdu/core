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

use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonCourseID = $_POST['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
    echo 'Fatal error loading this page!';
} else {
    $URL = Url::fromModuleRoute('Timetable Admin', 'course_manage_class_edit')
        ->withQueryParams([
            'gibbonCourseID' => $gibbonCourseID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
        ]);

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_edit.php') == false) {
        header('Location: ' . $URL->withReturn('error0'));
    } else {
        //Proceed!
        //Check if course specified
        if ($gibbonCourseClassID == '') {
            header('Location: ' . $URL->withReturn('error1'));
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseClassID=:gibbonCourseClassID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            if ($result->rowCount() != 1) {
                header('Location: ' . $URL->withReturn('error2'));
            } else {
                //Validate Inputs
                $name = $_POST['name'] ?? '';
                $nameShort = $_POST['nameShort'] ?? '';
                $reportable = $_POST['reportable'] ?? '';
                $attendance = $_POST['attendance'] ?? 'N';
                $enrolmentMin = (!empty($_POST['enrolmentMin']) && is_numeric($_POST['enrolmentMin'])) ? $_POST['enrolmentMin'] : null;
                $enrolmentMax = (!empty($_POST['enrolmentMax']) && is_numeric($_POST['enrolmentMax'])) ? $_POST['enrolmentMax'] : null;

                $customRequireFail = false;
                $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Class', [], $customRequireFail);

                if ($customRequireFail) {
                    header('Location: ' . $URL->withReturn('error1'));
                    exit;
                }

                if ($name == '' or $nameShort == '') {
                    header('Location: ' . $URL->withReturn('error3'));
                } else {
                    //Check unique inputs for uniquness
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT * FROM gibbonCourseClass WHERE ((name=:name) OR (nameShort=:nameShort)) AND gibbonCourseID=:gibbonCourseID AND NOT gibbonCourseClassID=:gibbonCourseClassID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        header('Location: ' . $URL->withReturn('error2'));
                        exit();
                    }

                    if ($result->rowCount() > 0) {
                        header('Location: ' . $URL->withReturn('error3'));
                    } else {
                        //Write to database
                        try {
                            $data = array('name' => $name, 'nameShort' => $nameShort, 'reportable' => $reportable, 'attendance' => $attendance, 'enrolmentMin' => $enrolmentMin, 'enrolmentMax' => $enrolmentMax, 'fields' => $fields, 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sql = 'UPDATE gibbonCourseClass SET name=:name, nameShort=:nameShort, reportable=:reportable, attendance=:attendance, enrolmentMin=:enrolmentMin, enrolmentMax=:enrolmentMax, fields=:fields WHERE gibbonCourseClassID=:gibbonCourseClassID';
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
    }
}
