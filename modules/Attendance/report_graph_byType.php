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

    $sort = !empty($_POST['sort'])? $_POST['sort'] : 'surname, preferredName';

    
    require_once './modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);

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

    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php?q=/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_graph_byType.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
					if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
						echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					?></span>
				</td>
				<td class="right">
                    <input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $dateStart) ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dateStart=new LiveValidation('dateStart');
                        dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } ); 
                        dateStart.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dateStart" ).datepicker();
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, 'End Date') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
                </td>
                <td class="right">
                    <input name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $dateEnd) ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dateEnd=new LiveValidation('dateEnd');
                        dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } ); 
                        dateEnd.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dateEnd" ).datepicker();
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <td> 
                    <b><?php echo __($guid, 'Types') ?></b><br/>
                    <span class="emphasis small"></span>
                </td>
                <td class="right">
                    <select multiple name="types[]" id="types[]" style="width: 302px; height: 100px">
                        <?php
                        $typeOptions = $attendance->getAttendanceTypes();
                        foreach ($typeOptions as $type) {

                            $selected = ( in_array($type['name'], $types) )? 'selected' : '';
                        

                            echo "<option $selected value='".$type['name']."'>".htmlPrep(__($guid, $type['name'] )).'</option>';
                        }
                        ?>          
                    </select>
                </td>
            </tr>
            <tr>
                <td> 
                    <b><?php echo __($guid, 'Reasons') ?></b><br/>
                    <span class="emphasis small"></span>
                </td>
                <td class="right">
                    <select multiple name="reasons[]" id="reasons[]" style="width: 302px; height: 100px">
                        <?php
                        $reasonOptions = $attendance->getAttendanceReasons();
                        foreach ($reasonOptions as $reason) {
                            if (empty($reason)) continue;
                            $selected = ( in_array($reason, $reasons) )? 'selected' : '';
                        

                            echo "<option $selected value='".$reason."'>".htmlPrep(__($guid, $reason )).'</option>';
                        }
                        ?>          
                    </select>
                </td>
            </tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="address" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_graph_byType.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($dateStart != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        echo '<p><span class="small emphasis">'.__($guid, 'Click a legend item to toggle visibility.').'</span></p>';

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd);
            $sql = "SELECT c.name, l.reason, count( DISTINCT l.gibbonPersonID) as count, l.date FROM gibbonAttendanceLogPerson l JOIN gibbonAttendanceCode c ON (l.type=c.name) WHERE l.date>=:dateStart AND l.date<=:dateEnd GROUP BY l.date, c.name, l.reason ORDER BY l.date, c.direction DESC, c.name";
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
                    <? 
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