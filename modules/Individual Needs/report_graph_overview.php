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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/report_graph_overview.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Individual Needs Overview').'</div>';
    echo '</div>';

    //PLOT DATA
    echo '<script type="text/javascript" src="'.$_SESSION[$guid]['absoluteURL'].'/lib/Chart.js/2.0/Chart.bundle.min.js"></script>';

    echo '<div style="width:100%">';
    echo '<div>';
    echo '<canvas id="canvas"></canvas>';
    echo '</div>';
    echo '</div>';

    $colors = ['153, 102, 255', '255, 99, 132', '54, 162, 235', '255, 206, 86', '75, 192, 192', '255, 159, 64', '152, 221, 95'];
    $colorCount = count($colors);

    $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';

    if (!empty($gibbonYearGroupID)) {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "SELECT gibbonRollGroup.name as labelName, gibbonRollGroup.gibbonRollGroupID as labelID, COUNT(DISTINCT gibbonStudentEnrolment.gibbonPersonID) as studentCount, COUNT(DISTINCT gibbonINPersonDescriptor.gibbonPersonID) as inCount
                FROM gibbonStudentEnrolment
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                LEFT JOIN gibbonINPersonDescriptor ON (gibbonINPersonDescriptor.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonPerson.status='Full'
                GROUP BY gibbonRollGroup.gibbonRollGroupID
                ORDER BY gibbonYearGroup.sequenceNumber";
    } else {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonYearGroup.name as labelName, gibbonYearGroup.gibbonYearGroupID as labelID, COUNT(DISTINCT gibbonStudentEnrolment.gibbonPersonID) as studentCount, COUNT(DISTINCT gibbonINPersonDescriptor.gibbonPersonID) as inCount
                FROM gibbonStudentEnrolment
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                LEFT JOIN gibbonINPersonDescriptor ON (gibbonINPersonDescriptor.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonPerson.status='Full'
                GROUP BY gibbonYearGroup.gibbonYearGroupID
                ORDER BY gibbonYearGroup.sequenceNumber";
    }

    

    $chartData = $pdo->select($sql, $data)->fetchAll();

    ?>
    <script>

    var chartData = {

        labels: [
            <?php
                foreach ($chartData as $row) {
                    echo "'".$row['labelName']."',";
                }
            ?>
        ],
        datasets: [
            
            {
                label: "Total Students",
                // backgroundColor: "blue",
                backgroundColor: "<?php echo 'rgba('.$colors[0].',1)'; ?>",
                data: [
                    <?php
                        foreach ($chartData as $row) {
                            echo "".$row['studentCount'].",";
                        }
                    ?>
                ]
            },
            {
                label: "Individual Needs",
                // backgroundColor: "red",
                backgroundColor: "<?php echo 'rgba('.$colors[1].',1)'; ?>",
                data: [
                    <?php
                        foreach ($chartData as $row) {
                            echo "".$row['inCount'].",";
                        }
                    ?>
                ]
            },
        ],

        ids: [
            <?php
                foreach ($chartData as $row) {
                    echo "'".$row['labelID']."',";
                }
            ?>
        ],

    };

    window.onload = function(){
        var ctx = document.getElementById("canvas").getContext("2d");
        var myLineChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options:
                {
                    fill: false,
                    responsive: true,
                    showTooltips: true,
                    tooltips: {
                        mode: 'label',
                    },
                    hover: {
                        mode: 'single',
                        onHover: function(elements) {
                            if (elements.length) document.body.style.cursor = 'pointer';
                            else document.body.style.cursor = 'default';
                        },
                    },
                    onClick: function(event, elements) {
                        var index = elements[0]._index;
                        window.location = '<?php 
                        if (!empty($gibbonYearGroupID)) {
                            echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/in_summary.php&gibbonRollGroupID='; 
                        } else {
                            echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Individual Needs/report_graph_overview.php&gibbonYearGroupID='; 
                        }
                        ?>' + chartData.ids[index];
                    },
                }
            }
        );
    }
    </script>
    <?php
    
}
