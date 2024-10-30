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
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Work Summary by Form Group'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/report_workSummary_byFormGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    echo '<p>';
    echo __('This report draws data from the Markbook, Planner and Behaviour modules to give an overview of student performance and work completion. It only counts Online Submission data when submission is set to Required.');
    echo '</p>';

    echo '<h2>';
    echo __('Choose Form Group');
    echo '</h2>';

    $gibbonFormGroupID = isset($_GET['gibbonFormGroupID'])? $_GET['gibbonFormGroupID'] : null;

    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_workSummary_byFormGroup.php');

    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->required()->selected($gibbonFormGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if ($gibbonFormGroupID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        
            $data = array('gibbonFormGroupID' => $gibbonFormGroupID);
            $sql = "SELECT surname, preferredName, name, gibbonPerson.gibbonPersonID, gibbonPerson.dateStart FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Student');
        echo '</th>';
        echo '<th>';
        echo __('Satisfactory');
        echo '</th>';
        echo '<th>';
        echo __('Unsatisfactory');
        echo '</th>';
        echo '<th>';
        echo __('On Time');
        echo '</th>';
        echo '<th>';
        echo __('Late');
        echo '</th>';
        echo '<th>';
        echo __('Incomplete');
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
            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID']."&subpage=Homework'>".Format::name('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
            echo '</td>';
            echo "<td style='width:15%'>";
            
                $dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'dateStart' => $row['dateStart'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID  AND (attainmentConcern='N' OR attainmentConcern IS NULL) AND (effortConcern='N' OR effortConcern IS NULL) AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y' AND (:dateStart IS NULL OR completeDate >= :dateStart)";
                $resultData = $connection2->prepare($sqlData);
                $resultData->execute($dataData);

            if ($resultData->rowCount() < 1) {
                echo '0';
            } else {
                echo $resultData->rowCount();
            }
            echo '</td>';
            echo "<td style='width:15%'>";
            //Count up unsatisfactory from markbook
            
                $dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'dateStart' => $row['dateStart'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentConcern='Y' OR effortConcern='Y') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y' AND (:dateStart IS NULL OR completeDate >= :dateStart)";
                $resultData = $connection2->prepare($sqlData);
                $resultData->execute($dataData);
            $dataData2 = array();
            $sqlWhere = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere .= ' AND ';
                    }
                    $dataData2['data2'.$countWhere] = $rowData['gibbonPlannerEntryID'];
                    $sqlWhere .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data2'.$countWhere;
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
			
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$sqlData2 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Unacceptable' OR descriptor='Homework - Unacceptable') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);

            if (($resultData->rowCount() + $resultData2->rowCount()) < 1) {
                echo '0';
            } else {
                echo $resultData->rowCount() + $resultData2->rowCount();
            }
            echo '</td>';


            echo "<td style='width:15%'>";
			//Count up on time in planner
            
				$dataData['gibbonPersonID'] = $row['gibbonPersonID'];
                $dataData['dateStart'] = $row['dateStart'];
				$dataData['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$sqlData = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND status='On Time' AND gibbonSchoolYearID=:gibbonSchoolYearID AND homeworkSubmissionRequired='Required' AND (:dateStart IS NULL OR gibbonPlannerEntry.date >= :dateStart) ";
				$resultData = $connection2->prepare($sqlData);
				$resultData->execute($dataData);

			//Print out total on times
			if (($resultData->rowCount() < 1)) {
				echo '0';
			} else {
				echo $resultData->rowCount();
			}
            echo '</td>';




            echo "<td style='width:15%'>";
			//Count up lates in markbook
			
				$dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'dateStart' => $row['dateStart'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
				$sqlData = "SELECT DISTINCT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentValue='Late' OR effortValue='Late') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y' AND (:dateStart IS NULL OR completeDate >= :dateStart)";
				$resultData = $connection2->prepare($sqlData);
				$resultData->execute($dataData);

            $dataData2 = array();
            $dataData3 = array();
            $sqlWhere = '';
            $sqlWhere2 = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                $dataData2['data2i'.$countWhere] = $rowData['gibbonCourseClassID'];
                $sqlWhere .= ' AND NOT gibbonPlannerEntry.gibbonCourseClassID=:data2i'.$countWhere;
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere2 .= ' AND ';
                    }
                    $dataData2['data2pl'.$countWhere] = $rowData['gibbonPlannerEntryID'];
                    $sqlWhere2 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data2pl'.$countWhere;
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
			
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$sqlData2 = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND status='Late' AND gibbonSchoolYearID=:gibbonSchoolYearID AND homeworkSubmissionRequired='Required' $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);

            $sqlWhere3 = ' AND (';
            $countWhere = 0;
            while ($rowData2 = $resultData2->fetch()) {
                if ($rowData2['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere3 .= ' AND ';
                    }
                    $dataData3['data3i'.$countWhere] = $rowData2['gibbonPlannerEntryID'];
                    $sqlWhere3 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data3i'.$countWhere;
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
			
				$dataData3['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData3['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$sqlData3 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Late' OR descriptor='Homework - Late') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere2 $sqlWhere3";
				$resultData3 = $connection2->prepare($sqlData3);
				$resultData3->execute($dataData3);
			//Print out total late
			if (($resultData->rowCount() + $resultData2->rowCount() + $resultData3->rowCount()) < 1) {
				echo '0';
			} else {
				echo $resultData->rowCount() + $resultData2->rowCount() + $resultData3->rowCount();
			}
            echo '</td>';
            echo "<td style='width:15%'>";
			//Count up incompletes in markbook
			
				$dataData = array('gibbonPersonID' => $row['gibbonPersonID'], 'dateStart' => $row['dateStart'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
				$sqlData = "SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND (attainmentValue='Incomplete' OR effortValue='Incomplete') AND gibbonSchoolYearID=:gibbonSchoolYearID AND complete='Y' AND (:dateStart IS NULL OR completeDate >= :dateStart)";
				$resultData = $connection2->prepare($sqlData);
				$resultData->execute($dataData);

            $dataData2 = array();
            $dataData3 = array();
            $dataData4 = array();
            $sqlWhere = '';
            $sqlWhere2 = ' AND (';
            $countWhere = 0;
            while ($rowData = $resultData->fetch()) {
                $dataData2['data2i'.$countWhere] = $rowData['gibbonCourseClassID'];
                $sqlWhere .= ' AND NOT gibbonPlannerEntry.gibbonCourseClassID=:data2i'.$countWhere;
                if ($rowData['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere2 .= ' AND ';
                    }
                    $dataData4['data4pl'.$countWhere] = $rowData['gibbonPlannerEntryID'];
                    $sqlWhere2 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data4pl'.$countWhere;
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
			
				$dataData2['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData2['dateStart'] = $row['dateStart'];
				$dataData2['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$dataData2['homeworkDueDateTime'] = date('Y-m-d H:i:s');
				$dataData2['date'] = date('Y-m-d');
				$sqlData2 = "SELECT * FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND homeworkSubmission='Y' AND homeworkDueDateTime<:homeworkDueDateTime AND homeworkSubmissionRequired='Required' AND (:dateStart IS NULL OR gibbonPlannerEntry.date >= :dateStart) AND date<=:date $sqlWhere";
				$resultData2 = $connection2->prepare($sqlData2);
				$resultData2->execute($dataData2);

            $countIncomplete = 0;
            $sqlWhere3 = ' AND (';
            $countWhere = 0;
            while ($rowData2 = $resultData2->fetch()) {
                
                    $dataData3['gibbonPersonID'] = $row['gibbonPersonID'];
                    $dataData3['gibbonPlannerEntryID'] = $rowData2['gibbonPlannerEntryID'];
                    $sqlData3 = "SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'";
                    $resultData3 = $connection2->prepare($sqlData3);
                    $resultData3->execute($dataData3);

                if ($resultData3->rowCount() < 1) {
                    ++$countIncomplete;
                }
                if ($rowData2['gibbonPlannerEntryID'] != '') {
                    if ($countWhere > 0) {
                        $sqlWhere3 .= ' AND ';
                    }
                    $dataData4['data4i'.$countWhere] = $rowData2['gibbonPlannerEntryID'];
                    $sqlWhere3 .= ' NOT gibbonBehaviour.gibbonPlannerEntryID=:data4i'.$countWhere;
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
			
				$dataData4['gibbonPersonID'] = $row['gibbonPersonID'];
				$dataData4['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
				$sqlData4 = "SELECT * FROM gibbonBehaviour WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND type='Negative' AND (descriptor='Classwork - Incomplete' OR descriptor='Homework - Incomplete') AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere2 $sqlWhere3";
				$resultData4 = $connection2->prepare($sqlData4);
				$resultData4->execute($dataData4);

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
            echo __('There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>
