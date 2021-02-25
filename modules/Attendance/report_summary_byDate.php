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

    $countClassAsSchool = getSettingByScope($connection2, 'Attendance', 'countClassAsSchool');
    $dateEnd = (isset($_REQUEST['dateEnd']))? dateConvert($guid, $_REQUEST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_REQUEST['dateStart']))? dateConvert($guid, $_REQUEST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    // Limit date range to the current school year
    if ($dateStart < $_SESSION[$guid]['gibbonSchoolYearFirstDay']) {
        $dateStart = $_SESSION[$guid]['gibbonSchoolYearFirstDay'];
    }

    if ($dateEnd > $_SESSION[$guid]['gibbonSchoolYearLastDay']) {
        $dateEnd = $_SESSION[$guid]['gibbonSchoolYearLastDay'];
    }

    $group = !empty($_REQUEST['group'])? $_REQUEST['group'] : '';
    $sort = !empty($_REQUEST['sort'])? $_REQUEST['sort'] : 'surname';

    $gibbonCourseClassID = (isset($_REQUEST["gibbonCourseClassID"]))? $_REQUEST["gibbonCourseClassID"] : 0;
    $gibbonRollGroupID = (isset($_REQUEST["gibbonRollGroupID"]))? $_REQUEST["gibbonRollGroupID"] : 0;

    $gibbonAttendanceCodeID = (isset($_REQUEST["gibbonAttendanceCodeID"]))? $_REQUEST["gibbonAttendanceCodeID"] : 0;
    $reportType = (empty($gibbonAttendanceCodeID))? 'types' : 'reasons';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_summary_byDate.php");

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateStart')->setValue(dateConvertBack($guid, $dateStart))->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateEnd')->setValue(dateConvertBack($guid, $dateEnd))->required();

    $options = array("all" => __('All Students'));
    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
        $options["class"] = __('Class');
    }
    if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
        $options["rollGroup"] = __('Roll Group');
    }
    $row = $form->addRow();
        $row->addLabel('group', __('Group By'));
        $row->addSelect('group')->fromArray($options)->selected($group)->required();

    $form->toggleVisibilityByClass('class')->onSelect('group')->when('class');
    $row = $form->addRow()->addClass('class');
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonCourseClassID)->placeholder()->required();

    $form->toggleVisibilityByClass('rollGroup')->onSelect('group')->when('rollGroup');
    $row = $form->addRow()->addClass('rollGroup');
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'preferredName' => __('Preferred Name'), 'rollGroup' => __('Roll Group')))->selected($sort)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    // Stop outputting if the form hasn't been submitted yet
    if (empty($group) || empty($sort)) {
        return;
    }

    // Get attendance codes
    try {
        if (!empty($gibbonAttendanceCodeID)) {
            $dataCodes = array( 'gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
            $sqlCodes = "SELECT * FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID";
        } else {
            $dataCodes = array();
            $sqlCodes = "SELECT * FROM gibbonAttendanceCode WHERE active = 'Y' AND reportable='Y' ORDER BY sequenceNumber ASC, name";
        }

        $resultCodes = $pdo->executeQuery($dataCodes, $sqlCodes);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultCodes->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('There are no attendance codes defined.');
        echo '</div>';
    }
    else if ( empty($dateStart) || empty($group)) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else if ($dateStart > $today || $dateEnd > $today) {
            echo "<div class='error'>";
            echo __('The specified date is in the future: it must be today or earlier.');
            echo '</div>';
    } else {
        echo '<h2>';
        echo __('Report Data').': '. Format::dateRangeReadable($dateStart, $dateEnd);        
        echo '</h2>';

        
            $dataSchoolDays = array( 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSchoolDays = "SELECT COUNT(DISTINCT CASE WHEN date>=gibbonSchoolYear.firstDay AND date<=gibbonSchoolYear.lastDay THEN date END) as total, COUNT(DISTINCT CASE WHEN date>=:dateStart AND date <=:dateEnd THEN date END) as dateRange FROM gibbonAttendanceLogPerson, gibbonSchoolYearTerm, gibbonSchoolYear WHERE date>=gibbonSchoolYearTerm.firstDay AND date <= gibbonSchoolYearTerm.lastDay AND date <= NOW() AND gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID";

            $resultSchoolDays = $connection2->prepare($sqlSchoolDays);
            $resultSchoolDays->execute($dataSchoolDays);
        $schoolDayCounts = $resultSchoolDays->fetch();

        echo '<p style="color:#666;">';
            echo '<strong>' . __('Total number of school days to date:').' '.$schoolDayCounts['total'].'</strong><br/>';
            echo __('Total number of school days in date range:').' '.$schoolDayCounts['dateRange'];
        echo '</p>';

        $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlPieces = array();

        if ($reportType == 'types') {
            $attendanceCodes = array();

            $i = 0;
            while( $type = $resultCodes->fetch() ) {
                $typeIdentifier = "`".str_replace("`","``",$type['nameShort'])."`";
                $data['type'.$i] = $type['name'];
                $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.name=:type".$i." THEN date END) AS ".$typeIdentifier;
                $attendanceCodes[ $type['direction'] ][] = $type;
                $i++;
            }
        }
        else if ($reportType == 'reasons') {
            $attendanceCodeInfo = $resultCodes->fetch();
            $attendanceReasons = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceReasons') );

            for($i = 0; $i < count($attendanceReasons); $i++) {
                $reasonIdentifier = "`".str_replace("`","``",$attendanceReasons[$i])."`";
                $data['reason'.$i] = $attendanceReasons[$i];
                $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.reason=:reason".$i." THEN date END) AS ".$reasonIdentifier;
            }

            $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.reason='' THEN date END) AS `No Reason`";
            $attendanceReasons[] = 'No Reason';
        }

        $sqlSelect = implode( ',', $sqlPieces );

        //Produce array of attendance data
        try {
            $groupBy = 'GROUP BY gibbonAttendanceLogPerson.gibbonPersonID';
            $orderBy = 'ORDER BY surname, preferredName';
            if ($sort == 'preferredName')
                $orderBy = 'ORDER BY preferredName, surname';
            if ($sort == 'rollGroup')
                $orderBy = ' ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName';

            if ($group == 'all') {
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, $sqlSelect FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
            }
            else if ($group == 'class') {
                $data['gibbonCourseClassID'] = $gibbonCourseClassID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, $sqlSelect FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonAttendanceLogPerson.context='Class' AND gibbonAttendanceLogPerson.gibbonCourseClassID=:gibbonCourseClassID";
            }
            else if ($group == 'rollGroup') {
                $data['gibbonRollGroupID'] = $gibbonRollGroupID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, $sqlSelect FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND gibbonAttendanceLogPerson.context='Roll Group'";
            }

            if ( !empty($gibbonAttendanceCodeID) ) {
                $data['gibbonAttendanceCodeID'] = $gibbonAttendanceCodeID;
                $sql .= ' AND gibbonAttendanceCode.gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
            }

            if ($countClassAsSchool == 'N' && $group != 'class') {
                $sql .= " AND NOT context='Class'";
            }

            $sql .= ' '. $groupBy . ' '. $orderBy;

            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_summary_byDate_print.php&dateStart='.dateConvertBack($guid, $dateStart).'&dateEnd='.dateConvertBack($guid, $dateEnd).'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&gibbonAttendanceCodeID='. $gibbonAttendanceCodeID .'&group=' . $group . '&sort=' . $sort . "'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo '<table cellspacing="0" class="fullWidth colorOddEven" >';

            echo "<tr class='head'>";
            echo '<th style="width:80px" rowspan=2>';
            echo __('Roll Group');
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
            } else if ($reportType == 'reasons') {
                echo '<th colspan='.count($attendanceReasons).' class="columnDivider" style="text-align:center;">';
                echo __($attendanceCodeInfo['name'] );
                echo '</th>';
            }
            echo '</tr>';


            echo '<tr class="head" style="min-height:80px;">';

            if ($reportType == 'types') {

                $href= $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_summary_byDate.php&dateStart='.dateConvertBack($guid, $dateStart).'&dateEnd='.dateConvertBack($guid, $dateEnd).'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonRollGroupID='.$gibbonRollGroupID.'&group=' . $group . '&sort=' . $sort;

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
            } else if ($reportType == 'reasons') {
                for( $i = 0; $i < count($attendanceReasons); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'">';
                        echo '<div class="verticalText">';
                        echo $attendanceReasons[$i];
                        echo '</div>';
                    echo '</th>';
                }
            }

            echo '</tr>';


            while ($row = $result->fetch()) {

                // ROW
                echo "<tr>";
                echo '<td>';
                    echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                    echo '<a href="index.php?q=/modules/Attendance/report_studentHistory.php&gibbonPersonID='.$row['gibbonPersonID'].'" target="_blank">';
                    echo Format::name('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                    echo '</a>';
                echo '</td>';

                if ($reportType == 'types') {
                    for( $i = 0; $i < count($attendanceCodes['In']); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $row[ $attendanceCodes['In'][$i]['nameShort'] ];
                        echo '</td>';
                    }

                    for( $i = 0; $i < count($attendanceCodes['Out']); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $row[ $attendanceCodes['Out'][$i]['nameShort'] ];
                        echo '</td>';
                    }
                } else if ($reportType == 'reasons') {
                    for( $i = 0; $i < count($attendanceReasons); $i++ ) {
                        echo '<td class="center '.( $i == 0? 'columnDivider' : '').'">';
                            echo $row[ $attendanceReasons[$i] ];
                        echo '</td>';
                    }
                }
                echo '</tr>';

            }
            if ($result->rowCount() == 0) {
                echo "<tr>";
                echo '<td colspan=5>';
                echo __('All students are present.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';


        }
    }
}
?>
