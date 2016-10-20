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

    
    require_once './modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);

    if ($dateStart != '' && $group != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $dataSchoolDays = array( 'dateStart' => $dateStart, 'dateEnd' => $dateEnd );
            $sqlSchoolDays = "SELECT COUNT(DISTINCT date) as total, COUNT(DISTINCT CASE WHEN date>=:dateStart AND date <=:dateEnd THEN date END) as dateRange FROM gibbonAttendanceLogPerson, gibbonSchoolYearTerm, gibbonSchoolYear WHERE date>=gibbonSchoolYearTerm.firstDay AND date <= gibbonSchoolYearTerm.lastDay AND date <= NOW() AND gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart)";

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

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd);

            if ($group == 'all') {
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Excused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }
            else if ($group == 'class') {
                $data['gibbonCourseClassID'] = $gibbonCourseClassID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Excused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) AND gibbonAttendanceLogPerson.gibbonCourseClassID=:gibbonCourseClassID GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }
            else if ($group == 'rollGroup') {
                $data['gibbonRollGroupID'] = $gibbonRollGroupID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Excused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND gibbonAttendanceCode.name='Absent - Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND gibbonAttendanceLogPerson.gibbonCourseClassID=0 GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }

            if ( empty($sort) ) {
                $sort = 'surname, preferredName';
            }
            
            if ($sort == 'rollGroup') {
                $sql .= ' ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName';
            } else {
                $sql .= ' ORDER BY ' . $sort;
            }

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

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_summary_byDate_print.php&dateStart='.dateConvertBack($guid, $dateStart)."&sort=" . $sort . "'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo '<table cellspacing="0" class="fullWidth colorOddEven" >';

            echo "<tr class='head'>";
            echo '<th style="width:80px" rowspan=2>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th rowspan=2>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th colspan=3 style="border-left: 1px solid #666;text-align:center;">';
            echo __($guid, 'IN');
            echo '</th>';
            echo '<th colspan=3 style="border-left: 1px solid #666;text-align:center;">';
            echo __($guid, 'OUT');
            echo '</th>';
            echo '</tr>';


            echo "<tr class='head'>";
            echo '<th class="verticalHeader" style="border-left: 1px solid #666;">';
                echo '<div class="verticalText">';
                echo __($guid, 'Present');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Late');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Offsite');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader" style="border-left: 1px solid #666;">';
                echo '<div class="verticalText">';
                echo __($guid, 'Absent').'<br/><span class="small emphasis">'.__($guid, 'Excused').'</span>';
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Absent').'<br/><span class="small emphasis">'.__($guid, 'Unexcused').'</span>';
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Left');
                echo '</div>';
            echo '</th>';
            echo '</tr>';


            while ($row = $result->fetch()) {


                // ROW
                echo "<tr>";
                echo '<td>';
                    echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                    echo '<a href="index.php?q=/modules/Attendance/report_studentHistory.php&gibbonPersonID='.$row['gibbonPersonID'].'" target="_blank">';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                    echo '</a>';
                echo '</td>';

                echo '<td class="center" style="border-left: 1px solid #666;">';
                    echo $row['present'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['late'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['offsite'];
                echo '</td>';
                echo '<td class="center" style="border-left: 1px solid #666;">';
                    echo $row['excused'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['unexcused'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['left'];
                echo '</td>';
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