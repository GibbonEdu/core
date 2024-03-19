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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Attendance\AttendanceLogCourseClassGateway;

//Gibbon system-wide includes
require __DIR__ . '/../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php' ;

$gibbonCourseClassID=$_POST['gibbonCourseClassID'] ?? '';
$gibbonTTDayRowClassID=!empty($_POST['gibbonTTDayRowClassID']) ? $_POST['gibbonTTDayRowClassID'] : null;
$currentDate=$_POST['currentDate'] ?? '';
$today=date('Y-m-d');

$moduleName = getModuleName($_POST['address'] ?? '');

if ($moduleName == 'Planner') {
    $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? '';
    $URL=$session->get('absoluteURL') . "/index.php?q=/modules/" . $moduleName . "/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&gibbonCourseClassID=$gibbonCourseClassID&date=" . $currentDate ;
} else {
    $URL=$session->get('absoluteURL') . "/index.php?q=/modules/" . $moduleName . "/attendance_take_byCourseClass.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID&currentDate=" . Format::date($currentDate) ;
}

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")==FALSE) {
    //Fail 0
    $URL.="&return=error0" ;
    header("Location: {$URL}");
    die();
}
else {
    //Proceed!
    //Check if gibbonCourseClassID and currentDate specified
    if ($gibbonCourseClassID=="" AND $currentDate=="") {
        //Fail1
        $URL.="&return=error1" ;
        header("Location: {$URL}");
        die();
    }
    else {
        try {
            $data=array("gibbonCourseClassID"=>$gibbonCourseClassID);
            $sql="SELECT * FROM gibbonCourseClass WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
            $result=$connection2->prepare($sql);
            $result->execute($data);
        }
        catch(PDOException $e) {
            //Fail2
            $URL.="&return=error2" ;
            header("Location: {$URL}");
            die();
        }

        if ($result->rowCount()!=1) {
            //Fail 2
            $URL.="&return=error1" ;
            header("Location: {$URL}");
            die();
        }
        else {
            //Check that date is not in the future
            if ($currentDate>$today) {
                //Fail 4
                $URL.="&return=error3" ;
                header("Location: {$URL}");
                die();
            }
            else {
                //Check that date is a school day
                if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
                    //Fail 5
                    $URL.="&return=error3" ;
                    header("Location: {$URL}");
                    die();
                }
                else {
                    $settingGateway = $container->get(SettingGateway::class);
                    $attendanceLogCourseClassGateway = $container->get(AttendanceLogCourseClassGateway::class);

                    //Write to database
                    require_once __DIR__ . '/src/AttendanceView.php';
                    $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

                    if (!empty($gibbonTTDayRowClassID)) {
                        $classLog = $attendanceLogCourseClassGateway->selectBy(['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $currentDate, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID])->fetch();
                    } else {
                        $classLog = $attendanceLogCourseClassGateway->selectBy(['gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $currentDate])->fetch();
                    }

                    if (!empty($classLog)) {
                        $gibbonAttendanceLogCourseClassID = $classLog['gibbonAttendanceLogCourseClassID'];
                        $attendanceLogCourseClassGateway->update($classLog['gibbonAttendanceLogCourseClassID'], [
                            'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                            'timestampTaken'=>date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        $gibbonAttendanceLogCourseClassID = $attendanceLogCourseClassGateway->insert([
                            'gibbonPersonIDTaker' => $session->get('gibbonPersonID'),
                            'gibbonCourseClassID'=>$gibbonCourseClassID,
                            'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID,
                            'date'=>$currentDate,
                            'timestampTaken'=>date('Y-m-d H:i:s'),
                        ]);
                    }

                    if (empty($gibbonAttendanceLogCourseClassID)) {
                        //Fail 2
                        $URL.="&return=error2" ;
                        header("Location: {$URL}");
                        die();
                    }

                    $recordFirstClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'recordFirstClassAsSchool');
                    $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);

                    $recordSchoolAttendance = $_POST['recordSchoolAttendance'] ?? 'N';
                    $count=$_POST["count"] ?? '';
                    $partialFail=FALSE ;

                    for ($i=0; $i<$count; $i++) {
                        $gibbonPersonID=$_POST[$i . "-gibbonPersonID"] ;

                        $type=$_POST[$i . "-type"] ?? '';
                        $reason=$_POST[$i . "-reason"] ?? '';
                        $comment=$_POST[$i . "-comment"] ?? '';
                        $prefilled=$_POST[$i . "-prefilled"] ?? '';

                        $attendanceCode = $attendance->getAttendanceCodeByType($type);
                        $direction = $attendanceCode['direction'];

                        //Check for last record on same day
                        try {
                            $data=array("gibbonPersonID"=>$gibbonPersonID, "date"=>$currentDate . "%");
                            $sql="SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(PDOException $e) {
                            //Fail 2
                            $URL.="&return=error2" ;
                            header("Location: {$URL}");
                            die();
                        }

                        // Check context, gibbonCourseClassID and type, updating only if not a match
                        $existing = false ;
                        $gibbonAttendanceLogPersonID = '';
                        if ($result->rowCount()>0) {
                            while ($row=$result->fetch()) {
                                if ($row['context'] == 'Class' && $row['gibbonCourseClassID'] == $gibbonCourseClassID && (empty($row['gibbonTTDayRowClassID']) || $row['gibbonTTDayRowClassID'] == $gibbonTTDayRowClassID) ) {
                                    $existing = true ;
                                    $gibbonAttendanceLogPersonID = $row['gibbonAttendanceLogPersonID'];
                                    break;
                                }
                            }
                        }

                        $data = [
                            'gibbonAttendanceCodeID' => $attendanceCode['gibbonAttendanceCodeID'],
                            'gibbonPersonID'         => $gibbonPersonID,
                            'context'                => 'Class',
                            'direction'              => $direction,
                            'type'                   => $type,
                            'reason'                 => $reason,
                            'comment'                => $comment,
                            'gibbonPersonIDTaker'    => $session->get('gibbonPersonID'),
                            'gibbonCourseClassID'    => $gibbonCourseClassID,
                            'gibbonTTDayRowClassID'  => $gibbonTTDayRowClassID,
                            'date'                   => $currentDate,
                            'timestampTaken'         => date('Y-m-d H:i:s'),
                        ];

                        if (!$existing) {
                            // If no records then create one
                            $inserted = $attendanceLogGateway->insert($data);
                            $partialFail &= !$inserted;
                        } else {
                            $updated = $attendanceLogGateway->update($gibbonAttendanceLogPersonID, $data);
                            $partialFail &= !$updated;
                        }

                        if ($recordFirstClassAsSchool == 'Y' && empty($prefilled)) {
                            $data['context'] = 'Person';
                            $inserted = $attendanceLogGateway->insert($data);
                            $partialFail &= !$inserted;
                        }
                    }

                    if ($partialFail == true) {
                        //Fail 3
                        $URL.="&return=warning1" ;
                        header("Location: {$URL}");
                        die();
                    } else {
                        //Success 0
                        $URL.="&return=success0&time=" . date("H-i-s") ;
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
