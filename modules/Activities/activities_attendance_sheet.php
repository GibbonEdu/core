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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance_sheet.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Printable Attendance Sheet').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Activity');
    echo '</h2>';

    $gibbonActivityID = null;
    if (isset($_GET['gibbonActivityID'])) {
        $gibbonActivityID = $_GET['gibbonActivityID'];
    }

    $numberOfColumns = (isset($_GET['columns'])) ? $_GET['columns'] : 15;
    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Activity')  ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonActivityID">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							$selected = '';
							if ($gibbonActivityID == $rowSelect['gibbonActivityID']) {
								$selected = 'selected';
							}
							echo "<option $selected value='".$rowSelect['gibbonActivityID']."'>".htmlPrep($rowSelect['name']).'</option>';
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Number of Columns')  ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<input type="text" name="columns" class="standardWidth" maxlength="2" value="15"/>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/activities_attendance_sheet.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($gibbonActivityID != '') {
        $output = '';
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
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
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/activities_attendance_sheetPrint.php&gibbonActivityID=$gibbonActivityID&columns=$numberOfColumns'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            $lastPerson = '';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo "<th colspan=$numberOfColumns>";
            echo __($guid, 'Attendance');
            echo '</th>';
            echo '</tr>';
            echo "<tr style='height: 75px' class='odd'>";
            echo "<td style='vertical-align:top; width: 120px'>Date</td>";
            for ($i = 1; $i <= $numberOfColumns; ++$i) {
                echo "<td style='color: #bbb; vertical-align:top; width: 15px'>$i</td>";
            }
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $count.'. '.formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                for ($i = 1; $i <= $numberOfColumns; ++$i) {
                    echo '<td></td>';
                }
                echo '</tr>';

                $lastPerson = $row['gibbonPersonID'];
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=16>';
                echo __($guid, 'There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>