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

if (isActionAccessible($guid, $connection2, '/modules/Planner/scopeAndSequence.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Scope And Sequence').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Course');
    echo '</h2>';

    $gibbonCourseIDs = array();
    if (isset($_GET['gibbonCourseIDs'])) {
        $gibbonCourseIDs = $_GET['gibbonCourseIDs'];
    }
    ?>

	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Course') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
				</td>
				<td class="right">
					<select multiple class="standardWidth" name="gibbonCourseIDs[]" style="height: 150px">
						<?php
                        $currentDepartment = '';
						$lastDepartment = '';

						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = "SELECT gibbonCourse.*, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' ORDER BY department, nameShort";
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							$currentDepartment = $rowSelect['department'];
							if (($currentDepartment != $lastDepartment) and $currentDepartment != '') {
								echo "<optgroup label='--".$currentDepartment."--'>";
							}

                            $selected = '';
                            foreach ($gibbonCourseIDs as $gibbonCourseID) {
    							if ($gibbonCourseID == $rowSelect['gibbonCourseID']) {
    								$selected = 'selected';
    							}
                            }
                            echo "<option $selected value='".$rowSelect['gibbonCourseID']."'>".htmlPrep($rowSelect['name']).'</option>';
							$lastDepartment = $rowSelect['department'];
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/scopeAndSequence.php">
					<?php echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/scopeAndSequence.php'>".__($guid, 'Clear Filters').'</a> ';?>
                    <input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if (count($gibbonCourseIDs) > 0) {
        //Set up for edit access
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units.php', $connection2);
        $departments = array();
        if ($highestAction == 'Unit Planner_learningAreas') {
            $departmentCount = 1 ;
            try {
                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlSelect = "SELECT gibbonDepartment.gibbonDepartmentID FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonDepartment.name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { echo $e->getMessage(); }
            while ($rowSelect = $resultSelect->fetch()) {
                $departments[$departmentCount] = $rowSelect['gibbonDepartmentID'];
                $departmentCount ++;
            }
        }


        //Cycle through courses
        foreach ($gibbonCourseIDs as $gibbonCourseID) {
            //Check course exists
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseID' => $gibbonCourseID);
                $sql = "SELECT gibbonCourse.*, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' AND gibbonCourseID=:gibbonCourseID ORDER BY department, nameShort";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();

                //Can this course's units be edited?
                $canEdit = false ;
                if ($highestAction == 'Unit Planner_all') {
                    $canEdit = true ;
                }
                else if ($highestAction == 'Unit Planner_learningAreas') {
                    foreach ($departments AS $department) {
                        if ($department == $row['gibbonDepartmentID']) {
                            $canEdit = true ;
                        }
                    }
                }

                echo '<h2 class=\'bigTop\'>';
                echo $row['name'].' - '.$row['nameShort'];
                echo '</h2>';

                try {
                    $dataUnit = array('gibbonCourseID' => $gibbonCourseID);
                    $sqlUnit = 'SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment, tags FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnit.gibbonCourseID=:gibbonCourseID AND active=\'Y\' AND map=\'Y\' ORDER BY ordering, name';
                    $resultUnit = $connection2->prepare($sqlUnit);
                    $resultUnit->execute($dataUnit);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultUnit->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                }
                else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th style=\'width: 15%\'>';
                    echo __($guid, 'Unit');
                    echo '</th>';
                    echo '<th style=\'width: 45%\'>';
                    echo __($guid, 'Description');
                    echo '</th>';
                    echo "<th style=\'width: 30%\'>";
                    echo __($guid, 'Concepts & Keywords');
                    echo '</th>';
                    echo "<th style='width: 10%'>";
                    echo __($guid, 'Actions');
                    echo '</th>';
                    echo '</tr>';


                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowUnit = $resultUnit->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $rowUnit['name'].'<br/>';
                        echo '</td>';
                        echo '<td>';
                        echo $rowUnit['description'].'<br/>';
                        if ($rowUnit['attachment'] != '') {
                            echo "<br/><br/><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowUnit['attachment']."'>".__($guid, 'Download Unit Outline').'</a></li>';
                        }
                        echo '</td>';
                        echo '<td>';
                        $tags = explode(',', $rowUnit['tags']);
                        $tagsOutput = '' ;
                        foreach ($tags as $tag) {
                            $tagsOutput .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/conceptExplorer.php&tag=$tag'>".$tag.'</a>, ';
                        }
                        if ($tagsOutput != '')
                            $tagsOutput = substr($tagsOutput, 0, -2);
                        echo $tagsOutput;
                        echo '</td>';
                        echo '<td>';
                            if ($canEdit) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units_edit.php&gibbonUnitID=".$rowUnit['gibbonUnitID']."&gibbonCourseID=".$row['gibbonCourseID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/units_dump.php&gibbonCourseID=".$row['gibbonCourseID']."&gibbonUnitID=".$rowUnit['gibbonUnitID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__($guid, 'Export')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                            }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }
        }
    }
}
?>
