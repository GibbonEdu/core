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

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_letters.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Behaviour Letters').'</div>';
    echo '</div>';

    $gibbonPersonID = null;
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }

    echo '<h3>';
    echo __($guid, 'Filter');
    echo '</h3>';
    echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_letters.php'>";
    echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>"; ?>
			<tr>
				<td> 
					<b><?php echo __($guid, 'Student') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
						<option value=""></option>
						<?php
                        try {
                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
						while ($rowSelect = $resultSelect->fetch()) {
							if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
								echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							} else {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							}
						}
						?>			
					</select>
				</td>
			</tr>
			<?php

            echo '<tr>';
				echo "<td class='right' colspan=2>";
				echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
				echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_letters.php'>".__($guid, 'Clear Filters').'</a> ';
				echo "<input type='submit' value='".__($guid, 'Go')."'>";
				echo '</td>';
			echo '</tr>';
    	echo '</table>';
    echo '</form>';

    echo '<h3>';
    echo __($guid, 'Behaviour Records');
    echo '</h3>';
    echo '<p>';
    echo __($guid, 'This interface displays automated behaviour letters that have been issued within the current school year.');
    echo '</p>';
    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'AND ';
        if ($gibbonPersonID != '') {
            $data['gibbonPersonID'] = $gibbonPersonID;
            $sqlWhere .= 'gibbonBehaviourLetter.gibbonPersonID=:gibbonPersonID AND ';
        }
        if ($sqlWhere == 'AND ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $data['gibbonSchoolYearID2'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $sql = "SELECT gibbonBehaviourLetter.*, surname, preferredName FROM gibbonBehaviourLetter JOIN gibbonPerson ON (gibbonBehaviourLetter.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourLetter.gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere ORDER BY timestamp DESC";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Student').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date').'<span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Letter');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo "<th style='min-width: 70px'>";
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID']."&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'<span>';
            echo '</td>';
            echo '<td>';
            echo $row['letterLevel'];
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            echo "<script type='text/javascript'>";
            echo '$(document).ready(function(){';
            echo "\$(\".comment-$count\").hide();";
            echo "\$(\".show_hide-$count\").fadeIn(1000);";
            echo "\$(\".show_hide-$count\").click(function(){";
            echo "\$(\".comment-$count\").fadeToggle(1000);";
            echo '});';
            echo '});';
            echo '</script>';
            if ($row['body'] != '' or $row['recipientList'] != '') {
                echo "<a title='".__($guid, 'View Details')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'View Details')."' onclick='return false;' /></a>";
            }
            echo '</td>';
            echo '</tr>';
            if ($row['body'] != '' or $row['recipientList'] != '') {
                echo "<tr class='comment-$count' id='comment-$count'>";
                echo '<td colspan=4>';
                if ($row['body'] != '') {
                    echo '<b>'.__($guid, 'Letter Body').'</b><br/>';
                    echo nl2brr($row['body']).'<br/><br/>';
                }
                if ($row['recipientList'] != '') {
                    echo '<b>'.__($guid, 'Recipients').'</b><br/>';
                    $reipients = explode(',', $row['recipientList']);
                    foreach ($reipients as $reipient) {
                        echo trim($reipient).'<br/>';
                    }
                }
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type");
        }
    }
}
?>