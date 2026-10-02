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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\View\View;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * MarkbookPage
 * 
 * Displays markbook entries and grades for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class MarkbookPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private Connection $pdo;
    private View $view;

    public function __construct(
        Session $session,
        Connection $pdo,
        View $view
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->view = $view;
    }

    /**
     * Check if the current user has permission to view markbook
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Markbook', 'markbook_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Markbook');
    }


    /**
     * Generate HTML output for the markbook page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        // The markbook rendering logic is complex and involves many settings and calculations
        // For now, we include the original logic inline
        // TODO: refactor the markbook grades display into a template file
        
        ob_start();
        
        // Include the original markbook rendering logic
        $gibbonPersonID = $this->gibbonPersonID;
        $gibbonSchoolYearID = $this->gibbonSchoolYearID;
        $session = $this->session;
        $connection2 = $this->pdo->getConnection();
        $container = $this->getContainer();
        $pdo = $this->pdo;
        $page = $this->view;
        $settingGateway = $container->get(\Gibbon\Domain\System\SettingGateway::class);
        
        // Include module functions
        include './modules/Markbook/moduleFunctions.php';

        // Get settings
        $enableEffort = $settingGateway->getSettingByScope('Markbook', 'enableEffort');
        $enableRubrics = $settingGateway->getSettingByScope('Markbook', 'enableRubrics');
        $attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
        $attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
        $effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
        $effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');
        $enableModifiedAssessment = $settingGateway->getSettingByScope('Markbook', 'enableModifiedAssessment');

        $alertLevelGateway = $container->get(\Gibbon\Domain\System\AlertLevelGateway::class);
        $alert = $alertLevelGateway->getByID(\Gibbon\Domain\System\AlertLevelGateway::LEVEL_MEDIUM);
        $role = $session->get('gibbonRoleIDCurrentCategory');
        
        if ($role == 'Parent') {
            $showParentAttainmentWarning = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning');
            $showParentEffortWarning = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning');
        } else {
            $showParentAttainmentWarning = 'Y';
            $showParentEffortWarning = 'Y';
        }
        
        $entryCount = 0;
        $and = '';
        $and2 = '';
        $dataList = [];
        $dataEntry = [];
        $gibbonSchoolYearIDFilter = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

        if ($gibbonSchoolYearIDFilter != '*') {
            $dataList['gibbonSchoolYearID'] = $gibbonSchoolYearIDFilter;
            $and .= ' AND gibbonSchoolYearID=:gibbonSchoolYearID';
        }

        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '*';
        if ($gibbonDepartmentID != '*') {
            $dataList['gibbonDepartmentID'] = $gibbonDepartmentID;
            $and .= ' AND gibbonDepartmentID=:gibbonDepartmentID';
        }

        $type = $_REQUEST['type'] ?? '';
        if ($type != '') {
            $dataEntry['type'] = $type;
            $and2 .= ' AND type=:type';
        }

        $enableGroupByTerm = $settingGateway->getSettingByScope('Markbook', 'enableGroupByTerm');
        if ($enableGroupByTerm == "Y") {
            $termDefault = '';
            $schoolYearTermGateway = $container->get(\Gibbon\Domain\School\SchoolYearTermGateway::class);
            $termCurrent = $schoolYearTermGateway->getCurrentTermByDate(date('Y-m-d'));
            $termDefault = (is_array($termCurrent) && $termCurrent['gibbonSchoolYearID'] == $gibbonSchoolYearIDFilter) ? $termCurrent['gibbonSchoolYearTermID'] : '';
            $gibbonSchoolYearTermID = $_REQUEST['gibbonSchoolYearTermID'] ?? $termDefault;
            if (!empty($gibbonSchoolYearTermID)) {
                $term = $schoolYearTermGateway->getByID($gibbonSchoolYearTermID);
                $dataEntry['firstDay'] = $term['firstDay'];
                $dataEntry['lastDay'] = $term['lastDay'];
                $and2 .= ' AND completeDate>=:firstDay AND completeDate<=:lastDay';
            }
        }

        echo '<p>';
        echo __('This page displays academic results for a student throughout their school career. Only subjects with published results are shown.');
        echo '</p>';

        // Render filter form
        $form = \Gibbon\Forms\Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setClass('noIntBorder w-full');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/student_view_details.php');
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('allStudents', $_GET['allStudents'] ?? '');
        $form->addHiddenValue('search', $_GET['search'] ?? '');
        $form->addHiddenValue('subpage', 'Markbook');

        $results = $container->get(\Gibbon\Domain\Departments\DepartmentGateway::class)->selectDepartmentsOfTypeLearningArea();
        $rowFilter = $form->addRow();
        $rowFilter->addLabel('gibbonDepartmentID', __('Learning Areas'));
        $rowFilter->addSelect('gibbonDepartmentID')
            ->fromArray(['*' => __('All Learning Areas')])
            ->fromResults($results)
            ->selected($gibbonDepartmentID);

        $dataSelect = ['gibbonPersonID' => $gibbonPersonID];
        $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as value, CONCAT(gibbonSchoolYear.name, ' (', gibbonYearGroup.name, ')') AS name FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber";
        $rowFilter = $form->addRow();
        $rowFilter->addLabel('gibbonSchoolYearID', __('School Years'));
        $rowFilter->addSelect('gibbonSchoolYearID')
            ->fromArray(['*' => __('All Years')])
            ->fromQuery($pdo, $sqlSelect, $dataSelect)
            ->selected($gibbonSchoolYearIDFilter);

        if ($enableGroupByTerm == "Y") {
            $dataSelect = [];
            $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as chainedTo, gibbonSchoolYearTerm.gibbonSchoolYearTermID as value, gibbonSchoolYearTerm.name FROM gibbonSchoolYearTerm JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYearTerm.sequenceNumber";
            $rowFilter = $form->addRow();
            $rowFilter->addLabel('gibbonSchoolYearTermID', __('Term'));
            $rowFilter->addSelect('gibbonSchoolYearTermID')
                ->fromQueryChained($pdo, $sqlSelect, $dataSelect, 'gibbonSchoolYearID')
                ->placeholder()
                ->selected($gibbonSchoolYearTermID ?? '');
        }

        $types = $settingGateway->getSettingByScope('Markbook', 'markbookType');
        if (!empty($types)) {
            $rowFilter = $form->addRow();
            $rowFilter->addLabel('type', __('Type'));
            $rowFilter->addSelect('type')
                ->fromString($types)
                ->selected($type)
                ->placeholder();
        }

        $details = $_GET['details'] ?? 'Yes';
        $form->addHiddenValue('details', 'No');
        $showHide = $form->getFactory()->createCheckbox('details')->addClass('details')->setValue('Yes')->checked($details)->inline(true)
            ->description(__('Show/Hide Details'))->wrap('&nbsp;<span class="text-xs italic inline-block">', '</span>');

        $rowFilter = $form->addRow();
        $rowFilter->addSearchSubmit($session, __('Clear Filters'), ['gibbonPersonID', 'allStudents', 'search', 'subpage'])->prepend($showHide->getOutput());

        echo $form->getOutput();
        ?>

        <script type="text/javascript">
            /* Show/Hide detail control */
            $(document).ready(function(){
                var updateDetails = function (){
                    if ($('input[name=details]:checked').val()=="Yes" ) {
                        $(".detailItem").slideDown("fast", $(".detailItem").css("{'display' : 'table-row'}"));
                    }
                    else {
                        $(".detailItem").slideUp("fast");
                    }
                }
                $(".details").click(updateDetails);
                updateDetails();
            });
        </script>

        <?php
        
        // Get highest action for markbook permissions
        $highestActionMarkbook = Access::get('Markbook', 'markbook_view');
        
        // Determine which query to use based on permissions
        if ($highestActionMarkbook->allows('View Markbook_myClasses')) {
            // Get class list (limited to a teacher's classes)
            $dataList['gibbonPersonIDTeacher'] = $session->get('gibbonPersonID');
            $dataList['gibbonPersonIDStudent'] = $gibbonPersonID;
            $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                JOIN gibbonCourseClassPerson as teacherParticipant ON (teacherParticipant.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                LEFT JOIN gibbonMarkbookTarget ON (
                    gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID
                    AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonIDStudent)
                LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                AND teacherParticipant.gibbonPersonID=:gibbonPersonIDTeacher
                $and ORDER BY course, class";
            $resultList = $connection2->prepare($sqlList);
            $resultList->execute($dataList);
        } else {
            // Get class list (all classes)
            $dataList['gibbonPersonIDStudent'] = $gibbonPersonID;
            $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                LEFT JOIN gibbonMarkbookTarget ON (
                    gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID
                    AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonIDStudent)
                LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                $and ORDER BY course, class";
            $resultList = $connection2->prepare($sqlList);
            $resultList->execute($dataList);
        }

        if ($resultList->rowCount() > 0) {
            while ($rowList = $resultList->fetch()) {
                try {
                    $dataEntry['gibbonPersonID'] = $gibbonPersonID;
                    $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                    if ($highestActionMarkbook->allows('View Markbook_viewMyChildrensClasses')) {
                        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                    } elseif ($highestActionMarkbook->allows('View Markbook_myMarks')) {
                        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' $and2 ORDER BY completeDate";
                    } else {
                        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' $and2 ORDER BY completeDate";
                    }
                    $resultEntry = $connection2->prepare($sqlEntry);
                    $resultEntry->execute($dataEntry);
                } catch (\PDOException $e) {
                }

                if ($resultEntry->rowCount() > 0) {
                    echo "<a name='".$rowList['gibbonCourseClassID']."'></a><h4>".$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';

                    $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                    $sqlTeachers = "SELECT title, surname, preferredName, gibbonCourseClassPerson.reportable FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                    $resultTeachers = $connection2->prepare($sqlTeachers);
                    $resultTeachers->execute($dataTeachers);

                    $teachers = '<p><b>'.__('Taught by:').'</b> ';
                    while ($rowTeachers = $resultTeachers->fetch()) {
                        if ($rowTeachers['reportable'] != 'Y') continue;
                        $teachers = $teachers.Format::name($rowTeachers['title'], $rowTeachers['preferredName'], $rowTeachers['surname'], 'Staff', false, false).', ';
                    }
                    $teachers = substr($teachers, 0, -2);
                    $teachers = $teachers.'</p>';
                    echo $teachers;

                    if ($rowList['target'] != '') {
                        echo "<div style='font-weight: bold' class='linkTop'>";
                        echo __('Target').': '.$rowList['target'];
                        echo '</div>';
                    }

                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo "<th style='width: 120px'>";
                    echo __('Assessment');
                    echo '</th>';
                    if ($enableModifiedAssessment == 'Y') {
                        echo "<th style='width: 75px'>";
                            echo __('Modified');
                        echo '</th>';
                    }
                    echo "<th style='width: 75px; text-align: center'>";
                    if ($attainmentAlternativeName != '') {
                        echo $attainmentAlternativeName;
                    } else {
                        echo __('Attainment');
                    }
                    echo '</th>';
                    if ($enableEffort == 'Y') {
                        echo "<th style='width: 75px; text-align: center'>";
                        if ($effortAlternativeName != '') {
                            echo $effortAlternativeName;
                        } else {
                            echo __('Effort');
                        }
                        echo '</th>';
                    }
                    echo '<th>';
                    echo __('Comment');
                    echo '</th>';
                    echo "<th style='width: 75px'>";
                    echo __('Submission');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    while ($rowEntry = $resultEntry->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;
                        ++$entryCount;

                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<span title='".htmlPrep($rowEntry['description'])."'><b><u>".$rowEntry['name'].'</u></b></span><br/>';
                        echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                        $unit = getUnit($connection2, $rowEntry['gibbonUnitID'], $rowEntry['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            echo $unit[0].'<br/>';
                        }
                        if (isset($unit[1])) {
                            if ($unit[1] != '') {
                                echo $unit[1].' '.__('Unit').'</i><br/>';
                            }
                        }
                        if ($rowEntry['completeDate'] != '') {
                            echo __('Marked on').' '.Format::date($rowEntry['completeDate']).'<br/>';
                        } else {
                            echo __('Unmarked').'<br/>';
                        }
                        echo $rowEntry['type'];
                        if ($rowEntry['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$rowEntry['attachment'])) {
                            echo " | <a 'title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$rowEntry['attachment']."'>".__('More info').'</a>';
                        }
                        echo '</span><br/>';
                        echo '</td>';
                        if ($enableModifiedAssessment == 'Y') {
                            if (!is_null($rowEntry['modifiedAssessment'])) {
                                echo "<td>";
                                echo Format::yesNo($rowEntry['modifiedAssessment']);
                                echo '</td>';
                            } else {
                                echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                echo __('N/A');
                                echo '</td>';
                            }
                        }
                        if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                            echo "<td class='dull' style='color: #bbb; text-align: center'>";
                            echo __('N/A');
                            echo '</td>';
                        } else {
                            echo "<td style='text-align: center'>";
                            $attainmentExtra = '';

                                $dataAttainment = array('gibbonScaleIDAttainment' => $rowEntry['gibbonScaleIDAttainment']);
                                $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDAttainment';
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
                            echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                            if ($rowEntry['gibbonRubricIDAttainment'] != '' and $enableRubrics =='Y') {
                                echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                            }
                            echo '</div>';
                            if ($rowEntry['attainmentValue'] != '') {
                                echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['attainmentDescriptor'])).'</b>'.__($attainmentExtra).'</div>';
                            }
                            echo '</td>';
                        }
                        if ($enableEffort == 'Y') {
                            if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                                echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                echo __('N/A');
                                echo '</td>';
                            } else {
                                echo "<td style='text-align: center'>";
                                $effortExtra = '';

                                    $dataEffort = array('gibbonScaleIDEffort' => $rowEntry['gibbonScaleIDEffort']);
                                    $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDEffort';
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
                                echo "<div $styleEffort>".$rowEntry['effortValue'];
                                if ($rowEntry['gibbonRubricIDEffort'] != '' and $enableRubrics =='Y') {
                                    echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                                }
                                echo '</div>';
                                if ($rowEntry['effortValue'] != '') {
                                    echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['effortDescriptor'])).'</b>'.__($effortExtra).'</div>';
                                }
                                echo '</td>';
                            }
                        }
                        if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                            echo "<td class='dull' style='color: #bbb; text-align: center'>";
                            echo __('N/A');
                            echo '</td>';
                        } else {
                            echo '<td>';
                            if ($rowEntry['comment'] != '') {
                                if (mb_strlen($rowEntry['comment']) > 200) {
                                    echo "<script type='text/javascript'>";
                                    echo '$(document).ready(function(){';
                                    echo "\$(\".comment-$entryCount\").hide();";
                                    echo "\$(\".show_hide-$entryCount\").fadeIn(1000);";
                                    echo "\$(\".show_hide-$entryCount\").click(function(){";
                                    echo "\$(\".comment-$entryCount\").fadeToggle(1000);";
                                    echo '});';
                                    echo '});';
                                    echo '</script>';
                                    echo '<span>'.mb_substr($rowEntry['comment'], 0, 200).'...<br/>';
                                    echo "<a title='".__('View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>".__('Read more').'</a></span><br/>';
                                } else {
                                    echo nl2br($rowEntry['comment']).'<br/>';
                                }
                            }
                            if ($rowEntry['response'] != '') {
                                echo "<a title='Uploaded Response' href='".$session->get('absoluteURL').'/'.$rowEntry['response']."'>".__('Uploaded Response').'</a><br/>';
                            }
                            echo '</td>';
                        }
                        if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                            echo "<td class='dull' style='color: #bbb; text-align: center'>";
                            echo __('N/A');
                            echo '</td>';
                        } else {

                                $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                $resultSub = $connection2->prepare($sqlSub);
                                $resultSub->execute($dataSub);
                            if ($resultSub->rowCount() != 1) {
                                echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                echo __('N/A');
                                echo '</td>';
                            } else {
                                echo '<td>';
                                $rowSub = $resultSub->fetch();
                                $resultWork = $container->get(\Gibbon\Domain\Planner\PlannerEntryHomeworkGateway::class)->selectHomeworkByStudent($rowEntry['gibbonPlannerEntryID'], $gibbonPersonID);

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
                                        echo "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$session->get('absoluteURL').'/'.$rowWork['location']."'>$linkText</a></span>";
                                    } elseif ($rowWork['type'] == 'Link') {
                                        echo "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                    } else {
                                        echo "<span title='$status. ".sprintf(__('Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                                    }
                                } else {
                                    // Get student data for date checking
                                    $studentData = $container->get(\Gibbon\Domain\Students\StudentGateway::class)->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();
                                    
                                    if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                        echo "<span title='Pending'>".__('Pending').'</span>';
                                    } else {
                                        if ($studentData && $studentData['dateStart'] > $rowSub['date']) {
                                            echo "<span title='".__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
                                        } else {
                                            if ($rowSub['homeworkSubmissionRequired'] == 'Required') {
                                                echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__('Incomplete').'</div>';
                                            } else {
                                                echo __('Not submitted online');
                                            }
                                        }
                                    }
                                }
                                echo '</td>';
                            }
                        }
                        echo '</tr>';
                        if ($rowEntry['commentOn'] == 'Y' && mb_strlen($rowEntry['comment']) > 200) {
                            echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                            echo '<td colspan=6>';
                            echo nl2br($rowEntry['comment']);
                            echo '</td>';
                            echo '</tr>';
                        }
                    }

                    $enableColumnWeighting = $settingGateway->getSettingByScope('Markbook', 'enableColumnWeighting');
                    $enableDisplayCumulativeMarks = $settingGateway->getSettingByScope('Markbook', 'enableDisplayCumulativeMarks');

                    if ($enableColumnWeighting == 'Y' && $enableDisplayCumulativeMarks == 'Y') {
                        $gibbon = $container->get('Gibbon\Gibbon');
                        renderStudentCumulativeMarks($gibbon, $pdo, $gibbonPersonID, $rowList['gibbonCourseClassID'], $gibbonSchoolYearTermID ?? '');
                    }

                    echo '</table>';
                }
            }
        }
        if ($entryCount < 1) {
            echo "<div class='message'>";
            echo __('There are no records to display.');
            echo '</div>';
        }
        
        return ob_get_clean();
    }
}
