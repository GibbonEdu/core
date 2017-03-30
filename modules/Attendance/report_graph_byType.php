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

use Gibbon\Forms\Form;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

function getDateRange($guid, $connection2, $first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {

    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);

    while( $current <= $last ) {
        $date = date($output_format, $current);
        if (isSchoolOpen($guid, $date, $connection2 )) {
            $dates[] = $date;
        }
        $current = strtotime($step, $current);
    }

    return $dates;
}

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_graph_byType.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance Trends').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    $dateEnd = (isset($_POST['dateEnd']))? dateConvert($guid, $_POST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_POST['dateStart']))? dateConvert($guid, $_POST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

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

    $sort = !empty($_POST['sort'])? $_POST['sort'] : 'surname, preferredName';
    $mode = !empty($_POST['mode'])? $_POST['mode'] : 'endofday';

    // Get the roll groups - revert to All if it's selected
    $rollGroups = !empty($_POST['gibbonRollGroupID'])? $_POST['gibbonRollGroupID'] : array('all');
    if (in_array('all', $rollGroups)) $rollGroups = array('all');

    require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    if (isset($_POST['types']) && isset($_POST['dateStart'])) {
        $types = $_POST['types'];
    } else {
        if (!isset($_POST['dateStart'])) {
            $types = array_keys( $attendance->getAttendanceTypes() );
            unset($types[0]);
        } else {
            $types = array();
        }
    }

    $reasons = (isset($_POST['reasons']))? $_POST['reasons'] : array();

    // Options & Filters
    $form = Form::create('attendanceTrends', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_graph_byType.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateStart')->setValue(dateConvertBack($guid, $dateStart))->isRequired();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateEnd')->setValue(dateConvertBack($guid, $dateEnd))->isRequired();

    $typeOptions = array_column($attendance->getAttendanceTypes(), 'name');
    $row = $form->addRow();
        $row->addLabel('types', __('Types'));
        $row->addSelect('types')->fromArray($typeOptions)->selectMultiple()->selected($types);

    $reasonOptions = $attendance->getAttendanceReasons();
    $row = $form->addRow();
        $row->addLabel('reasons', __('Reasons'));
        $row->addSelect('reasons')->fromArray($reasonOptions)->selectMultiple()->selected($reasons);

    $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addSelect('mode')->fromArray( array('endofday' => __('End of Day Only'), 'all' => __('All Attendance Logs')) )->selected($mode);

    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonRollGroupID as value, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY LENGTH(name), name";
    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelect('gibbonRollGroupID')->fromArray(array('all' => __('All')))->fromQuery($pdo, $sql, $data)->selectMultiple()->selected($rollGroups);

    $form->addRow()->addSubmit();

    echo $form->getOutput();


    if ($dateStart != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        echo '<p><span class="small emphasis">'.__($guid, 'Click a legend item to toggle visibility.').'</span></p>';

        //Produce array of attendance data
        try {

            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd);
            $rollGroupJoin = '';

            // If any roll groups are selected, use a MySQL IN() on gibboNRollGroupID to determing the subset of students to select
            if ($rollGroups != array('all')) {
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $rollGroupString = implode(',', array_map(function ($str) { return intval($str); }, $rollGroups)); // Implode and cast to numbers only
                $rollGroupJoin = "JOIN gibbonStudentEnrolment e ON (l.gibbonPersonID=e.gibbonPersonID AND e.gibbonSchoolYearID=:gibbonSchoolYearID AND e.gibbonRollGroupID IN (".$rollGroupString."))";
            }


            if ($mode == 'endofday') {
                // End of Day filters attendance logs by the last timestamp for a student on a given day
                $sql = "SELECT c.name, l.reason, count(DISTINCT log.gibbonAttendanceLogPersonID) as count, l.date FROM gibbonAttendanceLogPerson l JOIN gibbonAttendanceCode c ON (l.type=c.name) INNER JOIN (SELECT gibbonAttendanceLogPersonID, MAX(timestampTaken) FROM gibbonAttendanceLogPerson WHERE date>=:dateStart AND date<=:dateEnd GROUP BY gibbonPersonID, date ORDER BY timestampTaken DESC) log ON (l.gibbonAttendanceLogPersonID = log.gibbonAttendanceLogPersonID) ".$rollGroupJoin." WHERE l.date>=:dateStart AND l.date<=:dateEnd GROUP BY l.date, c.name, l.reason ORDER BY l.date, c.direction DESC, c.name";
            } else {
                // Count all records
                $sql = "SELECT c.name, l.reason, count(DISTINCT l.gibbonPersonID) as count, l.date FROM gibbonAttendanceLogPerson l JOIN gibbonAttendanceCode c ON (l.type=c.name) ".$rollGroupJoin." WHERE l.date>=:dateStart AND l.date<=:dateEnd GROUP BY l.date, c.name, l.reason ORDER BY l.date, c.direction DESC, c.name";
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

            $rows = $result->fetchAll();
            $days = getDateRange( $guid, $connection2, $dateStart, $dateEnd );

            $data = array();

            foreach ($types as $type) {
                foreach ($days as $date) {
                    $data[ $type ][ $date ] = 0;
                }
            }
            foreach ($rows as $row) {
                if ( isset($data[ $row['name'] ][ $row['date'] ]) ) {
                    $data[ $row['name'] ][ $row['date'] ] += $row['count'];
                }

            }

            foreach ($reasons as $reason) {
                if ($reason == '') continue;
                foreach ($days as $date) {
                    $data[ $reason ][ $date ] = 0;
                }
            }

            foreach ($rows as $row) {
                if ( isset($data[ $row['reason'] ][ $row['date'] ]) ) {
                    $data[ $row['reason'] ][ $row['date'] ] += $row['count'];
                }

            }

            // print '<pre>';
            // print_r($data);
            // print '<pre>';

            //PLOT DATA
            echo '<script type="text/javascript" src="'.$_SESSION[$guid]['absoluteURL'].'/lib/Chart.js/2.0/Chart.bundle.min.js"></script>';

            echo '<div style="width:100%">';
            echo '<div>';
            echo '<canvas id="canvas"></canvas>';
            echo '</div>';
            echo '</div>';

            $colors = getColourArray();
            $colorCount = count($colors);
            ?>
            <script>
            var chartData = {

                labels: [
                    <?php
                        foreach ( $days as $date) {
                            echo "'".date('M j', strtotime($date) )."',";
                        }
                    ?>
                ],
                datasets: [
                    <?php
                    $datasetCount = 0;
                    foreach ($data as $typeName => $dates) :
                    ?>
                    {
                        label: "<?php echo $typeName; ?>",

                        fill: false,
                        backgroundColor: "<?php echo 'rgba('.$colors[ $datasetCount % $colorCount ].',1)'; ?>",
                        borderColor: "<?php echo 'rgba('.$colors[ $datasetCount % $colorCount ].',1)'; ?>",
                        pointBackgroundColor: "<?php echo 'rgba('.$colors[ $datasetCount % $colorCount ].',1)'; ?>",
                        borderWidth: 1,
                        data: [
                        <?php
                            foreach ($dates as $dateName => $count) {
                                echo "'".$count."',";
                            }
                        ?>
                        ],
                    },
                    <?php
                    $datasetCount++;
                    endforeach;
                    ?>
                ]

            }

            window.onload = function(){
                var ctx = document.getElementById("canvas").getContext("2d");
                var myLineChart = new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options:
                        {
                            fill: false,
                            responsive: true,
                            showTooltips: true,
                            tooltips: {
                                mode: 'single',
                            },
                            hover: {
                                mode: 'dataset',
                                //onHover: function() { alert('Foo'); }
                            },
                            scales: {
                                xAxes: [{
                                    // type: 'time',
                                    // time: {
                                    //     displayFormats: {
                                    //        'day': 'MMM DD'
                                    //     }
                                    // },

                                    ticks: {
                                        autoSkip: true,
                                        maxRotation: 0,
                                        padding: 30,
                                    }
                                }]
                            }
                        }
                    }
                 );
            }
        </script>
        <?php

        }
    }
}
?>
