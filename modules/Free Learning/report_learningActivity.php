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
along with this program. If not, see <http:// www.gnu.org/licenses/>.
*/

use Gibbon\View\View;
use Gibbon\Forms\Form;

// Module includes
include "./modules/" . $session->get('module') . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_learningActivity.php")==FALSE) {
    // Acess denied
    print "<div class='error'>" ;
        print __("You do not have access to this action.") ;
    print "</div>" ;
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Learning Activity'));

    $timePeriod = $_GET['timePeriod'] ?? 'Last 30 Days';

    $timePeriodLookup = [
        "Last 30 Days" => "30",
        "Last 60 Days" => "60",
        "Last 12 Months" => "12",
    ];

    //  FORM
	$form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
	$form->setTitle(__('Filter'));

	$form->setClass('noIntBorder w-full');
	$form->addHiddenValue('q', '/modules/Free Learning/report_learningActivity.php');

    $timePeriods = [
        'Last 30 Days' => __m('Last 30 Days'),
        'Last 60 Days' => __m('Last 60 Days'),
        'Last 12 Months' => __m('Last 12 Months'),
    ];
    $row = $form->addRow();
        $row->addLabel('timePeriod', __m('Time Period'));
        $row->addSelect('timePeriod')->fromArray($timePeriods)->selected($timePeriod);

	$row = $form->addRow();
		$row->addSearchSubmit($session, __('Clear Filters'));

	echo $form->getOutput();

    if ($timePeriod != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<p>';
        echo __m('Figures for Complete - Pending, Complete - Approved and Evidence Not Yet Approved are calculated from only those units joined within the specified time period. Due to the possibility of multiple submissions for any given unit, a single unit joined may result in multiple other statuses.');
        echo '</p>';

        try {
            $data = array();
            if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                $sql = 'SELECT freeLearningUnitStudentID, timestampJoined, GROUP_CONCAT(timestamp) AS timestamps, GROUP_CONCAT(type) AS types FROM freeLearningUnitStudent LEFT JOIN gibbonDiscussion ON (gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND foreignTable=\'freeLearningUnitStudent\') WHERE timestampJoined>=DATE_SUB(NOW(), INTERVAL '.$timePeriodLookup[$timePeriod].' DAY) GROUP BY freeLearningUnitStudentID';
            } else if ($timePeriod == "Last 12 Months") {
                $sql = 'SELECT freeLearningUnitStudentID, timestampJoined, GROUP_CONCAT(timestamp) AS timestamps, GROUP_CONCAT(type) AS types FROM freeLearningUnitStudent LEFT JOIN gibbonDiscussion ON (gibbonDiscussion.foreignTableID=freeLearningUnitStudent.freeLearningUnitStudentID AND foreignTable=\'freeLearningUnitStudent\') WHERE timestampJoined>=DATE_SUB(NOW(), INTERVAL '.$timePeriodLookup[$timePeriod].' MONTH) GROUP BY freeLearningUnitStudentID';
            }
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
            $rows = $result->fetchAll();

            // PLOT DATA
            echo '<script type="text/javascript" src="'.$session->get('absoluteURL').'/lib/Chart.js/3.0/chart.min.js"></script>';
            echo "<p style='margin-top: 20px; margin-bottom: 5px'><b>".__('Data').'</b></p>';
            echo '<div style="width:100%">';
            echo '<div>';
            echo '<canvas id="canvas"></canvas>';
            echo '</div>';
            echo '</div>';
            ?>
            <script>
                var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
                var lineChartData = {
                    <?php
                    $countJoinedTotal = 0;
                    $countApprovedTotal = 0 ;
                    $countNYATotal = 0;
                    $countSubmittedTotal = 0;
                    
                    echo 'labels : [';
                        if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                            $days = array();
                            for($i = 0; $i < $timePeriodLookup[$timePeriod]; $i++) {
                                $countJoined = 0;
                                $countApproved = 0 ;
                                $countNYA = 0 ;
                                $countSubmitted = 0 ;
                                $d = date("d", strtotime('-'. $i .' days'));
                                $m = date("m", strtotime('-'. $i .' days'));
                                foreach ($rows as $row) {
                                    if (is_numeric(strpos($row['timestampJoined'], $m."-".$d))) {
                                        $countJoined++ ;
                                    }

                                    $count = 0;
                                    $timestamps =  explode(',', $row['timestamps']);
                                    $types =  explode(',', $row['types']);

                                    foreach ($timestamps as $timestamp) {
                                        $type = $types[$count];

                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Complete - Approved') {
                                            $countApproved++ ;
                                        }
                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Evidence Not Yet Approved') {
                                            $countNYA++ ;
                                        }
                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Complete - Pending') {
                                            $countSubmitted++ ;
                                        }

                                        $count ++;
                                    } 
                                }

                                $countJoinedTotal += $countJoined;
                                $countApprovedTotal += $countApproved;
                                $countNYATotal += $countNYA;
                                $countSubmittedTotal += $countSubmitted;

                                if ($i == 0) {
                                    array_unshift($days, array(0 => '(Today) '.$d.'/'.$m, 1 => $countJoined, 2 => $countApproved, 3 => $countNYA, 4 => $countSubmitted));
                                } else {
                                    array_unshift($days, array(0 => $d.'/'.$m, 1 => $countJoined, 2 => $countApproved, 3 => $countNYA, 4 => $countSubmitted));
                                }
                            }
                            $labels = '';
                            foreach ($days AS $day) {
                                $labels .= '"'.$day[0].'",';
                            }
                            echo substr($labels, 0, -1);
                        } else if ($timePeriod == "Last 12 Months") {
                            $months = array();
                            for($i = 0; $i < $timePeriodLookup[$timePeriod]; $i++) {
                                $countJoined = 0;
                                $countApproved = 0 ;
                                $countNYA = 0 ;
                                $countSubmitted = 0 ;
                                $m = date("m", strtotime('-'. $i .' months'));
                                $Y = date("Y", strtotime('-'. $i .' months'));
                                foreach ($rows as $row) {
                                    if (is_numeric(strpos($row['timestampJoined'], $Y."-".$m))) {
                                        $countJoined++ ;
                                    }

                                    $count = 0;
                                    $timestamps =  explode(',', $row['timestamps']);
                                    $types =  explode(',', $row['types']);

                                    foreach ($timestamps as $timestamp) {
                                        $type = $types[$count];

                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Complete - Approved') {
                                            $countApproved++ ;
                                        }
                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Evidence Not Yet Approved') {
                                            $countNYA++ ;
                                        }
                                        if (is_numeric(strpos($timestamp, $m."-".$d)) && $type == 'Complete - Pending') {
                                            $countSubmitted++ ;
                                        }

                                        $count ++;
                                    } 
                                }
                                
                                $countJoinedTotal += $countJoined;
                                $countApprovedTotal += $countApproved;
                                $countNYATotal += $countNYA;
                                $countSubmittedTotal += $countSubmitted;

                                if ($i == 0) {
                                    array_unshift($months, array(0 => '(Today) '.$m.'/'.$Y, 1 => $countJoined, 2 => $countApproved, 3 => $countNYA, 4 => $countSubmitted));
                                } else {
                                    array_unshift($months, array(0 => $m.'/'.$Y, 1 => $countJoined, 2 => $countApproved, 3 => $countNYA, 4 => $countSubmitted));
                                }
                            }
                            $labels = '';
                            foreach ($months AS $month) {
                                $labels .= '"'.$month[0].'",';
                            }
                            echo substr($labels, 0, -1);
                        }
                    echo '],';
                    ?>

                    datasets : [
                        {
                            label: "<?php echo __m("Units Joined") ?>",
                            backgroundColor : "rgba(186, 230, 253, 0.5)",
                            borderColor : "rgba(2, 132, 199, 1)",
                            hoverBorderColor : "rgba(2, 132, 199, 1)",
                            pointColor : "rgba(2, 132, 199, 1)",
                            pointBorderColor : "rgba(2, 132, 199, 1)",
                            pointBackgroundColor : "rgba(2, 132, 199, 1)",
                            lineTension: 0.3,
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                                    foreach ($days AS $day) {
                                        $data .= $day[1].',';
                                    }
                                } else if ($timePeriod == "Last 12 Months") {
                                    foreach ($months AS $month) {
                                        $data .= $month[1].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        },
                        {
                            label: "<?php echo __m("Complete - Pending") ?>",
                            backgroundColor : "rgb(220, 197, 244, 0.5)",
                            borderColor : "rgb(99, 63, 134)",
                            hoverBorderColor : "rgb(99, 63, 134)",
                            pointColor : "rgb(99, 63, 134)",
                            pointBorderColor : "rgb(99, 63, 134)",
                            pointBackgroundColor : "rgb(99, 63, 134)",
                            lineTension: 0.3,
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                                    foreach ($days AS $day) {
                                        $data .= $day[4].',';
                                    }
                                } else if ($timePeriod == "Last 12 Months") {
                                    foreach ($months AS $month) {
                                        $data .= $month[4].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        },
                        {
                            label: "<?php echo __m("Complete - Approved") ?>",
                            backgroundColor : "rgba(198, 246, 213, 0.5)",
                            borderColor : "rgb(47, 133, 90)",
                            hoverBorderColor : "rgb(47, 133, 90)",
                            pointColor : "rgb(47, 133, 90)",
                            pointBorderColor : "rgb(47, 133, 90)",
                            pointBackgroundColor : "rgb(47, 133, 90)",
                            lineTension: 0.3,
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                                    foreach ($days AS $day) {
                                        $data .= $day[2].',';
                                    }
                                } else if ($timePeriod == "Last 12 Months") {
                                    foreach ($months AS $month) {
                                        $data .= $month[2].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        },
                        {
                            label: "<?php echo __m("Evidence Not Yet Approved") ?>",
                            backgroundColor : "rgba(255, 210, 168, 0.5)",
                            borderColor : "rgb(212, 86, 2)",
                            hoverBorderColor : "rgb(212, 86, 2)",
                            pointColor : "rgb(212, 86, 2)",
                            pointBorderColor : "rgb(212, 86, 2)",
                            pointBackgroundColor : "rgb(212, 86, 2)",
                            lineTension: 0.3,
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == "Last 30 Days" OR $timePeriod == "Last 60 Days") {
                                    foreach ($days AS $day) {
                                        $data .= $day[3].',';
                                    }
                                } else if ($timePeriod == "Last 12 Months") {
                                    foreach ($months AS $month) {
                                        $data .= $month[3].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        }
                    ]
                }

                window.onload = function(){
                    var ctx = document.getElementById("canvas").getContext("2d");
                    window.myLine = new Chart(ctx, {
                        type: 'line',
                        data: lineChartData,
                        options: {
                            responsive: true,
                            spanGaps: true,
                            showTooltips: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                }
                            }
                        }
                    });
                }
            </script>
            <?php
            echo "<p class='text-right mt-4 text-xs'>";
                echo '<b>'.__m('Total Units Joined').'</b>: '.$countJoinedTotal.'<br/>';
                echo '<b>'.__m('Total Units Approved').'</b>: '.$countApprovedTotal.'<br/>';
            echo "</p>";
        }
    }
}
?>
