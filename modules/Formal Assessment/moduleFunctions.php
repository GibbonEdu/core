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
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                } elseif ($role == 'student') {
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                } elseif ($role == 'parent') {
                    $sqlInternalAssessment = "SELECT gibbonInternalAssessmentColumn.*, gibbonInternalAssessmentEntry.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:gibbonPersonID2 AND gibbonSchoolYearID=:gibbonSchoolYearID AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y'  ORDER BY completeDate DESC, gibbonCourse.nameShort, gibbonCourseClass.nameShort";
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
                $output .= "<th style='width: 120px'>";
                $output .= 'Assessment';
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
                    $output .= "<span title='".htmlPrep($rowInternalAssessment['description'])."'><b><u>".$rowInternalAssessment['course'].'.'.$rowInternalAssessment['class'].' '.$rowInternalAssessment['name'].'</u></b></span><br/>';
                    $output .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                    if ($rowInternalAssessment['completeDate'] != '') {
                        $output .= 'Marked on '.dateConvertBack($guid, $rowInternalAssessment['completeDate']).'<br/>';
                    } else {
                        $output .= 'Unmarked<br/>';
                    }
                    if ($rowInternalAssessment['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowInternalAssessment['attachment'])) {
                        $output .= " | <a 'title='Download more information' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowInternalAssessment['attachment']."'>More info</a>";
                    }
                    $output .= '</span><br/>';
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
                            $attainmentExtra = '<br/>'.__($guid, $rowAttainment['usage']);
                        }
                        $styleAttainment = "style='font-weight: bold'";
                        $output .= "<div $styleAttainment>".$rowInternalAssessment['attainmentValue'].'</div>';
                        if ($rowInternalAssessment['attainmentValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowInternalAssessment['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
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
                            $effortExtra = '<br/>'.__($guid, $rowEffort['usage']);
                        }
                        $styleEffort = "style='font-weight: bold'";
                        $output .= "<div $styleEffort>".$rowInternalAssessment['effortValue'];
                        $output .= '</div>';
                        if ($rowInternalAssessment['effortValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                            $output .= '<b>'.htmlPrep(__($guid, $rowInternalAssessment['effortDescriptor'])).'</b>';
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

    $selectCount = 0;
    $output .= "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php'>";
    $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>";
    $output .= '<tr>';
    $output .= "<td style='width: 190px'>";
    if ($mode == 'write') {
        $output .= "<input name='q' id='q' type='hidden' value='/modules/Formal Assessment/internalAssessment_write.php'>";
    } else {
        $output .= "<input name='q' id='q' type='hidden' value='/modules/Formal Assessment/internalAssessment_manage.php'>";
    }
    $output .= "<select name='gibbonCourseClassID' id='gibbonCourseClassID' style='width:161px'>";
    $output .= "<option value=''></option>";
    try {
        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {
    }
    $output .= "<optgroup label='--".__($guid, 'My Classes')."--'>";
    while ($rowSelect = $resultSelect->fetch()) {
        $selected = '';
        if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
            $selected = 'selected';
            ++$selectCount;
        }
        $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
    }
    $output .= '</optgroup>';

    if ($mode == 'manage' or ($mode == 'write' and getHighestGroupedAction($guid, '/modules/Formal Assessment/internalAssessment_write_data.php', $connection2) == 'Write Internal Assessments_all')) {
        try {
            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY course, class";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        $output .= "<optgroup label='--".__($guid, 'All Classes')."--'>";
        while ($rowSelect = $resultSelect->fetch()) {
            $selected = '';
            if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassID and $selectCount == 0) {
                $selected = 'selected';
                ++$selectCount;
            }
            $output .= "<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
        }
        $output .= '</optgroup>';
    }
    $output .= '</select>';
    $output .= '</td>';
    $output .= "<td class='right'>";
    $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';
    $output .= '</form>';

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
                $sqlResults = "SELECT gibbonExternalAssessmentField.name, gibbonExternalAssessmentField.category, resultGrade.value, resultGrade.descriptor, result.usage, result.lowestAcceptable, resultGrade.sequenceNumber, gibbonScaleGradeIDPrimaryAssessmentScale, resultGradePrimary.value AS valuePrimary, resultGradePrimary.descriptor AS descriptorPrimary, resultPrimary.usage AS usagePrimary, resultPrimary.lowestAcceptable AS lowestAcceptablePrimary, resultGradePrimary.sequenceNumber AS sequenceNumberPrimary FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonExternalAssessment ON (gibbonExternalAssessment.gibbonExternalAssessmentID=gibbonExternalAssessmentField.gibbonExternalAssessmentID) JOIN gibbonScaleGrade AS resultGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=resultGrade.gibbonScaleGradeID) JOIN gibbonScale AS result ON (result.gibbonScaleID=resultGrade.gibbonScaleID) LEFT JOIN gibbonScaleGrade AS resultGradePrimary ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale=resultGradePrimary.gibbonScaleGradeID) LEFT JOIN gibbonScale AS resultPrimary ON (resultPrimary.gibbonScaleID=resultGradePrimary.gibbonScaleID) WHERE gibbonPersonID=:gibbonPersonID AND result.active='Y' AND gibbonExternalAssessment.active='Y' AND gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order";
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
                        echo "<th style='width:15%'>";
                        echo "<span title='".__($guid, 'Primary assessment scale equivalent')."'>".__($guid, 'PAS Equivalent').'</span>';
                        echo '</th>';
                        echo "<th style='width:15%'>";
                        echo "<span title='".__($guid, 'Weighted average from subject-related markbook grades in the current year')."'>".__($guid, 'Markbook<br/>Average').'</span>';
                        echo '</th>';
                        echo "<th style='width:15%'>";
                        echo "<span title='".__($guid, 'Plus/Minus Value Added')."'>".__($guid, '+/-').'</span>';
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
                    echo '<td>';
                    if ($rowResults['valuePrimary'] != '' and $rowResults['usagePrimary'] != '') {
                        if (!is_null($rowResults['gibbonScaleGradeIDPrimaryAssessmentScale']) and !is_null($_SESSION[$guid]['primaryAssessmentScale'])) {
                            $style = '';
                            if ($rowResults['lowestAcceptablePrimary'] != '' and $rowResults['sequenceNumberPrimary'] > $rowResults['lowestAcceptablePrimary']) {
                                $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                            }
                            echo "<span $style title='".__($guid, $rowResults['usagePrimary'])."'>".__($guid, $rowResults['valuePrimary']).'</span>';
                        }
                    }
                    echo '</td>';
                    echo '<td>';
                    $av = false;
                    if (!is_null($rowResults['gibbonScaleGradeIDPrimaryAssessmentScale']) and !is_null($_SESSION[$guid]['primaryAssessmentScale'])) {
                        try {
                            $dataMB3 = array('name' => '%'.$rowResults['name'].'%', 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', (time() - (60 * 60 * 24 * 90))));
                            $sqlMB3 = "SELECT attainmentValue FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookEntry ON (gibbonMarkbookColumn.gibbonMarkbookColumnID=gibbonMarkbookEntry.gibbonMarkbookColumnID) WHERE gibbonCourse.name LIKE :name AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonMarkbookEntry.gibbonPersonIDStudent=$gibbonPersonID AND gibbonScaleIDAttainment=".$_SESSION[$guid]['primaryAssessmentScale'].' AND completeDate>=:date';
                            $resultMB3 = $connection2->prepare($sqlMB3);
                            $resultMB3->execute($dataMB3);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        $countMB3 = $resultMB3->rowCount();
                        $sumMB3 = 0;
                        while ($rowMB3 = $resultMB3->fetch()) {
                            $sumMB3 += $rowMB3['attainmentValue'];
                        }

                        try {
                            $dataMB12 = array('name' => '%'.$rowResults['name'].'%', 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', (time() - (60 * 60 * 24 * 90))));
                            $sqlMB12 = "SELECT attainmentValue FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookEntry ON (gibbonMarkbookColumn.gibbonMarkbookColumnID=gibbonMarkbookEntry.gibbonMarkbookColumnID) WHERE gibbonCourse.name LIKE :name AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonMarkbookEntry.gibbonPersonIDStudent=$gibbonPersonID AND gibbonScaleIDAttainment=".$_SESSION[$guid]['primaryAssessmentScale'].' AND completeDate>=:date';
                            $resultMB12 = $connection2->prepare($sqlMB12);
                            $resultMB12->execute($dataMB12);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        $countMB12 = $resultMB12->rowCount();
                        $sumMB12 = 0;
                        while ($rowMB12 = $resultMB12->fetch()) {
                            $sumMB12 += $rowMB12['attainmentValue'];
                        }

                        if ($countMB3 > 2 and $countMB12 <= 2) {
                            $av = round($sumMB3 / $countMB3, 2);
                        } elseif ($countMB3 <= 2 and $countMB12 > 2) {
                            $av = round($sumMB12 / $countMB12, 2);
                        } elseif ($countMB3 > 2 and $countMB12 > 2) {
                            $av = round((($sumMB3 / $countMB3) * 0.7) + (($sumMB12 / $countMB12) * 0.3), 2);
                        }

                        if ($av == false) {
                            echo '<i>'.__($guid, 'Insufficient data').'</i>';
                        } else {
                            echo "<span title='".$rowResults['usagePrimary']."'>".$av.'</span>';
                        }
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($av != false) {
                        $va = $av - $rowResults['valuePrimary'];
                        $style = '';
                        if ($va < 0) {
                            $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                        }
                        echo "<span $style>$va</span>";
                    }
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
