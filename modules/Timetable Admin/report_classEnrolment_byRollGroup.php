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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/report_classEnrolment_byRollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Class Enrolment by Roll Group').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Roll Group');
    echo '</h2>';

    $gibbonRollGroupID = '';
    if (isset($_GET['gibbonRollGroupID'])) {
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
    }
    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Roll Group') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonRollGroupID">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							if ($gibbonRollGroupID == $rowSelect['gibbonRollGroupID']) {
								echo "<option selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
							} else {
								echo "<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_classEnrolment_byRollGroup.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($gibbonRollGroupID != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
            $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Class Count');
        echo '</th>';
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
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo "<a href='index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=".$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
            echo '</td>';
            echo '<td>';
            try {
                $dataCount = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlCount = "SELECT * FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND role='Student' AND gibbonSchoolYearID=:gibbonSchoolYearID";
                $resultCount = $connection2->prepare($sqlCount);
                $resultCount->execute($dataCount);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultCount->rowCount() >= 0) {
                echo $resultCount->rowCount();
            } else {
                echo '<i>'.__($guid, 'NA').'</i>';
            }
            echo '</td>';
            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=3>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>