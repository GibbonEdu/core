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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//$role can be teacher, student or parent. If no role is specified, the default is teacher.
function getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, $role = 'teacher')
{
    global $session, $container;

    $output = '';

    $settingGateway = $container->get(SettingGateway::class);
    //Get alternative header names
    $attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
    $effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');

    //Get school years in reverse order
    try {
        $dataYears = array('gibbonPersonID' => $gibbonPersonID);
        $sqlYears = "SELECT * FROM gibbonSchoolYear JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (status='Current' OR status='Past') AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
        $resultYears = $connection2->prepare($sqlYears);
        $resultYears->execute($dataYears);
    } catch (PDOException $e) {
    }

    if ($resultYears->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __('There are no records to display.');
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
            }

            if ($resultInternalAssessment->rowCount() > 0) {
                $results = true;
                $output .= '<h4>';
                $output .= $rowYears['name'];
                $output .= '</h4>';
                $output .= "<table cellspacing='0' style='width: 100%'>";
                $output .= "<tr class='head'>";
                $output .= "<th style='width: 160px'>";
                $output .= __('Assessment');
                $output .= '</th>';
                $output .= "<th style='width: 180px'>";
                $output .= __('Course');
                $output .= '</th>';
                $output .= "<th style='width: 75px; text-align: center'>";
                if ($attainmentAlternativeName != '') {
                    $output .= $attainmentAlternativeName;
                } else {
                    $output .= __('Attainment');
                }
                $output .= '</th>';
                $output .= "<th style='width: 75px; text-align: center'>";
                if ($effortAlternativeName != '') {
                    $output .= $effortAlternativeName;
                } else {
                    $output .= __('Effort');
                }
                $output .= '</th>';
                $output .= '<th>';
                $output .= __('Comment');
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
                        $output .= __('Marked on').' '.Format::date($rowInternalAssessment['completeDate']).'<br/>';
                    } else {
                        $output .= __('Unmarked').'<br/>';
                    }
                    if ($rowInternalAssessment['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$rowInternalAssessment['attachment'])) {
                        $output .= " | <a target='_blank' title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$rowInternalAssessment['attachment']."'>".__('More info')."</a>";
                    }
                    $output .= '</span>';
                    $output .= '</td>';
                    $output .= "<td>";
                    $output .= $rowInternalAssessment['courseFull'];
                    $output .= '</td>';
                    if ($rowInternalAssessment['attainment'] == 'N' or $rowInternalAssessment['gibbonScaleIDAttainment'] == '') {
                        $output .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $output .= __('N/A');
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
                        }
                        if ($resultAttainment->rowCount() == 1) {
                            $rowAttainment = $resultAttainment->fetch();
                            $attainmentExtra = __($rowAttainment['usage']);
                        }
                        $styleAttainment = "style='font-weight: bold'";
                        $title = ($rowInternalAssessment['attainmentValue']!=$rowInternalAssessment['attainmentDescriptor']) ? $title="title='".$rowInternalAssessment['attainmentDescriptor']."'" : '';
                        $output .= "<div $styleAttainment".$title.">".$rowInternalAssessment['attainmentValue'].'</div>';
                        if ($rowInternalAssessment['attainmentValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>".__($attainmentExtra).'</div>';
                        }
                        $output .= '</td>';
                    }
                    if ($rowInternalAssessment['effort'] == 'N' or $rowInternalAssessment['gibbonScaleIDEffort'] == '') {
                        $output .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $output .= __('N/A');
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
                        }
                        if ($resultEffort->rowCount() == 1) {
                            $rowEffort = $resultEffort->fetch();
                            $effortExtra = __($rowEffort['usage']);
                        }
                        $styleEffort = "style='font-weight: bold'";
                        $title = ($rowInternalAssessment['effortValue']!=$rowInternalAssessment['effortDescriptor']) ? $title="title='".$rowInternalAssessment['effortDescriptor']."'" : '';
                        $output .= "<div $styleEffort".$title.">".$rowInternalAssessment['effortValue'];
                        $output .= '</div>';
                        if ($rowInternalAssessment['effortValue'] != '') {
                            $output .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                            if ($effortExtra != '') {
                                $output .= __($effortExtra);
                            }
                            $output .= '</div>';
                        }
                        $output .= '</td>';
                    }
                    if ($rowInternalAssessment['comment'] == 'N' and $rowInternalAssessment['uploadedResponse'] == 'N') {
                        echo "<td class='dull' style='color: #bbb; text-align: left'>";
                        echo __('N/A');
                        echo '</td>';
                    } else {
                        $output .= '<td>';
                        if ($rowInternalAssessment['comment'] != '') {
                            $output .= $rowInternalAssessment['comment'].'<br/>';
                        }
                        if ($rowInternalAssessment['response'] != '') {
                            $output .= "<a target='_blank' title='".__('Uploaded Response')."' href='".$session->get('absoluteURL').'/'.$rowInternalAssessment['response']."'>".__('Uploaded Response').'</a><br/>';
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
            $output .= __('There are no records to display.');
            $output .= '</div>';
        }
    }

    return $output;
}

