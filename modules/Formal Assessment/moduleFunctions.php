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

//$role can be teacher, student or parent. If no role is specified, the default is teacher.
function getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, $role = 'teacher')
{
    $output = '';

    //Get alternative header names
    $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
    $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
    $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
    $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');
    $alert = getAlert($guid, $connection2, 002);

    //Get school years in reverse order
    try {
        $dataYears = array('gibbonPersonID' => $gibbonPersonID);
        $sqlYears = "SELECT * FROM gibbonSchoolYear JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (status='Current' OR status='Past') AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
        $resultYears = $connection2->prepare($sqlYears);
        $resultYears->execute($dataYears);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultYears->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'There are no records to display.');
        $output .= '</div>';
    } else {
        $results = false;
        while ($rowYears = $resultYears->fetch()) {
            //Get and output Internal Assessments
            try {
                $dataInternalAssessment = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                if ($role == 'teacher') {
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name AS courseFull FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                } elseif ($role == 'student') {
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name AS courseFull FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                } elseif ($role == 'parent') {
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name AS courseFull FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y'  ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                }
                $resultInternalAssessment = $connection2->prepare($sqlInternalAssessment);
                $resultInternalAssessment->execute($dataInternalAssessment);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultInternalAssessment->rowCount() > 0) {
                $results = true;
                $output .= '<h4>';
                $output .= $rowYears['name'];
                $output .= '</h4>';
                $output .= "<table cellspacing='0' style='width: 100%'>";
                $output .= "<tr class='head'>";
                $output .= "<th style='width: 160px'>";
                $output .= 'Assessment';
                $output .= '</th>';
                $output .= "<th style='width: 180px'>";
                $output .= 'Course';
                $output .= '</th>';
                $output .= "<th style='width: 75px; text-align: center'>";
                if ($attainmentAlternativeName != '') {
                    $output .= $attainmentAlternativeName;
                } else {
                    $output .= __($guid, 'Attainment');
                }
                $output .= '</th>';
                $output .= "<th style='width: 75px; text-align: center'>";
                if ($effortAlternativeName != '') {
                    $output .= $effortAlternativeName;
                } else {
                    $output .= __($guid, 'Effort');
                }
                $output .= '</th>';
                $output .= '<th>';
                $output .= 'Comment';
                $output .= '</th>';

                $output .= '</tr>';

                $count = 0;
                while ($rowInternalAssessment = $resultInternalAssessment->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    $output .= "<tr class=$rowNum>";
                    $output .= '<td>';
                    $output .= "<span title='".htmlPrep($rowInternalAssessment['description'])."'><b><u>".$rowInternalAssessment['name'].'</u></b></span><br/>';
                    $output .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                    if ($rowInternalAssessment['completeDate'] != '') {
                        $output .= 'Marked on '.dateConvertBack($guid, $rowInternalAssessment['completeDate']).'<br/>';
                    } else {
                        $output .= 'Unmarked<br/>';
                    }
                    if ($rowInternalAssessment['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowInternalAssessment['attachment'])) {
                        $output .= " | <a 'title='Download more information' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowInternalAssessment['attachment']."'>More info</a>";
                    }
                    $output .= '</span>';
                    $output .= '</td>';
                    $output .= "<td>";
                    $output .= $rowInternalAssessment['courseFull'];
                    $output .= '</td>';
                    if ($rowInternalAssessment['attainment'] == 'N' or $rowInternalAssessment['gibbonScaleIDAttainment'] == '') {
                        $output .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $output .= __($guid, 'N/A');
                        $output .= '</td>';
                    } else {
                        $output .= "<td style='text-align: center'>";
                        $attainmentExtra = '';
                        try {
                            $dataAttainment = array('gibbonScaleID' => $rowInternalAssessment['gibbonScaleIDAttainment']);
                            $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                            $resultAttainment = $connection2->prepare($sqlAttainment);
                            $resultAttainment->execute($dataAttainment);
                        } catch (PDOException $e) {
                            $output .= "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultAttainment->rowCount() == 1) {
                            $rowAttainment = $resultAttainment->fetch();
                            $attainmentExtra = __($guid, $rowAttainment['usage']);
                        }
                        $styleAttainment = "style='font-weight: bold'";
                        $output .= "<div $styleAttainment>".$rowInternalAssessment['attainmentValue'].'</div>';
                        if ($rowInternalAssessment['attainmentValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>".__($guid, $attainmentExtra).'</div>';
                        }
                        $output .= '</td>';
                    }
                    if ($rowInternalAssessment['effort'] == 'N' or $rowInternalAssessment['gibbonScaleIDEffort'] == '') {
                        $output .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $output .= __($guid, 'N/A');
                        $output .= '</td>';
                    } else {
                        $output .= "<td style='text-align: center'>";
                        $effortExtra = '';
                        try {
                            $dataEffort = array('gibbonScaleID' => $rowInternalAssessment['gibbonScaleIDEffort']);
                            $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                            $resultEffort = $connection2->prepare($sqlEffort);
                            $resultEffort->execute($dataEffort);
                        } catch (PDOException $e) {
                            $output .= "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultEffort->rowCount() == 1) {
                            $rowEffort = $resultEffort->fetch();
                            $effortExtra = __($guid, $rowEffort['usage']);
                        }
                        $styleEffort = "style='font-weight: bold'";
                        $output .= "<div $styleEffort>".$rowInternalAssessment['effortValue'];
                        $output .= '</div>';
                        if ($rowInternalAssessment['effortValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                            if ($effortExtra != '') {
                                $output .= __($guid, $effortExtra);
                            }
                            $output .= '</div>';
                        }
                        $output .= '</td>';
                    }
                    if ($rowInternalAssessment['comment'] == 'N' and $rowInternalAssessment['uploadedResponse'] == 'N') {
                        echo "<td class='dull' style='color: #bbb; text-align: left'>";
                        echo __($guid, 'N/A');
                        echo '</td>';
                    } else {
                        $output .= '<td>';
                        if ($rowInternalAssessment['comment'] != '') {
                            $output .= $rowInternalAssessment['comment'].'<br/>';
                        }
                        if ($rowInternalAssessment['response'] != '') {
                            $output .= "<a title='".__($guid, 'Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowInternalAssessment['response']."'>".__($guid, 'Uploaded Response').'</a><br/>';
                        }
                        $output .= '</td>';
                    }
                    $output .= '</tr>';
                }

                $output .= '</table>';
            }
        }
        if ($results == false) {
            $output .= "<div class='error'>";
            $output .= __($guid, 'There are no records to display.');
            $output .= '</div>';
        }
    }

    return $output;
}

function sidebarExtra($guid, $connection2, $gibbonCourseClassID, $mode = 'manage')
{
    $output = '';

    $output .= '<h2>';
    $output .= __($guid, 'Select Class');
    $output .= '</h2>';

    $classes = array();
    
    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
    $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if ($result->rowCount() > 0) {
        $group = '--'.__('My Classes').'--';
        while ($class = $result->fetch()) {
            $classes[$group][$class['gibbonCourseClassID']] = $class['course'].'.'.$class['class'];
        }
    }

    if ($mode == 'manage' or ($mode == 'write' and getHighestGroupedAction($guid, '/modules/Formal Assessment/internalAssessment_write_data.php', $connection2) == 'Write Internal Assessments_all')) {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() > 0) {
            $group = '--'.__('All Classes').'--';
            while ($class = $result->fetch()) {
                $classes[$group][$class['gibbonCourseClassID']] = $class['course'].'.'.$class['class'];
            }
        }
    }

    $form = Form::create('classSelect', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->addHiddenValue('q', '/modules/Formal Assessment/internalAssessment_'.$mode.'.php');

    $row = $form->addRow();
        $row->addSelect('gibbonCourseClassID')
            ->fromArray($classes)
            ->selected($gibbonCourseClassID)
            ->placeholder()
            ->setClass('fullWidth');
        $row->addSubmit(__('Go'));

    $output .= $form->getOutput();

    return $output;
}

function externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $gibbonYearGroupID = null, $manage = false, $search = '', $allStudents = '')
{
    try {
        $dataAssessments = array('gibbonPersonID' => $gibbonPersonID);
        $sqlAssessments = 'SELECT * FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY date';
        $resultAssessments = $connection2->prepare($sqlAssessments);
        $resultAssessments->execute($dataAssessments);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultAssessments->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        while ($rowAssessments = $resultAssessments->fetch()) {
            echo '<h2>';
            echo __($guid, $rowAssessments['name'])." <span style='font-size: 75%; font-style: italic'>(".substr(strftime('%B', mktime(0, 0, 0, substr($rowAssessments['date'], 5, 2))), 0, 3).' '.substr($rowAssessments['date'], 0, 4).')</span>';
            if ($manage == true) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_edit.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=".$rowAssessments['gibbonExternalAssessmentStudentID']."&search=$search&allStudents=$allStudents'><img style='margin-left: 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_delete.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=".$rowAssessments['gibbonExternalAssessmentStudentID']."&search=$search&allStudents=$allStudents'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            }
            echo '</h2>';
            echo '<p>';
            echo __($guid, $rowAssessments['description']);
            echo '</p>';

            if ($rowAssessments['attachment'] != '') {
                echo "<div class='linkTop'>";
                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowAssessments['attachment']."'>".__($guid, 'Uploaded File').'</a>';
                echo '</div>';
            }

            //Get results
            try {
                $dataResults = array('gibbonPersonID' => $gibbonPersonID, 'gibbonExternalAssessmentStudentID' => $rowAssessments['gibbonExternalAssessmentStudentID']);
                $sqlResults = "SELECT gibbonExternalAssessmentField.name, gibbonExternalAssessmentField.category, resultGrade.value, resultGrade.descriptor, result.usage, result.lowestAcceptable, resultGrade.sequenceNumber
                    FROM gibbonExternalAssessmentStudentEntry
                        JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID)
                        JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID)
                        JOIN gibbonExternalAssessment ON (gibbonExternalAssessment.gibbonExternalAssessmentID=gibbonExternalAssessmentField.gibbonExternalAssessmentID)
                        JOIN gibbonScaleGrade AS resultGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=resultGrade.gibbonScaleGradeID)
                        JOIN gibbonScale AS result ON (result.gibbonScaleID=resultGrade.gibbonScaleID)
                    WHERE gibbonPersonID=:gibbonPersonID
                        AND result.active='Y'
                        AND gibbonExternalAssessment.active='Y'
                        AND gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID
                    ORDER BY category, gibbonExternalAssessmentField.order";
                $resultResults = $connection2->prepare($sqlResults);
                $resultResults->execute($dataResults);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultResults->rowCount() < 1) {
                echo "<div class='warning'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                $lastCategory = '';
                $count = 0;
                $rowNum = 'odd';
                while ($rowResults = $resultResults->fetch()) {
                    if ($rowResults['category'] != $lastCategory) {
                        if ($count != 0) {
                            echo '</table>';
                        }
                        echo "<p style='font-weight: bold; margin-bottom: 0px'>";
                        if (strpos($rowResults['category'], '_') === false) {
                            echo $rowResults['category'];
                        } else {
                            echo substr($rowResults['category'], (strpos($rowResults['category'], '_') + 1));
                        }
                        echo '</p>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width:40%'>";
                        echo __($guid, 'Item');
                        echo '</th>';
                        echo "<th style='width:15%'>";
                        echo __($guid, 'Result');
                        echo '</th>';
                        echo '</tr>';
                    }

                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo __($guid, $rowResults['name']);
                    echo '</td>';
                    echo '<td>';
                    $style = '';
                    if ($rowResults['lowestAcceptable'] != '' and $rowResults['sequenceNumber'] > $rowResults['lowestAcceptable']) {
                        $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                    }
                    echo "<span $style title='".__($guid, $rowResults['usage'])."'>".__($guid, $rowResults['value']).'</span>';
                    echo '</td>';
                    echo '</tr>';

                    $lastCategory = $rowResults['category'];
                    ++$count;
                }
                echo '</table>';
            }
        }
    }
}
