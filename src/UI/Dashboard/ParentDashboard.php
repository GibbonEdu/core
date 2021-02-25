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

namespace Gibbon\UI\Dashboard;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;

/**
 * Parent Dashboard View Composer
 *
 * @version  v18
 * @since    v18
 */
class ParentDashboard implements OutputableInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $db;
    protected $session;

    public function __construct(Connection $db, Session $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    public function getOutput()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $students = [];

        
            $data = ['gibbonPersonID' => $this->session->get('gibbonPersonID')];
            $sql = "SELECT * FROM gibbonFamilyAdult WHERE
                gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() > 0) {
            // Get child list
            while ($row = $result->fetch()) {
                
                    $dataChild = [
                        'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'),
                        'gibbonFamilyID' => $row['gibbonFamilyID'],
                        'today' => date('Y-m-d'),
                    ];
                    $sqlChild = "SELECT
                        gibbonPerson.gibbonPersonID,image_240, surname,
                        preferredName, dateStart,
                        gibbonYearGroup.nameShort AS yearGroup,
                        gibbonRollGroup.nameShort AS rollGroup,
                        gibbonRollGroup.website AS rollGroupWebsite,
                        gibbonRollGroup.gibbonRollGroupID
                        FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonFamilyID=:gibbonFamilyID
                        AND gibbonPerson.status='Full'
                        AND (dateStart IS NULL OR dateStart<=:today)
                        AND (dateEnd IS NULL OR dateEnd>=:today)
                        ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);

                while ($rowChild = $resultChild->fetch()) {
                    $students[] = $rowChild;
                }
            }
        }

        $output = '';

        if (count($students) > 0) {
            include_once $this->session->get('absolutePath').'/modules/Timetable/moduleFunctions.php';
            
            $output .= '<h2>'.__('Parent Dashboard').'</h2>';

            foreach ($students as $student) {
                $output .= '<h4>'.
                    $student['preferredName'].' '.$student['surname'].
                    '</h4>';

                $output .= '<section class="flex flex-col sm:flex-row">';
                
                $output .= '<div class="w-24 text-center mx-auto mb-4 sm:ml-0 sm:mr-4">'.
                    getUserPhoto($guid, $student['image_240'], 75).
                    "<div style='height: 5px'></div>".
                    "<span style='font-size: 70%'>".
                    "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID']."'>".__('Student Profile').'</a><br/>';

                if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                    $output .= "<a href='".$this->session->get('absoluteURL').'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$student['gibbonRollGroupID']."'>".__('Roll Group').' ('.$student['rollGroup'].')</a><br/>';
                }
                if ($student['rollGroupWebsite'] != '') {
                    $output .= "<a target='_blank' href='".$student['rollGroupWebsite']."'>".$student['rollGroup'].' '.__('Website').'</a>';
                }

                $output .= '</span>';
                $output .= '</div>';
                $output .= '<div class="flex-grow mb-6">';
                $dashboardContents = $this->renderChildDashboard($student['gibbonPersonID'], $student['dateStart']);
                if ($dashboardContents == false) {
                    $output .= "<div class='error'>".__('There are no records to display.').'</div>';
                } else {
                    $output .= $dashboardContents;
                }
                $output .= '</div>';
                $output .= '</section>';
            }
        }

        return $output;
    }

    protected function renderChildDashboard($gibbonPersonID, $dateStart)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $homeworkNameSingular = getSettingByScope($connection2, 'Planner', 'homeworkNameSingular');

        $return = false;

        $alert = getAlert($guid, $connection2, 002);
        $entryCount = 0;

        //PREPARE PLANNER SUMMARY
        $plannerOutput = "<span style='font-size: 85%; font-weight: bold'>".__('Today\'s Classes')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner.php&search='.$gibbonPersonID."'>".__('View Planner').'</a></span>';

        $classes = false;
        $date = date('Y-m-d');
        if (isSchoolOpen($guid, $date, $connection2) == true and isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') and $_SESSION[$guid]['username'] != '') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $gibbonPersonID, 'date2' => $date, 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $plannerOutput .= "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $classes = true;
                $plannerOutput .= "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>";
                $plannerOutput .= "<tr class='head'>";
                $plannerOutput .= '<th>';
                $plannerOutput .= __('Class').'<br/>';
                $plannerOutput .= '</th>';
                $plannerOutput .= '<th>';
                $plannerOutput .= __('Lesson').'<br/>';
                $plannerOutput .= "<span style='font-size: 85%; font-weight: normal; font-style: italic'>".__('Summary').'</span>';
                $plannerOutput .= '</th>';
                $plannerOutput .= '<th>';
                $plannerOutput .= __($homeworkNameSingular);
                $plannerOutput .= '</th>';
                $plannerOutput .= '<th>';
                $plannerOutput .= __('Action');
                $plannerOutput .= '</th>';
                $plannerOutput .= '</tr>';

                $count2 = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count2 % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count2;

                    //Highlight class in progress
                    if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                        $rowNum = 'current';
                    }

                    //COLOR ROW BY STATUS!
                    $plannerOutput .= "<tr class=$rowNum>";
                    $plannerOutput .= '<td>';
                    $plannerOutput .= '<b>'.$row['course'].'.'.$row['class'].'</b><br/>';
                    $plannerOutput .= '</td>';
                    $plannerOutput .= '<td id="wordWrap">';
                    $plannerOutput .= $row['name'].'<br/>';
                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                    if (isset($unit[0])) {
                        $plannerOutput .= $unit[0];
                        if ($unit[1] != '') {
                            $plannerOutput .= '<br/><i>'.$unit[1].' '.__('Unit').'</i><br/>';
                        }
                    }
                    $plannerOutput .= "<div style='font-size: 85%; font-weight: normal; font-style: italic'>";
                    $plannerOutput .= $row['summary'];
                    $plannerOutput .= '</div>';
                    $plannerOutput .= '</td>';
                    $plannerOutput .= '<td>';
                    if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                        $plannerOutput .= __('No');
                    } else {
                        if ($row['homework'] == 'Y') {
                            $plannerOutput .= __('Yes').': '.__('Teacher Recorded').'<br/>';
                            if ($row['homeworkSubmission'] == 'Y') {
                                $plannerOutput .= "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                if ($row['homeworkCrowdAssess'] == 'Y') {
                                    $plannerOutput .= "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                }
                            }
                        }
                        if ($row['myHomeworkDueDateTime'] != '') {
                            $plannerOutput .= __('Yes').': '.__('Student Recorded').'</br>';
                        }
                    }
                    $plannerOutput .= '</td>';
                    $plannerOutput .= '<td>';
                    $plannerOutput .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&search='.$gibbonPersonID.'&viewBy=date&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."&date=$date&width=1000&height=550'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    $plannerOutput .= '</td>';
                    $plannerOutput .= '</tr>';
                }
                $plannerOutput .= '</table>';
            }
        }
        if ($classes == false) {
            $plannerOutput .= "<div style='margin-top: 2px' class='warning'>";
            $plannerOutput .= __('There are no records to display.');
            $plannerOutput .= '</div>';
        }

        //PREPARE RECENT GRADES
        $gradesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".__('Recent Feedback')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Markbook/markbook_view.php&search='.$gibbonPersonID."'>".__('View Markbook').'</a></span></div>';
        $grades = false;

        //Get settings
        $enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
        $enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
        $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
        $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
        $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
        $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');
        $enableModifiedAssessment = getSettingByScope($connection2, 'Markbook', 'enableModifiedAssessment');

        try {
            $dataEntry = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' ORDER BY completeDate DESC LIMIT 0, 3";
            $resultEntry = $connection2->prepare($sqlEntry);
            $resultEntry->execute($dataEntry);
        } catch (PDOException $e) {
            $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultEntry->rowCount() > 0) {
            $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
            $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');
            $grades = true;
            $gradesOutput .= "<table cellspacing='0' style='margin: 3px 0px; width: 100%'>";
            $gradesOutput .= "<tr class='head'>";
            $gradesOutput .= "<th style='width: 120px'>";
            $gradesOutput .= __('Assessment');
            $gradesOutput .= '</th>';
            if ($enableModifiedAssessment == 'Y') {
                $gradesOutput .= "<th style='width: 75px'>";
                $gradesOutput .= __('Modified');
                $gradesOutput .= '</th>';
            }
            $gradesOutput .= "<th style='width: 75px'>";
            if ($attainmentAlternativeName != '') {
                $gradesOutput .= $attainmentAlternativeName;
            } else {
                $gradesOutput .= __('Attainment');
            }
            $gradesOutput .= '</th>';
            if ($enableEffort == 'Y') {
                $gradesOutput .= "<th style='width: 75px'>";
                if ($effortAlternativeName != '') {
                    $gradesOutput .= $effortAlternativeName;
                } else {
                    $gradesOutput .= __('Effort');
                }
            }
            $gradesOutput .= '</th>';
            $gradesOutput .= '<th>';
            $gradesOutput .= __('Comment');
            $gradesOutput .= '</th>';
            $gradesOutput .= "<th style='width: 75px'>";
            $gradesOutput .= __('Submission');
            $gradesOutput .= '</th>';
            $gradesOutput .= '</tr>';

            $count3 = 0;
            while ($rowEntry = $resultEntry->fetch()) {
                if ($count3 % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count3;

                $gradesOutput .= "<a name='".$rowEntry['gibbonMarkbookEntryID']."'></a>";

                $gradesOutput .= "<tr class=$rowNum>";
                $gradesOutput .= '<td>';
                $gradesOutput .= "<span title='".htmlPrep($rowEntry['description'])."'>".$rowEntry['name'].'</span><br/>';
                $gradesOutput .= "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                $gradesOutput .= __('Marked on').' '.dateConvertBack($guid, $rowEntry['completeDate']).'<br/>';
                $gradesOutput .= '</span>';
                $gradesOutput .= '</td>';
                if ($enableModifiedAssessment == 'Y') {
                    if (!is_null($rowEntry['modifiedAssessment'])) {
                        $gradesOutput .= "<td>";
                        $gradesOutput .= ynExpander($guid, $rowEntry['modifiedAssessment']);
                        $gradesOutput .= '</td>';
                    }
                    else {
                        $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $gradesOutput .= __('N/A');
                        $gradesOutput .= '</td>';
                    }
                }
                if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                    $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: center'>";
                    $gradesOutput .= __('N/A');
                    $gradesOutput .= '</td>';
                } else {
                    $gradesOutput .= "<td style='text-align: center'>";
                    $attainmentExtra = '';
                    
                        $dataAttainment = array('gibbonScaleID' => $rowEntry['gibbonScaleIDAttainment']);
                        $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                        $resultAttainment = $connection2->prepare($sqlAttainment);
                        $resultAttainment->execute($dataAttainment);
                    if ($resultAttainment->rowCount() == 1) {
                        $rowAttainment = $resultAttainment->fetch();
                        $attainmentExtra = '<br/>'.__($rowAttainment['usage']);
                    }
                    $styleAttainment = "style='font-weight: bold'";
                    if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
                        $styleAttainment = "style='color: ".$alert['color'].'; font-weight: bold; border: 2px solid '.$alert['color'].'; padding: 2px 4px; background-color: '.$alert['colorBG']."'";
                    } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
                        $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                    }
                    $gradesOutput .= "<div $styleAttainment>".$rowEntry['attainmentValue'];
                    if ($rowEntry['gibbonRubricIDAttainment'] != '' AND $enableRubrics =='Y') {
                        $gradesOutput .= "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$gibbonPersonID."&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                    }
                    $gradesOutput .= '</div>';
                    if ($rowEntry['attainmentValue'] != '') {
                        $gradesOutput .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['attainmentDescriptor'])).'</b>'.__($attainmentExtra).'</div>';
                    }
                    $gradesOutput .= '</td>';
                }
                if ($enableEffort == 'Y') {
                    if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                        $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: center'>";
                        $gradesOutput .= __('N/A');
                        $gradesOutput .= '</td>';
                    } else {
                        $gradesOutput .= "<td style='text-align: center'>";
                        $effortExtra = '';
                        
                            $dataEffort = array('gibbonScaleID' => $rowEntry['gibbonScaleIDEffort']);
                            $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                            $resultEffort = $connection2->prepare($sqlEffort);
                            $resultEffort->execute($dataEffort);
                        if ($resultEffort->rowCount() == 1) {
                            $rowEffort = $resultEffort->fetch();
                            $effortExtra = '<br/>'.__($rowEffort['usage']);
                        }
                        $styleEffort = "style='font-weight: bold'";
                        if ($rowEntry['effortConcern'] == 'Y' and $showParentEffortWarning == 'Y') {
                            $styleEffort = "style='color: ".$alert['color'].'; font-weight: bold; border: 2px solid '.$alert['color'].'; padding: 2px 4px; background-color: '.$alert['colorBG']."'";
                        }
                        $gradesOutput .= "<div $styleEffort>".$rowEntry['effortValue'];
                        if ($rowEntry['gibbonRubricIDEffort'] != '' AND $enableRubrics =='Y') {
                            $gradesOutput .= "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$gibbonPersonID."&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                        }
                        $gradesOutput .= '</div>';
                        if ($rowEntry['effortValue'] != '') {
                            $gradesOutput .= "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['effortDescriptor'])).'</b>'.__($effortExtra).'</div>';
                        }
                        $gradesOutput .= '</td>';
                    }
                }
                if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                    $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                    $gradesOutput .= __('N/A');
                    $gradesOutput .= '</td>';
                } else {
                    $gradesOutput .= '<td>';
                    if ($rowEntry['comment'] != '') {
                        if (mb_strlen($rowEntry['comment']) > 50) {
                            $gradesOutput .= "<script type='text/javascript'>";
                            $gradesOutput .= '$(document).ready(function(){';
                            $gradesOutput .= "\$(\".comment-$entryCount-$gibbonPersonID\").hide();";
                            $gradesOutput .= "\$(\".show_hide-$entryCount-$gibbonPersonID\").fadeIn(1000);";
                            $gradesOutput .= "\$(\".show_hide-$entryCount-$gibbonPersonID\").click(function(){";
                            $gradesOutput .= "\$(\".comment-$entryCount-$gibbonPersonID\").fadeToggle(1000);";
                            $gradesOutput .= '});';
                            $gradesOutput .= '});';
                            $gradesOutput .= '</script>';
                            $gradesOutput .= '<span>'.mb_substr($rowEntry['comment'], 0, 50).'...<br/>';
                            $gradesOutput .= "<a title='".__('View Description')."' class='show_hide-$entryCount-$gibbonPersonID' onclick='return false;' href='#'>".__('Read more').'</a></span><br/>';
                        } else {
                            $gradesOutput .= nl2br($rowEntry['comment']);
                        }
                        $gradesOutput .= '<br/>';
                    }
                    if ($rowEntry['response'] != '') {
                        $gradesOutput .= "<a title='".__('Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>".__('Uploaded Response').'</a><br/>';
                    }
                    $gradesOutput .= '</td>';
                }
                if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                    $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                    $gradesOutput .= __('N/A');
                    $gradesOutput .= '</td>';
                } else {
                    try {
                        $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                        $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                        $resultSub = $connection2->prepare($sqlSub);
                        $resultSub->execute($dataSub);
                    } catch (PDOException $e) {
                        $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultSub->rowCount() != 1) {
                        $gradesOutput .= "<td class='dull' style='color: #bbb; text-align: left'>";
                        $gradesOutput .= __('N/A');
                        $gradesOutput .= '</td>';
                    } else {
                        $gradesOutput .= '<td>';
                        $rowSub = $resultSub->fetch();

                        try {
                            $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                            $resultWork = $connection2->prepare($sqlWork);
                            $resultWork->execute($dataWork);
                        } catch (PDOException $e) {
                            $gradesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultWork->rowCount() > 0) {
                            $rowWork = $resultWork->fetch();

                            if ($rowWork['status'] == 'Exemption') {
                                $linkText = __('Exemption');
                            } elseif ($rowWork['version'] == 'Final') {
                                $linkText = __('Final');
                            } else {
                                $linkText = __('Draft').' '.$rowWork['count'];
                            }

                            $style = '';
                            $status = 'On Time';
                            if ($rowWork['status'] == 'Exemption') {
                                $status = __('Exemption');
                            } elseif ($rowWork['status'] == 'Late') {
                                $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                $status = __('Late');
                            }

                            if ($rowWork['type'] == 'File') {
                                $gradesOutput .= "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                            } elseif ($rowWork['type'] == 'Link') {
                                $gradesOutput .= "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                            } else {
                                $gradesOutput .= "<span title='$status. ".sprintf(__('Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                            }
                        } else {
                            if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                $gradesOutput .= "<span title='Pending'>".__('Pending').'</span>';
                            } else {
                                if (!empty($dateStart) && $dateStart > $rowSub['date']) {
                                    $gradesOutput .= "<span title='".__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
                                } else {
                                    if ($rowSub['homeworkSubmissionRequired'] == 'Required') {
                                        $gradesOutput .= "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__('Incomplete').'</div>';
                                    } else {
                                        $gradesOutput .= __('Not submitted online');
                                    }
                                }
                            }
                        }
                        $gradesOutput .= '</td>';
                    }
                }
                $gradesOutput .= '</tr>';
                if (strlen($rowEntry['comment']) > 50) {
                    $gradesOutput .= "<tr class='comment-$entryCount-$gibbonPersonID' id='comment-$entryCount-$gibbonPersonID'>";
                    $gradesOutput .= '<td colspan=6>';
                    $gradesOutput .= nl2br($rowEntry['comment']);
                    $gradesOutput .= '</td>';
                    $gradesOutput .= '</tr>';
                }
                ++$entryCount;
            }

            $gradesOutput .= '</table>';
        }
        if ($grades == false) {
            $gradesOutput .= "<div style='margin-top: 2px' class='warning'>";
            $gradesOutput .= __('There are no records to display.');
            $gradesOutput .= '</div>';
        }

        //PREPARE UPCOMING DEADLINES
        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');
        
        $deadlinesOutput = "<div style='margin-top: 20px'><span style='font-size: 85%; font-weight: bold'>".__('Upcoming Due Dates')."</span> . <span style='font-size: 70%'><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_deadlines.php&search='.$gibbonPersonID."'>".__('View {homeworkName}', ['homeworkName' => __($homeworkNamePlural)]).'</a></span></div>';
        $deadlines = false;

        $plannerGateway = $this->getContainer()->get(PlannerEntryGateway::class);
        $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($_SESSION[$guid]['gibbonSchoolYearID'], $gibbonPersonID, 'viewableParents')->fetchAll();

        $deadlinesOutput .= $this->getContainer()->get('page')->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
            'gibbonPersonID' => $gibbonPersonID,
            'deadlines' => $deadlines,
        ]);

        //PREPARE TIMETABLE
        $timetable = false;
        $timetableOutput = '';
        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
            $date = date('Y-m-d');
            if (isset($_POST['ttDate'])) {
                $date = dateConvert($guid, $_POST['ttDate']);
            }
            $params = '';
            if ($classes != false or $grades != false or $deadlines != false) {
                $params = '&tab=1';
            }
            $timetableOutputTemp = renderTT($guid, $connection2, $gibbonPersonID, null, null, dateConvertToTimestamp($date), '', $params, 'narrow');
            if ($timetableOutputTemp != false) {
                $timetable = true;
                $timetableOutput .= $timetableOutputTemp;
            }
        }

        //PREPARE ACTIVITIES
        $activities = false;
        $activitiesOutput = false;
        if (!(isActionAccessible($guid, $connection2, '/modules/Activities/activities_view.php'))) {
            $activitiesOutput .= "<div class='error'>";
            $activitiesOutput .= __('Your request failed because you do not have access to this action.');
            $activitiesOutput .= '</div>';
        } else {
            $activities = true;

            $activitiesOutput .= "<div class='linkTop'>";
            $activitiesOutput .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_view.php&gibbonPersonID=".$gibbonPersonID."'>".__('View Available Activities').'</a>';
            $activitiesOutput .= '</div>';

            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
            if ($dateType == 'Term') {
                $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
            }
            try {
                $dataYears = array('gibbonPersonID' => $gibbonPersonID);
                $sqlYears = "SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' AND gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC";
                $resultYears = $connection2->prepare($sqlYears);
                $resultYears->execute($dataYears);
            } catch (PDOException $e) {
                $activitiesOutput .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultYears->rowCount() < 1) {
                $activitiesOutput .= "<div class='error'>";
                $activitiesOutput .= __('There are no records to display.');
                $activitiesOutput .= '</div>';
            } else {
                $yearCount = 0;
                while ($rowYears = $resultYears->fetch()) {
                    ++$yearCount;
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                        $sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $activitiesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() < 1) {
                        $activitiesOutput .= "<div class='error'>";
                        $activitiesOutput .= __('There are no records to display.');
                        $activitiesOutput .= '</div>';
                    } else {
                        $activitiesOutput .= "<table cellspacing='0' style='width: 100%'>";
                        $activitiesOutput .= "<tr class='head'>";
                        $activitiesOutput .= '<th>';
                        $activitiesOutput .= __('Activity');
                        $activitiesOutput .= '</th>';
                        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
                        if ($options != '') {
                            $activitiesOutput .= '<th>';
                            $activitiesOutput .= __('Type');
                            $activitiesOutput .= '</th>';
                        }
                        $activitiesOutput .= '<th>';
                        if ($dateType != 'Date') {
                            $activitiesOutput .= __('Term');
                        } else {
                            $activitiesOutput .= __('Dates');
                        }
                        $activitiesOutput .= '</th>';
                        $activitiesOutput .= '<th>';
                        $activitiesOutput .= __('Slots');
                        $activitiesOutput .= '</th>';
                        $activitiesOutput .= '<th>';
                        $activitiesOutput .= __('Status');
                        $activitiesOutput .= '</th>';
                        $activitiesOutput .= '</tr>';

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
                            $activitiesOutput .= "<tr class=$rowNum>";
                            $activitiesOutput .= '<td>';
                            $activitiesOutput .= $row['name'];
                            $activitiesOutput .= '</td>';
                            if ($options != '') {
                                $activitiesOutput .= '<td>';
                                $activitiesOutput .= trim($row['type']);
                                $activitiesOutput .= '</td>';
                            }
                            $activitiesOutput .= '<td>';
                            if ($dateType != 'Date') {
                                $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                                $termList = '';
                                for ($i = 0; $i < count($terms); $i = $i + 2) {
                                    if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                        $termList .= $terms[($i + 1)].'<br/>';
                                    }
                                }
                                $activitiesOutput .= $termList;
                            } else {
                                if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                                    if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                                        $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                                    } else {
                                        $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).'<br/>'.substr($row['programStart'], 0, 4);
                                    }
                                } else {
                                    $activitiesOutput .= date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
                                }
                            }
                            $activitiesOutput .= '</td>';
                            $activitiesOutput .= '<td>';
                                try {
                                    $dataSlots = array('gibbonActivityID' => $row['gibbonActivityID']);
                                    $sqlSlots = 'SELECT gibbonActivitySlot.*, gibbonDaysOfWeek.name AS dayOfWeek, gibbonSpace.name AS facility FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) LEFT JOIN gibbonSpace ON (gibbonActivitySlot.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';
                                    $resultSlots = $connection2->prepare($sqlSlots);
                                    $resultSlots->execute($dataSlots);
                                } catch (PDOException $e) {
                                    $activitiesOutput .= "<div class='error'>".$e->getMessage().'</div>';
                                }
                                $count = 0;
                                while ($rowSlots = $resultSlots->fetch()) {
                                    $activitiesOutput .= '<b>'.$rowSlots['dayOfWeek'].'</b><br/>';
                                    $activitiesOutput .= '<i>'.__('Time').'</i>: '.substr($rowSlots['timeStart'], 0, 5).' - '.substr($rowSlots['timeEnd'], 0, 5).'<br/>';
                                    if ($rowSlots['gibbonSpaceID'] != '') {
                                        $activitiesOutput .= '<i>'.__('Location').'</i>: '.$rowSlots['facility'];
                                    } else {
                                        $activitiesOutput .= '<i>'.__('Location').'</i>: '.$rowSlots['locationExternal'];
                                    }
                                    ++$count;
                                }
                                if ($count == 0) {
                                    $activitiesOutput .= '<i>'.__('None').'</i>';
                                }
                            $activitiesOutput .= '</td>';
                            $activitiesOutput .= '<td>';
                            if ($row['status'] != '') {
                                $activitiesOutput .= $row['status'];
                            } else {
                                $activitiesOutput .= '<i>'.__('NA').'</i>';
                            }
                            $activitiesOutput .= '</td>';
                            $activitiesOutput .= '</tr>';
                        }
                        $activitiesOutput .= '</table>';
                    }
                }
            }
        }

        //GET HOOKS INTO DASHBOARD
        $hooks = array();
        try {
            $dataHooks = array();
            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Parental Dashboard'";
            $resultHooks = $connection2->prepare($sqlHooks);
            $resultHooks->execute($dataHooks);
        } catch (PDOException $e) {
            $return .= "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultHooks->rowCount() > 0) {
            $count = 0;
            while ($rowHooks = $resultHooks->fetch()) {
                $options = unserialize($rowHooks['options']);
                //Check for permission to hook
                
                    $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                    $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Parental Dashboard'  AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                    $resultHook = $connection2->prepare($sqlHook);
                    $resultHook->execute($dataHook);
                if ($resultHook->rowCount() == 1) {
                    $rowHook = $resultHook->fetch();
                    $hooks[$count]['name'] = $rowHooks['name'];
                    $hooks[$count]['sourceModuleName'] = $rowHook['module'];
                    $hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
                    ++$count;
                }
            }
        }

        if ($classes == false and $grades == false and $deadlines == false and $timetable == false and $activities == false and count($hooks) < 1) {
            $return .= "<div class='warning'>";
            $return .= __('There are no records to display.');
            $return .= '</div>';
        } else {
            $parentDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'parentDashboardDefaultTab');
            $parentDashboardDefaultTabCount = null;

            $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
            $return .= '<ul>';
            $tabCountExtraReset = 0;
            if ($classes != false or $grades != false or $deadlines != false) {
                $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__('Learning').'</a></li>';
                $tabCountExtraReset++;
                if ($parentDashboardDefaultTab == 'Planner')
                    $parentDashboardDefaultTabCount = $tabCountExtraReset;
            }
            if ($timetable != false) {
                $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__('Timetable').'</a></li>';
                $tabCountExtraReset++;
                if ($parentDashboardDefaultTab == 'Timetable')
                    $parentDashboardDefaultTabCount = $tabCountExtraReset;
            }
            if ($activities != false) {
                $return .= "<li><a href='#tabs".$tabCountExtraReset."'>".__('Activities').'</a></li>';
                $tabCountExtraReset++;
                if ($parentDashboardDefaultTab == 'Activities')
                    $parentDashboardDefaultTabCount = $tabCountExtraReset;
            }
            $tabCountExtra = $tabCountExtraReset;
            foreach ($hooks as $hook) {
                ++$tabCountExtra;
                $return .= "<li><a href='#tabs".$tabCountExtra."'>".__($hook['name']).'</a></li>';
            }
            $return .= '</ul>';

            $tabCountExtraReset = 0;
            if ($classes != false or $grades != false or $deadlines != false) {
                $return .= "<div id='tabs".$tabCountExtraReset."' class='overflow-x-auto'>";
                $return .= $plannerOutput;
                $return .= $gradesOutput;
                $return .= $deadlinesOutput;
                $return .= '</div>';
                $tabCountExtraReset++;
            }
            if ($timetable != false) {
                $return .= "<div id='tabs".$tabCountExtraReset."' class='overflow-x-auto'>";
                $return .= $timetableOutput;
                $return .= '</div>';
                $tabCountExtraReset++;
            }
            if ($activities != false) {
                $return .= "<div id='tabs".$tabCountExtraReset."' class='overflow-x-auto'>";
                $return .= $activitiesOutput;
                $return .= '</div>';
                $tabCountExtraReset++;
            }
            $tabCountExtra = $tabCountExtraReset;
            foreach ($hooks as $hook) {
                if ($parentDashboardDefaultTab == $hook['name'])
                    $parentDashboardDefaultTabCount = $tabCountExtra+1;
                ++$tabCountExtra;
                $return .= "<div style='min-height: 100px' id='tabs".$tabCountExtra."'>";
                $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
                if (!file_exists($include)) {
                    $return .= "<div class='error'>";
                    $return .= __('The selected page cannot be displayed due to a hook error.');
                    $return .= '</div>';
                } else {
                    $return .= include $include;
                }
                $return .= '</div>';
            }
            $return .= '</div>';
        }


        $defaultTab = 0;
        if (isset($_GET['tab'])) {
            $defaultTab = $_GET['tab'];
        }
        else if (!is_null($parentDashboardDefaultTabCount)) {
            $defaultTab = $parentDashboardDefaultTabCount-1;
        }
        $return .= "<script type='text/javascript'>";
        $return .= '$( "#'.$gibbonPersonID.'tabs" ).tabs({';
        $return .= 'active: '.$defaultTab.',';
        $return .= 'ajaxOptions: {';
        $return .= 'error: function( xhr, status, index, anchor ) {';
        $return .= '$( anchor.hash ).html(';
        $return .= "\"Couldn't load this tab.\" );";
        $return .= '}';
        $return .= '}';
        $return .= '});';
        $return .= '</script>';

        return $return;
    }
}
