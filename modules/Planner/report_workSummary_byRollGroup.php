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

if (isActionAccessible($guid, $connection2, '/modules/Planner/report_workSummary_byRollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Work Summary by Roll Group').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'This report draws data from the Markbook, Planner and Behaviour modules to give an overview of student performance and work completion. It only counts Online Submission data when submission is set to compulsory.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Choose Roll Group');
    echo '</h2>';

    $gibbonRollGroupID = null;
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
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_workSummary_byRollGroup.php">
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
            $sql = "SELECT surname, preferredName, name, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Satisfactory');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Unsatisfactory');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Late');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Incomplete');
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
            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID']."&subpage=Homework'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
            echo '</td>';
            echo "<td style='width:15%'>";
            try {
                $dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentConcern='N' AND effortConcern='N') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y'";
                $resultData = $connection2->prepare($sqlData);
                $resultData->execute($dataData);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultData->rowCount() < 1) {
                echo '0';
            } else {
                echo $resultData->rowCount();
            }
            echo '</td>';
            echo "<td style='width:15%'>";
                        //Count up unsatisfactory from markbook
                        try {
                            $dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentConcern='Y' OR effortConcern='Y') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y'";
                            $resultData = $connection2->prepare($sqlData);
                            $resultData->execute($dataData);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
            $dataData2 = array();
            $sqlWhere = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere .= ' AND ';
                    }
                    $dataData2['data2'.$rowData['gibbonPlannerEntryID']] = $rowData['gibbonPlannerEntryID'];
                    $sqlWhere .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data2'.$rowData['gibbonPlannerEntryID'];
                    ++$countWhere;
                }
            }
            if ($countWhere > 0) {
                $sqlWhere .= ' OR gibbonBehaviour.gibbonPlannerEntryID IS NULL';
            }
            $sqlWhere .= ')';
            if ($sqlWhere == ' AND ()') {
                $sqlWhere = '';
            }

			//Count up unsatisfactory from behaviour, counting out $sqlWhere
			try {
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$sqlData2 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Unacceptable' OR descriptor='Homework - Unacceptable') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

            if (($resultData->rowCount() + $resultData2->rowCount()) < 1) {
                echo '0';
            } else {
                echo $resultData->rowCount() + $resultData2->rowCount();
            }
            echo '</td>';
            echo "<td style='width:15%'>";
			//Count up lates in markbook
			try {
				$dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
				$sqlData = "SELECT DISTINCT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentValue='Late' OR effortValue='Late') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y'";
				$resultData = $connection2->prepare($sqlData);
				$resultData->execute($dataData);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

            $dataData2 = array();
            $dataData3 = array();
            $sqlWhere = '';
            $sqlWhere2 = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                $dataData2['data2'.$rowData['gibbonCourseClassID']] = $rowData['gibbonCourseClassID'];
                $sqlWhere .= ' AND NOT gibbonPlannerEntry.gibbonCourseClassID=:data2'.$rowData['gibbonCourseClassID'];
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere2 .= ' AND ';
                    }
                    $sqlWhere2 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID='.$rowData['gibbonPlannerEntryID'];
                    ++$countWhere;
                }
            }
            if ($countWhere > 0) {
                $sqlWhere2 .= ' OR gibbonBehaviour.gibbonPlannerEntryID IS NULL';
            }
            $sqlWhere2 .= ')';
            if ($sqlWhere2 == ' AND ()') {
                $sqlWhere2 = '';
            }

			//Count up lates in planner, counting out $sqlWhere
			try {
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$sqlData2 = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND status='Late' AND gibbonSchoolYearID=:gibbonSchoolYearID AND homeworkSubmissionRequired='Compulsory' $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

            $sqlWhere3 = ' AND (';
            $countWhere = 0;
            while ($rowData2 = $resultData2->fetch()) {
                if ($rowData2['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere3 .= ' AND ';
                    }
                    $dataData3['data3'.$rowData2['gibbonPlannerEntryID']] = $rowData2['gibbonPlannerEntryID'];
                    $sqlWhere3 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data3'.$rowData2['gibbonPlannerEntryID'];
                    ++$countWhere;
                }
            }
            if ($countWhere > 0) {
                $sqlWhere3 .= ' OR gibbonBehaviour.gibbonPlannerEntryID IS NULL';
            }
            $sqlWhere3 .= ')';
            if ($sqlWhere3 == ' AND ()') {
                $sqlWhere3 = '';
            }

			//Count up lates from behaviour, counting out $sqlWhere2 and $sqlWhere3
			try {
				$dataData3['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData3['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$sqlData3 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Late' OR descriptor='Homework - Late') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere2 $sqlWhere3";
				$resultData3 = $connection2->prepare($sqlData3);
				$resultData3->execute($dataData3);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}
			//Print out total late
			if (($resultData->rowCount() + $resultData2->rowCount() + $resultData3->rowCount()) < 1) {
				echo '0';
			} else {
				echo $resultData->rowCount() + $resultData2->rowCount() + $resultData3->rowCount();
			}
            echo '</td>';
            echo "<td style='width:15%'>";
			//Count up incompletes in markbook
			try {
				$dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
				$sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentValue='Incomplete' OR effortValue='Incomplete') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y'";
				$resultData = $connection2->prepare($sqlData);
				$resultData->execute($dataData);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

            $dataData2 = array();
            $dataData3 = array();
            $dataData4 = array();
            $sqlWhere = '';
            $sqlWhere2 = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                $dataData2['data2'.$rowData['gibbonCourseClassID']] = $rowData['gibbonCourseClassID'];
                $sqlWhere .= ' AND NOT gibbonPlannerEntry.gibbonCourseClassID=:data2'.$rowData['gibbonCourseClassID'];
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere2 .= ' AND ';
                    }
                    $dataData4['data4'.$rowData['gibbonPlannerEntryID']] = $rowData['gibbonPlannerEntryID'];
                    $sqlWhere2 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data4'.$rowData['gibbonPlannerEntryID'];
                    ++$countWhere;
                }
            }
            if ($countWhere > 0) {
                $sqlWhere2 .= ' OR gibbonBehaviour.gibbonPlannerEntryID IS NULL';
            }
            $sqlWhere2 .= ')';
            if ($sqlWhere2 == ' AND ()') {
                $sqlWhere2 = '';
            }

			//Count up incompletes in planner, counting out $sqlWhere
			try {
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$dataData2['homeworkDueDateTime'] = date('Y-m-d H:i:s');
				$dataData2['date'] = date('Y-m-d');
				$sqlData2 = "SELECT * FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND homeworkSubmission='Y' AND homeworkDueDateTime<:homeworkDueDateTime AND homeworkSubmissionRequired='Compulsory' AND date<=:date $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

            $countIncomplete = 0;
            $sqlWhere3 = ' AND (';
            $countWhere = 0;
            while ($rowData2 = $resultData2->fetch()) {
                try {
                    $dataData3['gibbonPersonID'] = $row['gibbonPersonID'];
                    $dataData3['gibbonPlannerEntryID'] = $rowData2['gibbonPlannerEntryID'];
                    $sqlData3 = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'";
                    $resultData3 = $connection2->prepare($sqlData3);
                    $resultData3->execute($dataData3);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultData3->rowCount() < 1) {
                    ++$countIncomplete;
                }
                if ($rowData2['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere3 .= ' AND ';
                    }
                    $dataData4['data4'.$rowData2['gibbonPlannerEntryID']] = $rowData2['gibbonPlannerEntryID'];
                    $sqlWhere3 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data4'.$rowData2['gibbonPlannerEntryID'];
                    ++$countWhere;
                }
            }
            if ($countWhere > 0) {
                $sqlWhere3 .= ' OR gibbonBehaviour.gibbonPlannerEntryID IS NULL';
            }
            $sqlWhere3 .= ')';
            if ($sqlWhere3 == ' AND ()') {
                $sqlWhere3 = '';
            }

			//Count up incompletes from behaviour, counting out $sqlWhere2 and $sqlWhere3
			try {
				$dataData4['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData4['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$sqlData4 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Incomplete' OR descriptor='Homework - Incomplete') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere2 $sqlWhere3";
				$resultData4 = $connection2->prepare($sqlData4);
				$resultData4->execute($dataData4);
			} catch (PDOException $e) {
				echo "<div class='error'>".$e->getMessage().'</div>';
			}

			//Print out total lates
			if (($resultData->rowCount() + $countIncomplete + $resultData4->rowCount() < 1)) {
				echo '0';
			} else {
				echo $resultData->rowCount() + $countIncomplete + $resultData4->rowCount();
			}
            echo '</td>';
            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=2>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>