function sidebarExtra($guid, $connection2, $gibbonCourseClassID, $mode = 'manage')
{
    global $session;

    $output = '';

    $output .= '<div class="column-no-break">';

    $classes = array();

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
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
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
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

    $form = Form::create('classSelect', $session->get('absoluteURL').'/index.php', 'get');
    $form->addHiddenValue('q', '/modules/Formal Assessment/internalAssessment_'.$mode.'.php');
    $form->setTitle(__('Select Class'));
    $form->setClass('smallIntBorder w-full');

    $row = $form->addRow();
        $row->addSelect('gibbonCourseClassID')
            ->fromArray($classes)
            ->selected($gibbonCourseClassID)
            ->placeholder()
            ->setClass('float-none w-full');
        $row->addSubmit(__('Go'));

    $output .= $form->getOutput();

    $output .= '</div>';

    return $output;
}

function externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $gibbonYearGroupID = null, $manage = false, $search = '', $allStudents = '')
{
    global $session;

    $dataAssessments = array('gibbonPersonID' => $gibbonPersonID);
    $sqlAssessments = 'SELECT * FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY date';
    $resultAssessments = $connection2->prepare($sqlAssessments);
    $resultAssessments->execute($dataAssessments);

    if ($resultAssessments->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        while ($rowAssessments = $resultAssessments->fetch()) {
            echo '<h2>';
            echo __($rowAssessments['name'])." <span style='font-size: 75%; font-style: italic'>(".Format::monthName(mktime(0, 0, 0, substr($rowAssessments['date'], 5, 2)), true).' '.substr($rowAssessments['date'], 0, 4).')</span>';
            if ($manage == true) {
                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/externalAssessment_manage_details_edit.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=".$rowAssessments['gibbonExternalAssessmentStudentID']."&search=$search&allStudents=$allStudents'><img style='margin-left: 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                echo "<a href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/externalAssessment_manage_details_delete.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=".$rowAssessments['gibbonExternalAssessmentStudentID']."&search=$search&allStudents=$allStudents&width=600&height=135' class='thickbox'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>";
            }
            echo '</h2>';
            echo '<p>';
            echo __($rowAssessments['description']);
            echo '</p>';

            if ($rowAssessments['attachment'] != '') {
                echo "<div class='linkTop'>";
                echo "<a target='_blank' href='".$session->get('absoluteURL').'/'.$rowAssessments['attachment']."'>".__('Uploaded File').'</a>';
                echo '</div>';
            }

            //Get results

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

            if ($resultResults->rowCount() < 1) {
                echo "<div class='warning'>";
                echo __('There are no records to display.');
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
                        echo "<p style='font-weight: bold; margin: 15px 0 5px;'>";
                        if (strpos($rowResults['category'], '_') === false) {
                            echo $rowResults['category'];
                        } else {
                            echo substr($rowResults['category'], (strpos($rowResults['category'], '_') + 1));
                        }
                        echo '</p>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width:40%'>";
                        echo __('Item');
                        echo '</th>';
                        echo "<th style='width:15%'>";
                        echo __('Result');
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
                    echo __($rowResults['name']);
                    echo '</td>';
                    echo '<td>';
                    $style = '';
                    if ($rowResults['lowestAcceptable'] != '' and $rowResults['sequenceNumber'] > $rowResults['lowestAcceptable']) {
                        $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                    }
                    echo "<span $style title='".__($rowResults['usage'])."'>".__($rowResults['value']).'</span>';
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
