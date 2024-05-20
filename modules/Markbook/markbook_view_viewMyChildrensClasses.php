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
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;

$page->breadcrumbs->add(__('View Markbook'));

// Lock the file so other scripts cannot call it
if (MARKBOOK_VIEW_LOCK !== sha1( $highestAction . $session->get('gibbonPersonID') ) . date('zWy') ) return;

//Get settings
$settingGateway = $container->get(SettingGateway::class);
$enableEffort = $settingGateway->getSettingByScope('Markbook', 'enableEffort');
$enableRubrics = $settingGateway->getSettingByScope('Markbook', 'enableRubrics');
$attainmentAltName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$effortAltName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');

$entryCount = 0;

$page->write('<p>'.__("This page shows your children's academic results throughout your school career. Only subjects with published results are shown.").'</p>');

//Test data access field for permission

    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
    $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
    $result = $connection2->prepare($sql);
    $result->execute($data);

if ($result->rowCount() < 1) {
    echo $page->getBlankSlate();
    return;
} else {
    //Get child list
    $count = 0;
    $options = array();
    while ($row = $result->fetch()) {
        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
        $resultChild = $connection2->prepare($sqlChild);
        $resultChild->execute($dataChild);
        while ($rowChild = $resultChild->fetch()) {
            $options[$rowChild['gibbonPersonID']]=Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
        }
    }

    if (count($options) == 0) {
        echo $page->getBlankSlate();
        return;
    } elseif (count($options) == 1) {
        $gibbonPersonID = key($options);
    } else {
        echo '<h2>';
        echo __('Choose Student');
        echo '</h2>';

        $gibbonPersonID = (isset($_GET['search']))? $_GET['search'] : null;

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth standardForm');

        $form->addHiddenValue('q', '/modules/Markbook/markbook_view.php');
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('search', __('Student'));
            $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    $showParentAttainmentWarning = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning');
    $showParentEffortWarning = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning');

    if (!empty($gibbonPersonID) and count($options) > 0) {
        //Confirm access to this student
        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'));
        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
        $resultChild = $connection2->prepare($sqlChild);
        $resultChild->execute($dataChild);
        if ($resultChild->rowCount() < 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $rowChild = $resultChild->fetch();

            if ($count > 1) {
                echo '<h2>';
                echo 'Filter & Options';
                echo '</h2>';
            }

            $and = '';
            $and2 = '';
            $dataList = array();
            $dataEntry = array();

            $gibbonSchoolYearID = isset($_REQUEST['gibbonSchoolYearID'])? $_REQUEST['gibbonSchoolYearID'] : $session->get('gibbonSchoolYearID');
            if ($gibbonSchoolYearID != '*') {
                $dataList['gibbonSchoolYearID'] = $gibbonSchoolYearID;
                $and .= ' AND gibbonSchoolYearID=:gibbonSchoolYearID';
            }

            $gibbonDepartmentID = isset($_REQUEST['gibbonDepartmentID'])? $_REQUEST['gibbonDepartmentID'] : '*';
            if ($gibbonDepartmentID != '*') {
                $dataList['gibbonDepartmentID'] = $gibbonDepartmentID;
                $and .= ' AND gibbonDepartmentID=:gibbonDepartmentID';
            }

            $type = isset($_REQUEST['type'])? $_REQUEST['type'] : '';
            if ($type != '') {
                $dataEntry['type'] = $type;
                $and2 .= ' AND type=:type';
            }

            $enableGroupByTerm = $settingGateway->getSettingByScope('Markbook', 'enableGroupByTerm');
            if ($enableGroupByTerm == "Y") {
                $termDefault = '';
                $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
                $termCurrent = $schoolYearTermGateway->getCurrentTermByDate(date('Y-m-d'));
                $termDefault = (is_array($termCurrent) && $termCurrent['gibbonSchoolYearID'] == $gibbonSchoolYearID) ? $termCurrent['gibbonSchoolYearTermID'] : '' ;
                $gibbonSchoolYearTermID = isset($_REQUEST['gibbonSchoolYearTermID']) ? $_REQUEST['gibbonSchoolYearTermID'] : $termDefault;
                if (!empty($gibbonSchoolYearTermID)) {
                    $term = $schoolYearTermGateway->getByID($gibbonSchoolYearTermID);
                    $dataEntry['firstDay'] = $term['firstDay'];
                    $dataEntry['lastDay'] = $term['lastDay'];
                    $and2 .= ' AND completeDate>=:firstDay AND completeDate<=:lastDay';
                }
            }

            $form = Form::create('filter', $session->get('absoluteURL').'/index.php','get');
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', '/modules/'.$session->get('module').'/markbook_view.php');
            $form->addHiddenValue('search', $gibbonPersonID);

            $sqlSelect = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
            $rowFilter = $form->addRow();
                $rowFilter->addLabel('gibbonDepartmentID', __('Learning Areas'));
                $rowFilter->addSelect('gibbonDepartmentID')
                    ->fromArray(array('*' => __('All Learning Areas')))
                    ->fromQuery($pdo, $sqlSelect)
                    ->selected($gibbonDepartmentID);

            $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
            $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as value, CONCAT(gibbonSchoolYear.name, ' (', gibbonYearGroup.name, ')') AS name FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber";
            $rowFilter = $form->addRow();
                $rowFilter->addLabel('gibbonSchoolYearID', __('School Years'));
                $rowFilter->addSelect('gibbonSchoolYearID')
                    ->fromArray(array('*' => __('All Years')))
                    ->fromQuery($pdo, $sqlSelect, $dataSelect)
                    ->selected($gibbonSchoolYearID);
            
            if ($enableGroupByTerm == "Y") {
                $dataSelect = [];
                $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as chainedTo, gibbonSchoolYearTerm.gibbonSchoolYearTermID as value, gibbonSchoolYearTerm.name FROM gibbonSchoolYearTerm JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYearTerm.sequenceNumber";
                $rowFilter = $form->addRow();
                    $rowFilter->addLabel('gibbonSchoolYearTermID', __('Term'));
                    $rowFilter->addSelect('gibbonSchoolYearTermID')
                        ->fromQueryChained($pdo, $sqlSelect, $dataSelect, 'gibbonSchoolYearID')
                        ->placeholder()
                        ->selected($gibbonSchoolYearTermID);
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

            $details = isset($_GET['details'])? $_GET['details'] : 'Yes';
            $form->addHiddenValue('details', 'No');
            $showHide = $form->getFactory()->createCheckbox('details')->addClass('details')->setValue('Yes')->checked($details)->inline(true)
                ->description(__('Show/Hide Details'))->wrap('&nbsp;<span class="small emphasis displayInlineBlock">', '</span>');

            $rowFilter = $form->addRow();
                $rowFilter->addSearchSubmit($session, __('Clear Filters'), array('search'))->prepend($showHide->getOutput());

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

            //Get class list

                $dataList['gibbonPersonID'] = $gibbonPersonID;
                $dataList['gibbonPersonID2'] = $gibbonPersonID;
                $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class";
                $resultList = $connection2->prepare($sqlList);
                $resultList->execute($dataList);
            if ($resultList->rowCount() > 0) {
                while ($rowList = $resultList->fetch()) {
                    try {
                        $dataEntry['gibbonPersonID'] = $gibbonPersonID;
                        $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                        $resultEntry = $connection2->prepare($sqlEntry);
                        $resultEntry->execute($dataEntry);
                    } catch (PDOException $e) {
                    }
                    if ($resultEntry->rowCount() > 0) {
                        echo '<h4>'.$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';


                            $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                            $sqlTeachers = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                            $resultTeachers = $connection2->prepare($sqlTeachers);
                            $resultTeachers->execute($dataTeachers);

                        $teachers = '<p><b>Taught by:</b> ';
                        while ($rowTeachers = $resultTeachers->fetch()) {
                            $teachers = $teachers.Format::name($rowTeachers['title'], $rowTeachers['preferredName'], $rowTeachers['surname'], 'Staff', false, false).', ';
                        }
                        $teachers = substr($teachers, 0, -2);
                        $teachers = $teachers.'</p>';
                        echo $teachers;

                        if ($rowList['target'] != '') {
                            echo "<p class='text-right mb-2 text-xs font-bold'>";
                                echo __('Target').': '.$rowList['target'];
                            echo '</p>';
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
                            echo (!empty($attainmentAltName))? $attainmentAltName : __('Attainment');
                        echo '</th>';
						if ($enableEffort == 'Y') {
                            echo "<th style='width: 75px; text-align: center'>";
                                echo (!empty($effortAltName))? $effortAltName : __('Effort');
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
                                if ($unit[1] != '') {
                                    echo '<i>'.$unit[1].' Unit</i><br/>';
                                }
                            }
                            if ($rowEntry['completeDate'] != '') {
                                echo __('Marked on').' '.Format::date($rowEntry['completeDate']).'<br/>';
                            } else {
                                echo __('Unmarked').'<br/>';
                            }
                            echo $rowEntry['type'];
                            if ($rowEntry['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$rowEntry['attachment'])) {
                                echo " | <a 'title='Download more information' href='".$session->get('absoluteURL').'/'.$rowEntry['attachment']."'>More info</a>";
                            }
                            echo '</span><br/>';
                            echo '</td>';
                            if ($enableModifiedAssessment == 'Y') {
                                if (!is_null($rowEntry['modifiedAssessment'])) {
                                    echo "<td>";
                                    echo Format::yesNo($rowEntry['modifiedAssessment']);
                                    echo '</td>';
                                }
                                else {
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

                                    $dataAttainment = array('gibbonScaleID' => $rowEntry['gibbonScaleIDAttainment']);
                                    $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                    $resultAttainment = $connection2->prepare($sqlAttainment);
                                    $resultAttainment->execute($dataAttainment);
                                if ($resultAttainment->rowCount() == 1) {
                                    $rowAttainment = $resultAttainment->fetch();
                                    $attainmentExtra = '<br/>'.__($rowAttainment['usage']);
                                }
                                $styleAttainment = "style='font-weight: bold'";
                                if ( ($rowEntry['attainmentConcern'] == 'Y' || $rowEntry['attainmentConcern'] == 'P') and $showParentAttainmentWarning == 'Y') {
                                    $styleAttainment = getAlertStyle($alert, $rowEntry['attainmentConcern'] );
                                }
                                echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                if ($rowEntry['gibbonRubricIDAttainment'] != '' AND $enableRubrics =='Y') {
                                    echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
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
                                        $styleEffort = getAlertStyle($alert, $rowEntry['effortConcern'] );
                                    }
                                    echo "<div $styleEffort>".$rowEntry['effortValue'];
                                    if ($rowEntry['gibbonRubricIDEffort'] != '' AND $enableRubrics =='Y') {
                                        echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                                    }
                                    echo '</div>';
                                    if ($rowEntry['effortValue'] != '') {
                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                                        echo '<b>'.htmlPrep(__($rowEntry['effortDescriptor'])).'</b>';
                                        if ($effortExtra != '') {
                                            echo __($effortExtra);
                                        }
                                        echo '</div>';
                                    }
                                    echo '</td>';
                                }
							}
                            if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                echo "<td class='dull' style='color: #bbb; text-align: left'>";
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
                                        echo "<a title='".__('View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>Read more</a></span><br/>";
                                    } else {
                                        echo nl2br($rowEntry['comment']);
                                    }
                                    echo '<br/>';
                                }
                                if ($rowEntry['response'] != '') {
                                    echo "<a title='Uploaded Response' href='".$session->get('absoluteURL').'/'.$rowEntry['response']."'>Uploaded Response</a><br/>";
                                }
                                echo '</td>';
                            }
                            if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                echo __('N/A');
                                echo '</td>';
                            } else {

                                    $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                    $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                    $resultSub = $connection2->prepare($sqlSub);
                                    $resultSub->execute($dataSub);
                                if ($resultSub->rowCount() != 1) {
                                    echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                    echo __('N/A');
                                    echo '</td>';
                                } else {
                                    echo '<td>';
                                    $rowSub = $resultSub->fetch();


                                        $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                        $resultWork = $connection2->prepare($sqlWork);
                                        $resultWork->execute($dataWork);
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
                                        if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                            echo "<span title='Pending'>".__('Pending').'</span>';
                                        } else {
                                            if ($rowChild['dateStart'] > $rowSub['date']) {
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
                            renderStudentCumulativeMarks($gibbon, $pdo, $gibbonPersonID, $rowList['gibbonCourseClassID'], $gibbonSchoolYearTermID ?? '');
                        }

                        echo '</table>';


                            $dataEntry2 = array('gibbonPersonIDStudent' => $session->get('gibbonPersonID'));
                            $sqlEntry2 = "SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' ORDER BY completeDate DESC, name";
                            $resultEntry2 = $connection2->prepare($sqlEntry2);
                            $resultEntry2->execute($dataEntry2);
                        if ($resultEntry2->rowCount() > 0) {
                            $session->set('sidebarExtra', "<h2 class='sidebar'>");
                            $session->set('sidebarExtra', $session->get('sidebarExtra').__('Recent Marks'));
                            $session->set('sidebarExtra', $session->get('sidebarExtra').'</h2>');

                            $session->set('sidebarExtra', $session->get('sidebarExtra').'<ol>');
                            $count = 0;

                            while ($rowEntry2 = $resultEntry2->fetch() and $count < 5) {
                                $session->set('sidebarExtra', $session->get('sidebarExtra')."<li><a href='#".$rowEntry2['gibbonMarkbookEntryID']."'>".$rowEntry['course'].'.'.$rowEntry['class']."<br/><span style='font-size: 85%; font-style: italic'>".$rowEntry['name'].'</span></a></li>');
                                ++$count;
                            }

                            $session->set('sidebarExtra', $session->get('sidebarExtra').'</ol>');
                        }
                    }
                }
            }
        }
    }
}
if ($entryCount < 1) {
    echo $page->getBlankSlate();
}
