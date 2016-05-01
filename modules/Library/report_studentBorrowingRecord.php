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

$_SESSION[$guid]['report_student_emergencySummary.php_choices'] = '';

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_studentBorrowingRecord.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Borrowing Record').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Student');
    echo '</h2>';

    $gibbonPersonID = null;
    if (isset($_POST['gibbonPersonID'])) {
        $gibbonPersonID = $_POST['gibbonPersonID'];
    }
    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_studentBorrowingRecord.php'?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Students') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
						<option value=''></value>
						<?php
                        try {
                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['name']).')</option>';
    }
    ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="submit" value="<?php echo __($guid, 'Submit');
    ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($gibbonPersonID != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        $output = getBorrowingRecord($guid, $connection2, $gibbonPersonID);
        if ($output == false) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo $output;
        }
    }
}
?>