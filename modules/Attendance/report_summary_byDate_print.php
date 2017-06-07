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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_summary_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $dateEnd = (isset($_GET['dateEnd']))? dateConvert($guid, $_GET['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_GET['dateStart']))? dateConvert($guid, $_GET['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

    $group = !empty($_GET['group'])? $_GET['group'] : '';
    $sort = !empty($_GET['sort'])? $_GET['sort'] : 'surname, preferredName';

    $gibbonCourseClassID = (isset($_GET["gibbonCourseClassID"]))? $_GET["gibbonCourseClassID"] : 0;
    $gibbonRollGroupID = (isset($_GET["gibbonRollGroupID"]))? $_GET["gibbonRollGroupID"] : 0;

    $gibbonAttendanceCodeID = (isset($_GET["gibbonAttendanceCodeID"]))? $_GET["gibbonAttendanceCodeID"] : 0;
    $reportType = (empty($gibbonAttendanceCodeID))? 'types' : 'reasons';

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
        echo __($guid, 'There are no attendance codes defined.');
        echo '</div>';
    }
    else if ( empty($dateStart) || empty($group)) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo '<h2>';
        echo __($guid, 'Report Data').': '. date('M j', strtotime($dateStart) ) .' - '. date('M j, Y', strtotime($dateEnd) );
        echo '</h2>';

        try {
            $dataSchoolDays = array( 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSchoolDays = "SELECT COUNT(DISTINCT CASE WHEN date>=gibbonSchoolYear.firstDay AND date<=gibbonSchoolYear.lastDay THEN date END) as total, COUNT(DISTINCT CASE WHEN date>=:dateStart AND date <=:dateEnd THEN date END) as dateRange FROM gibbonAttendanceLogPerson, gibbonSchoolYearTerm, gibbonSchoolYear WHERE date>=gibbonSchoolYearTerm.firstDay AND date <= gibbonSchoolYearTerm.lastDay AND date <= NOW() AND gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID";

            $resultSchoolDays = $connection2->prepare($sqlSchoolDays);
            $resultSchoolDays->execute($dataSchoolDays);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $schoolDayCounts = $resultSchoolDays->fetch();

        echo '<p style="color:#666;">';
            echo '<strong>' . __($guid, 'Total number of school days to date:').' '.$schoolDayCounts['total'].'</strong><br/>';
            echo __($guid, 'Total number of school days in date range:').' '.$schoolDayCounts['dateRange'];
        echo '</p>';


        $sqlPieces = array();

        if ($reportType == 'types') {
            $attendanceCodes = array();

            while( $type = $resultCodes->fetch() ) {
                $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.name='".$type['name']."' THEN date END) AS ".$type['nameShort'];
                $attendanceCodes[ $type['direction'] ][] = $type;
            }
        }
        else if ($reportType == 'reasons') {
            $attendanceCodeInfo = $resultCodes->fetch();
            $attendanceReasons = explode(',', getSettingByScope($connection2, 'Attendance', 'attendanceReasons') );

            foreach( $attendanceReasons as $reason ) {
                $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.reason='".$reason."' THEN date END) AS `".$reason."`";
            }

            $sqlPieces[] = "COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.reason='' THEN date END) AS `No Reason`";
            $attendanceReasons[] = 'No Reason';
        }

        $sqlSelect = implode( ',', $sqlPieces );

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

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

            $sql .= ' '. $groupBy . ' '. $orderBy;

            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {

            echo '<table cellspacing="0" class="fullWidth colorOddEven" >';

            echo "<tr class='head'>";
            echo '<th style="width:80px" rowspan=2>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th rowspan=2>';
            echo __($guid, 'Name');
            echo '</th>';

            if ($reportType == 'types') {
                echo '<th colspan='.count($attendanceCodes['In']).' class="columnDivider" style="text-align:center;">';
                echo __($guid, 'IN');
                echo '</th>';
                echo '<th colspan='.count($attendanceCodes['Out']).' class="columnDivider" style="text-align:center;">';
                echo __($guid, 'OUT');
                echo '</th>';
            } else if ($reportType == 'reasons') {
                echo '<th colspan='.count($attendanceReasons).' class="columnDivider" style="text-align:center;">';
                echo __($guid, $attendanceCodeInfo['name'] );
                echo '</th>';
            }
            echo '</tr>';


            echo '<tr class="head" style="min-height:80px;">';

            if ($reportType == 'types') {

                for( $i = 0; $i < count($attendanceCodes['In']); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'" title="'.$attendanceCodes['In'][$i]['scope'].'">';
                        echo '<div class="verticalText">';
                        echo $attendanceCodes['In'][$i]['name'];
                        echo '</div>';
                    echo '</th>';
                }

                for( $i = 0; $i < count($attendanceCodes['Out']); $i++ ) {
                    echo '<th class="'.( $i == 0? 'verticalHeader columnDivider' : 'verticalHeader').'" title="'.$attendanceCodes['Out'][$i]['scope'].'">';
                        echo '<div class="verticalText">';
                        echo $attendanceCodes['Out'][$i]['name'];
                        echo '</div>';
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
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
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
                echo __($guid, 'All students are present.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';


        }
    }
}
?>
