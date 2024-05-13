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
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Attendance Summary by Date'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_summary_byDate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    echo '<h2>';
    echo __('Choose Date');
    echo '</h2>';

    $today = date('Y-m-d');

    $settingGateway = $container->get(SettingGateway::class);
    $countClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');
    $dateEnd = (isset($_REQUEST['dateEnd']))? Format::dateConvert($_REQUEST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_REQUEST['dateStart']))? Format::dateConvert($_REQUEST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    // Limit date range to the current school year
    if ($dateStart < $session->get('gibbonSchoolYearFirstDay')) {
        $dateStart = $session->get('gibbonSchoolYearFirstDay');
    }

    if ($dateEnd > $session->get('gibbonSchoolYearLastDay')) {
        $dateEnd = $session->get('gibbonSchoolYearLastDay');
    }

    $group = !empty($_REQUEST['group'])? $_REQUEST['group'] : '';
    $sort = !empty($_REQUEST['sort'])? $_REQUEST['sort'] : 'surname';

    $gibbonCourseClassID = (isset($_REQUEST["gibbonCourseClassID"]))? $_REQUEST["gibbonCourseClassID"] : 0;
    $gibbonFormGroupID = (isset($_REQUEST["gibbonFormGroupID"]))? $_REQUEST["gibbonFormGroupID"] : 0;

    $gibbonAttendanceCodeID = (isset($_REQUEST["gibbonAttendanceCodeID"]))? $_REQUEST["gibbonAttendanceCodeID"] : 0;
    $reportType = (empty($gibbonAttendanceCodeID))? 'types' : 'reasons';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_summary_byDate.php");

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->setValue(Format::date($dateStart))->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->setValue(Format::date($dateEnd))->required();

    $options = array("all" => __('All Students'));
    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
        $options["class"] = __('Class');
    }
    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byFormGroup.php")) {
        $options["formGroup"] = __('Form Group');
    }
    $row = $form->addRow();
        $row->addLabel('group', __('Group By'));
        $row->addSelect('group')->fromArray($options)->selected($group)->required();

    $form->toggleVisibilityByClass('class')->onSelect('group')->when('class');
    $row = $form->addRow()->addClass('class');
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'))->selected($gibbonCourseClassID)->placeholder()->required();

    $form->toggleVisibilityByClass('formGroup')->onSelect('group')->when('formGroup');
    $row = $form->addRow()->addClass('formGroup');
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'preferredName' => __('Preferred Name'), 'formGroup' => __('Form Group')))->selected($sort)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    // Stop outputting if the form hasn't been submitted yet
    if (empty($group) || empty($sort)) {
        return;
    }

    // Get attendance codes
    try {
        if (!empty($gibbonAttendanceCodeID)) {
            $dataCodes = array( 'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
            $sqlCodes = "SELECT direction as groupBy, gibbonAttendanceCode.* FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID";
        } else {
            $dataCodes = array();
            $sqlCodes = "SELECT direction as groupBy, gibbonAttendanceCode.* FROM gibbonAttendanceCode WHERE active = 'Y' AND reportable='Y' ORDER BY sequenceNumber ASC, name";
        }

        $resultCodes = $pdo->select($sqlCodes, $dataCodes);
    } catch (PDOException $e) {
    }

    $attendanceCodes = $resultCodes->fetchGrouped();
    $attendanceReasons = explode(',', $settingGateway->getSettingByScope('Attendance', 'attendanceReasons') );
    $attendanceReasons[] = 'No Reason';

    if ($resultCodes->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('There are no attendance codes defined.');
        echo '</div>';
    }
    else if ( empty($dateStart) || empty($group)) {
        echo $page->getBlankSlate();
    } else if ($dateStart > $today || $dateEnd > $today) {
            echo "<div class='error'>";
            echo __('The specified date is in the future: it must be today or earlier.');
            echo '</div>';
    } else {
        echo '<h2>';
        echo __('Report Data').': '. Format::dateRangeReadable($dateStart, $dateEnd);
        echo '</h2>';


            $dataSchoolDays = array( 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sqlSchoolDays = "SELECT 
                COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.date>=gibbonSchoolYear.firstDay AND gibbonAttendanceLogPerson.date<=gibbonSchoolYear.lastDay THEN gibbonAttendanceLogPerson.date END) as total, COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.date>=:dateStart AND gibbonAttendanceLogPerson.date <=:dateEnd THEN gibbonAttendanceLogPerson.date END) as dateRange 
            FROM gibbonAttendanceLogPerson
                JOIN gibbonSchoolYearTerm ON (gibbonAttendanceLogPerson.date>=gibbonSchoolYearTerm.firstDay AND gibbonAttendanceLogPerson.date <= gibbonSchoolYearTerm.lastDay)
                JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID )
                LEFT JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID AND gibbonSchoolYearSpecialDay.date = gibbonAttendanceLogPerson.date AND gibbonSchoolYearSpecialDay.type='School Closure')
            WHERE  
                gibbonAttendanceLogPerson.date <= NOW() 
                AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonSchoolYearSpecialDay.gibbonSchoolYearSpecialDayID IS NULL";

            $resultSchoolDays = $connection2->prepare($sqlSchoolDays);
            $resultSchoolDays->execute($dataSchoolDays);
        $schoolDayCounts = $resultSchoolDays->fetch();

        echo '<p style="color:#666;">';
            echo '<strong>' . __('Total number of school days to date:').' '.$schoolDayCounts['total'].'</strong><br/>';
            echo __('Total number of school days in date range:').' '.$schoolDayCounts['dateRange'];
        echo '</p>';

        $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));

        //Produce array of attendance data
        try {
            $orderBy = 'ORDER BY surname, preferredName, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken';
            if ($sort == 'preferredName')
                $orderBy = 'ORDER BY preferredName, surname, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken';
            if ($sort == 'formGroup')
                $orderBy = ' ORDER BY LENGTH(formGroup), formGroup, surname, preferredName, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken';

            if ($group == 'all') {
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup, surname, preferredName, gibbonAttendanceLogPerson.*, gibbonAttendanceCode.nameShort as code FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
            }
            else if ($group == 'class') {
                $data['gibbonCourseClassID'] = $gibbonCourseClassID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup, surname, preferredName, gibbonAttendanceLogPerson.*, gibbonAttendanceCode.nameShort as code FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonAttendanceLogPerson.context='Class' AND gibbonAttendanceLogPerson.gibbonCourseClassID=:gibbonCourseClassID";
            }
            else if ($group == 'formGroup') {
                $data['gibbonFormGroupID'] = $gibbonFormGroupID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonFormGroup.nameShort AS formGroup, surname, preferredName, gibbonAttendanceLogPerson.*, gibbonAttendanceCode.nameShort as code FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID";
            }

            if ( !empty($gibbonAttendanceCodeID) ) {
                $data['gibbonAttendanceCodeID'] = $gibbonAttendanceCodeID;
                $sql .= ' AND gibbonAttendanceCode.gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
            }

            if ($countClassAsSchool == 'N' && $group != 'class') {
                $sql .= " AND NOT context='Class'";
            }

            $sql .= ' '. $orderBy;

            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() < 1) {
            echo $page->getBlankSlate();
        } else {

            if (empty($daysOfWeek)) {
                $sql = "SELECT nameShort, name FROM gibbonDaysOfWeek where schoolDay='Y'";
                $daysOfWeek = $pdo->select($sql)->fetchKeyPair();
            }
    
            if (empty($schoolClosures)) {
                $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')];
                $sql = "SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name 
                        FROM gibbonSchoolYear 
                        JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                        JOIN gibbonSchoolYearSpecialDay ON (gibbonSchoolYearTerm.gibbonSchoolYearTermID=gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID)
                        WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonSchoolYearSpecialDay.type='School Closure' AND gibbonSchoolYearSpecialDay.date BETWEEN :dateStart AND :dateEnd
                        ORDER BY date";
                $schoolClosures = $pdo->select($sql, $data)->fetchKeyPair();
            }

            $dateRange = new DatePeriod(
                new DateTimeImmutable($dateStart),
                new DateInterval('P1D'),
                (new DateTimeImmutable($dateEnd))->modify('+1 day')
            );

            // Group the results by person first, then by date
            $attendanceResult = array_reduce($result->fetchAll(), function ($group, $item) {
                if (!isset($group[$item['gibbonPersonID']])) {
                    $group[$item['gibbonPersonID']] = [];
                }
                $group[$item['gibbonPersonID']][$item['date']][] = $item;
                return $group;
            }, []);

            $attendanceData = [];

            foreach ($attendanceResult as $gibbonPersonID => $values) {

                if (!isset($attendanceData[$gibbonPersonID])) {
                    $log = current(current($values));
                    $attendanceData[$gibbonPersonID]['formGroup'] = $log['formGroup'];
                    $attendanceData[$gibbonPersonID]['preferredName'] = $log['preferredName'];
                    $attendanceData[$gibbonPersonID]['surname'] = $log['surname'];
                }

                foreach ($dateRange as $date) {
                    if ($date->format('Y-m-d') > date('Y-m-d')) continue;
    
                    // Skip non-school days and school closures
                    if (!isset($daysOfWeek[$date->format('D')])) continue;
                    if (isset($schoolClosures[$date->format('Y-m-d')])) continue;
    
                    $logs = $values[$date->format('Y-m-d')] ?? [];

                    if (empty($logs)) continue;

                    if ($group == 'class') {
                        // Count all class logs
                        foreach ($logs as $log) {
                            $attendanceData[$gibbonPersonID][$log['code']] = ($attendanceData[$gibbonPersonID][$log['code']] ?? 0) + 1;

                            $reason = !empty($log['reason']) ? $log['reason'] : 'No Reason';
                            $attendanceData[$gibbonPersonID][$reason] = ($attendanceData[$gibbonPersonID][$reason] ?? 0) + 1;
                        }
                    } else {
                        // Count only the end of day logs
                        $endOfDay = end($logs);

                        $attendanceData[$gibbonPersonID][$endOfDay['code']] = ($attendanceData[$gibbonPersonID][$endOfDay['code']] ?? 0) + 1;

                        $reason = !empty($endOfDay['reason']) ? $endOfDay['reason'] : 'No Reason';
                        $attendanceData[$gibbonPersonID][$reason] = ($attendanceData[$gibbonPersonID][$reason] ?? 0) + 1;
                    }

                    $attendanceData[$gibbonPersonID]['total'] = ($attendanceData[$gibbonPersonID]['total'] ?? 0) + 1;
                }
            }

            // To avoid duplicating code, the print function has been removed until this report can be refactored
            // echo "<div class='linkTop'>";
            // echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/report_summary_byDate_print.php&dateStart='.Format::date($dateStart).'&dateEnd='.Format::date($dateEnd).'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonAttendanceCodeID='. $gibbonAttendanceCodeID .'&group=' . $group . '&sort=' . $sort . "'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
            // echo '</div>';

            echo '<table cellspacing="0" class="fullWidth colorOddEven" >';

            echo "<tr class='head'>";
            echo '<th style="width:80px" rowspan=2>';
            echo __('Form Group');
            echo '</th>';
            echo '<th rowspan=2>';
            echo __('Name');
            echo '</th>';

            if ($reportType == 'types') {
                echo '<th colspan='.count($attendanceCodes['In']).' class="columnDivider" style="text-align:center;">';
                echo __('IN');
                echo '</th>';
                echo '<th colspan='.count($attendanceCodes['Out']).' class="columnDivider" style="text-align:center;">';
                echo __('OUT');
                echo '</th>';
                echo '<th colspan=1 class="columnDivider" style="text-align:center;">';
                // echo ;
                echo '</th>';
            } else if ($reportType == 'reasons') {
                $attendanceCodeName = $pdo->selectOne("SELECT name FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID", ['gibbonAttendanceCodeID' => $gibbonAttendanceCodeID]);
                echo '<th colspan='.count($attendanceReasons).' class="columnDivider" style="text-align:center;">';
                echo __($attendanceCodeName);
                echo '</th>';
            }
            echo '</tr>';


            echo '<tr class="head" style="min-height:80px;">';

            if ($reportType == 'types') {

                $href= $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/report_summary_byDate.php&dateStart='.Format::date($dateStart).'&dateEnd='.Format::date($dateEnd).'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&group=' . $group . '&sort=' . $sort;

                for( $i = 0; $i < count($attendanceCodes['In']); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'" title="'.__($attendanceCodes['In'][$i]['scope']).'">';
                        echo '<a class="verticalText" href="'.$href.'&gibbonAttendanceCodeID='.$attendanceCodes['In'][$i]['gibbonAttendanceCodeID'].'">';
                        echo __($attendanceCodes['In'][$i]['name']);
                        echo '</a>';
                    echo '</th>';
                }

                for( $i = 0; $i < count($attendanceCodes['Out']); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'" title="'.__($attendanceCodes['Out'][$i]['scope']).'">';
                        echo '<a class="verticalText" href="'.$href.'&gibbonAttendanceCodeID='.$attendanceCodes['Out'][$i]['gibbonAttendanceCodeID'].'">';
                        echo __($attendanceCodes['Out'][$i]['name']);
                        echo '</a>';
                    echo '</th>';
                }

                echo '<th class="verticalHeader columnDivider" title="'.__('Total').'">';
                    echo '<div class="verticalText">';
                    echo __('Total');
                    echo '</div>';
                echo '</th>';

            } else if ($reportType == 'reasons') {
                for( $i = 0; $i < count($attendanceReasons); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'">';
                        echo '<div class="verticalText">';
                        echo $attendanceReasons[$i] ?? '';
                        echo '</div>';
                    echo '</th>';
                }
            }

            echo '</tr>';

            foreach ($attendanceData as $gibbonPersonID => $values) {

                // ROW
                echo "<tr>";
                echo '<td>';
                    echo $values['formGroup'];
                echo '</td>';
                echo '<td>';
                    echo '<a href="index.php?q=/modules/Attendance/report_studentHistory.php&gibbonPersonID='.$gibbonPersonID.'" target="_blank">';
                    echo Format::name('', $values['preferredName'], $values['surname'], 'Student', ($sort != 'preferredName') );
                    echo '</a>';
                echo '</td>';

                if ($reportType == 'types') {
                    for( $i = 0; $i < count($attendanceCodes['In']); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $values[ $attendanceCodes['In'][$i]['nameShort'] ] ?? 0;
                        echo '</td>';
                    }

                    for( $i = 0; $i < count($attendanceCodes['Out']); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $values[ $attendanceCodes['Out'][$i]['nameShort'] ] ?? 0;
                        echo '</td>';
                    }

                    echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                        echo $values[ 'total' ] ?? 0;
                    echo '</td>';
                } else if ($reportType == 'reasons') {
                    for( $i = 0; $i < count($attendanceReasons); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $values[ $attendanceReasons[$i] ] ?? 0;
                        echo '</td>';
                    }
                }
                echo '</tr>';

            }

            echo '</table>';


        }
    }
}
?>
