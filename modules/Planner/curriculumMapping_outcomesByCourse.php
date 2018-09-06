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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/curriculumMapping_outcomesByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Outcomes By Course').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This view gives an overview of which whole school and learning area outcomes are covered by classes in a given course, allowing for curriculum mapping by outcome and course.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Course');
    echo '</h2>';

    $gibbonCourseID = isset($_GET['gibbonCourseID'])? $_GET['gibbonCourseID'] : null;

	$form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

	$form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/curriculumMapping_outcomesByCourse.php');

	
	$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
	$sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' ORDER BY department, gibbonCourse.nameShort";
	$result = $pdo->executeQuery($data, $sql);

	$courses = ($result->rowCount() > 0)? $result->fetchAll() : array();
	$courses = array_reduce($courses, function($group, $item) {
		$group['--'.$item['department'].'--'][$item['gibbonCourseID']] = $item['name'];
		return $group;
	}, array());

	$row = $form->addRow();
		$row->addLabel('gibbonCourseID', __('Course'));
		$row->addSelect('gibbonCourseID')->fromArray($courses)->isRequired()->selected($gibbonCourseID)->placeholder();

	$row = $form->addRow();
		$row->addSearchSubmit($gibbon->session, __('Clear Filters'));

	echo $form->getOutput();

    if ($gibbonCourseID != '') {
        echo '<h2>';
        echo __($guid, 'Outcomes');
        echo '</h2>';

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
            //Get classes in this course
            try {
                $dataClasses = array('gibbonCourseID' => $gibbonCourseID);
                $sqlClasses = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name';
                $resultClasses = $connection2->prepare($sqlClasses);
                $resultClasses->execute($dataClasses);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultClasses->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $classCount = $resultClasses->rowCount();
                $classes = $resultClasses->fetchAll();

                //GET ALL OUTCOMES MET IN THIS COURSE, AND STORE IN AN ARRAY FOR DB-EFFICIENT USE IN TABLE
                try {
                    $dataOutcomes = array('gibbonCourseID1' => $gibbonCourseID, 'gibbonCourseID2' => $gibbonCourseID);
                    $sqlOutcomes = "(SELECT 'Unit' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonUnitOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN gibbonUnit ON (gibbonUnitOutcome.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID1 AND gibbonOutcome.active='Y' AND running='Y')
					UNION ALL
					(SELECT 'Working Block' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonOutcomeIDList LIKE concat('%', gibbonOutcome.gibbonOutcomeID, '%')) JOIN gibbonUnitClass ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID2 AND gibbonOutcome.active='Y' AND running='Y')
					UNION ALL
					(SELECT 'Planner Entry' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonPlannerEntryOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryOutcome.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID2 AND gibbonOutcome.active='Y')";
                    $resultOutcomes = $connection2->prepare($sqlOutcomes);
                    $resultOutcomes->execute($dataOutcomes);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $allOutcomes = $resultOutcomes->fetchAll();

                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Category');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Outcome');
                echo '</th>';
                foreach ($classes as $class) {
                    echo '<th colspan=2>';
                    echo $row['nameShort'].'.'.__($guid, $class['nameShort']);
                    echo '</th>';
                }
                echo '</tr>';
                echo "<tr class='head'>";
                echo '<th>';

                echo '</th>';
                echo '<th>';

                echo '</th>';
                foreach ($classes as $class) {
                    echo '<th>';
                    echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Unit').'</span>';
                    echo '</th>';
                    echo '<th>';
                    echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Lesson').'</span>';
                    echo '</th>';
                }
                echo '</tr>';

                    //Prep where for year group matching of outcomes to course
					$where = '';
					$yearGroups = explode(',', $row['gibbonYearGroupIDList']);
					foreach ($yearGroups as $yearGroup) {
						$where .= " AND gibbonYearGroupIDList LIKE concat('%', $yearGroup, '%')";
					}

				//SCHOOL OUTCOMES
				echo "<tr class='break'>";
                echo '<td colspan='.(($classCount * 2) + 2).'>';
                echo '<h4>'.__($guid, 'School Outcomes').'</h4>';
                echo '</td>';
                echo '</tr>';
                try {
                    $dataOutcomes = array();
                    $sqlOutcomes = "SELECT * FROM gibbonOutcome WHERE scope='School' AND active='Y' $where ORDER BY category, name";
                    $resultOutcomes = $connection2->prepare($sqlOutcomes);
                    $resultOutcomes->execute($dataOutcomes);
                } catch (PDOException $e) {
                    echo '<tr>';
                    echo '<td colspan='.(($classCount * 2) + 2).'>';
                    echo "<div class='error'>".$e->getMessage().'</div>';
                    echo '</td>';
                    echo '</tr>';
                }

                if ($resultOutcomes->rowCount() < 1) {
                    echo '<tr>';
                    echo '<td colspan='.(($classCount * 2) + 2).'>';
                    echo "<div class='error'>".__($guid, 'There are no records to display.').'</div>';
                    echo '</td>';
                    echo '</tr>';
                } else {
                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowOutcomes = $resultOutcomes->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $rowOutcomes['category'];
                        echo '</td>';
                        echo '<td>';
                        echo $rowOutcomes['name'];
                        echo '</td>';

						//Deal with outcomes
						foreach ($classes as $class) {
							echo '<td>';
							$outcomeCount = 0;
							foreach ($allOutcomes as $anOutcome) {
								if ($anOutcome['type'] == 'Unit' and $anOutcome['scope'] == 'School' and $anOutcome['gibbonOutcomeID'] == $rowOutcomes['gibbonOutcomeID'] and $class['gibbonCourseClassID'] == $anOutcome['gibbonCourseClassID']) {
									++$outcomeCount;
								}
							}
							if ($outcomeCount < 1) {
								echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
							} else {
								echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> x ".$outcomeCount;
							}
							echo '</td>';
							echo '<td>';
							$outcomeCount = 0;
							foreach ($allOutcomes as $anOutcome) {
								if ($anOutcome['type'] != 'Unit' and $anOutcome['scope'] == 'School' and $anOutcome['gibbonOutcomeID'] == $rowOutcomes['gibbonOutcomeID'] and $class['gibbonCourseClassID'] == $anOutcome['gibbonCourseClassID']) {
									++$outcomeCount;
								}
							}
							if ($outcomeCount < 1) {
								echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
							} else {
								echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> x ".$outcomeCount;
							}
							echo '</td>';
						}
                        echo '</tr>';
                    }
                }

                    //LEARNING AREA OUTCOMES
                    echo "<tr class='break'>";
					echo '<td colspan='.(($classCount * 2) + 2).'>';
					echo '<h4>'.sprintf(__($guid, '%1$s Outcomes'), $row['department']).'</h4>';
					echo '</td>';
					echo '</tr>';
					try {
						$dataOutcomes = array('gibbonDepartmentID' => $row['gibbonDepartmentID']);
						$sqlOutcomes = "SELECT * FROM gibbonOutcome WHERE scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID AND active='Y' $where ORDER BY category, name";
						$resultOutcomes = $connection2->prepare($sqlOutcomes);
						$resultOutcomes->execute($dataOutcomes);
					} catch (PDOException $e) {
						echo '<tr>';
						echo '<td colspan='.(($classCount * 2) + 2).'>';
						echo "<div class='error'>".$e->getMessage().'</div>';
						echo '</td>';
						echo '</tr>';
					}

					if ($resultOutcomes->rowCount() < 1) {
						echo '<tr>';
						echo '<td colspan='.(($classCount * 2) + 2).'>';
						echo "<div class='error'>".__($guid, 'There are no records to display.').'</div>';
						echo '</td>';
						echo '</tr>';
					} else {
						$count = 0;
						$rowNum = 'odd';
						while ($rowOutcomes = $resultOutcomes->fetch()) {
							if ($count % 2 == 0) {
								$rowNum = 'even';
							} else {
								$rowNum = 'odd';
							}
							++$count;

							//COLOR ROW BY STATUS!
							echo "<tr class=$rowNum>";
							echo '<td>';
							echo $rowOutcomes['category'];
							echo '</td>';
							echo '<td>';
							echo $rowOutcomes['name'];
							echo '</td>';

							//Deal with outcomes
							foreach ($classes as $class) {
								echo '<td>';
								$outcomeCount = 0;
								foreach ($allOutcomes as $anOutcome) {
									if ($anOutcome['type'] == 'Unit' and $anOutcome['scope'] == 'Learning Area' and $anOutcome['gibbonOutcomeID'] == $rowOutcomes['gibbonOutcomeID'] and $class['gibbonCourseClassID'] == $anOutcome['gibbonCourseClassID']) {
										++$outcomeCount;
									}
								}
								if ($outcomeCount < 1) {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
								} else {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> x ".$outcomeCount;
								}
								echo '</td>';
								echo '<td>';
								$outcomeCount = 0;
								foreach ($allOutcomes as $anOutcome) {
									if ($anOutcome['type'] != 'Unit' and $anOutcome['scope'] == 'Learning Area' and $anOutcome['gibbonOutcomeID'] == $rowOutcomes['gibbonOutcomeID'] and $class['gibbonCourseClassID'] == $anOutcome['gibbonCourseClassID']) {
										++$outcomeCount;
									}
								}
								if ($outcomeCount < 1) {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
								} else {
									echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> x ".$outcomeCount;
								}
								echo '</td>';
							}

                        echo '</tr>';
                    }
                }
                echo '</table>';
            }
        }
    }
}
?>
