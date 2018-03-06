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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Get settings
$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
$enableRawAttainment = getSettingByScope($connection2, 'Markbook', 'enableRawAttainment');

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$hasAttainmentName = ($attainmentAlternativeName != '' && $attainmentAlternativeNameAbrev != '');

$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');
$hasEffortName = ($effortAlternativeName != '' && $effortAlternativeNameAbrev != '');

// Get the sort order, if it exists
$studentOrderBy = (isset($_SESSION[$guid]['markbookOrderBy']))? $_SESSION[$guid]['markbookOrderBy'] : 'surname';
$studentOrderBy = (isset($_GET['markbookOrderBy']))? $_GET['markbookOrderBy'] : $studentOrderBy;

// This script makes entering raw marks easier, by capturing the enter key and moving to the next field insted of submitting
echo "<script type='text/javascript'>";
?>
    $(document).ready(function(){
        autosize($('textarea'));
    });

    // Map [Enter] key to work like the [Tab] key
    // Daniel P. Clark 2014
    // Modified for Gibbon Markbook Edit Data

    $(window).keydown(function(e) {

        // Set self as the current item in focus
        var self = $(':focus'),
          // Set the form by the current item in focus
          form = self.parents('form:eq(0)'),
          focusable;

        // Sometimes :focus selector doesnt work (in Chrome specifically)
        if (self.length == false) {
            self = e.target.value;
        }

        function enterKey(){

            if (e.which === 13 && !self.is('textarea,div[contenteditable=true]')) { // [Enter] key

                var index = self.attr('name').substr(0, self.attr('name').indexOf('-'));
                var attainmentNext = $( '#' + (parseInt(index) + 1) + '-attainmentValueRaw');

                //If not a regular hyperlink/button/textarea
                if ($.inArray(self, focusable) && (!self.is('a,button'))){
                    // Then prevent the default [Enter] key behaviour from submitting the form
                    e.preventDefault();
                } // Otherwise follow the link/button as by design, or put new line in textarea

                self.change();

                if (attainmentNext.length) {

                    attainmentNext.focus();
                    attainmentNext.select();

                    // Scroll to the next raw score
                    $('html,body').animate( {
                        scrollTop: $(document).scrollTop() + ( attainmentNext.offset().top - self.offset().top ),
                    }, 250);
                }

                return false;
            }
        }

        // We need to capture the [Shift] key and check the [Enter] key either way.
        if (e.shiftKey) { enterKey() } else { enterKey() }
    });

    <?php
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_data.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
        if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList, gibbonScale.name as targetGradeScale 
                            FROM gibbonCourse, gibbonCourseClass 
                            LEFT JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonCourseClass.gibbonScaleIDTarget) 
                            WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID 
                            AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID 
                            ORDER BY course, class";
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList, gibbonScale.name as targetGradeScale 
                            FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson 
                            LEFT JOIN gibbonScale ON (gibbonScale.gibbonScaleID=gibbonCourseClass.gibbonScaleIDTarget) 
                            WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID 
                            AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID 
                            AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' 
                            AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID 
                            ORDER BY course, class";
                }
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
                try {
                    $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql2 = "SELECT gibbonMarkbookColumn.*, gibbonUnit.name as unitName, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort 
                            FROM gibbonMarkbookColumn 
                            LEFT JOIN gibbonUnit ON (gibbonMarkbookColumn.gibbonUnitID=gibbonUnit.gibbonUnitID)
                            LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonMarkbookColumn.gibbonScaleIDAttainment)
                            LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonMarkbookColumn.gibbonScaleIDEffort)
                            WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID";
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected column does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $course = $result->fetch();
                    $values = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$course['course'].'.'.$course['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Enter Marks').'</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    //Setup for WP Comment Push
                    $wordpressCommentPush = getSettingByScope($connection2, 'Markbook', 'wordpressCommentPush');
                    if ($wordpressCommentPush == 'On') {
                        echo "<div class='warning'>";
                        echo __($guid, 'WordPress Comment Push is enabled: this feature allows you to push comments to student work submitted using a WordPress site. If you wish to push a comment, just select the checkbox next to the submitted work.');
                        echo '</div>';
                    }

                    // Added an info message to let uers know about enter / automatic calculations
                    if ($values['attainment'] == 'Y' && $values['attainmentRaw'] == 'Y' && !empty($values['attainmentRawMax']) && $enableRawAttainment == 'Y') {
                        echo '<p>';
                        echo __($guid, 'Press enter when recording marks to jump to the next student. Attainment values with a percentage grade scale will be calculated automatically. You can override the automatic value by selecting a different grade.');
                        echo '</p>';
                    }

                    echo "<div class='linkTop'>";
                    if ($values['gibbonPlannerEntryID'] != '') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=".$values['gibbonPlannerEntryID']."'>".__($guid, 'View Linked Lesson')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'View Linked Lesson')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> | ";
                    }
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo '</div>';

                    $columns = 1;

                    $hasTarget = !empty($course['targetGradeScale']);
                    $hasSubmission = false;
                    $hasAttainment = $values['attainment'] == 'Y';
                    $hasRawAttainment = $values['attainmentRaw'] == 'Y' && !empty($values['attainmentRawMax']) && $enableRawAttainment == 'Y';
                    $hasAttainmentRubric = $values['gibbonRubricIDAttainment'] != '' && $enableRubrics =='Y';
                    $hasEffort = $values['effort'] == 'Y';
                    $hasEffortRubric = $values['gibbonRubricIDEffort'] != '' && $enableRubrics =='Y';
                    $hasComment = $values['comment'] == 'Y';
                    $hasUpload = $values['uploadedResponse'] == 'Y';

                    $data = array(
                        'gibbonCourseClassID' => $gibbonCourseClassID, 
                        'gibbonMarkbookColumnID' => $values['gibbonMarkbookColumnID'], 
                        'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 
                        'today' => date('Y-m-d'),
                    );
                    $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy, title, surname, preferredName, gibbonPerson.gibbonPersonID, gibbonPerson.dateStart, gibbonStudentEnrolment.rollOrder, gibbonScaleGrade.value as targetScaleGrade, gibbonMarkbookEntry.attainmentValue, gibbonMarkbookEntry.attainmentValueRaw, gibbonMarkbookEntry.effortValue, gibbonMarkbookEntry.comment, gibbonMarkbookEntry.response
                            FROM gibbonCourseClassPerson 
                            JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                            JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                            LEFT JOIN gibbonMarkbookEntry ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonMarkbookEntry.gibbonPersonIDStudent=gibbonCourseClassPerson.gibbonPersonID)
                            LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                            LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                            AND gibbonPerson.status='Full' AND gibbonCourseClassPerson.role='Student'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                        
                    if ($studentOrderBy == 'rollOrder') {
                        $sql .= " ORDER BY ISNULL(rollOrder), rollOrder, surname, preferredName";
                    } else if ($studentOrderBy == 'preferredName') {
                        $sql .= " ORDER BY preferredName, surname";
                    } else {
                        $sql .= " ORDER BY surname, preferredName";
                    }
                    $result = $pdo->executeQuery($data, $sql);
                    $students = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

                    // WORK OUT IF THERE IS SUBMISSION
                    if (is_null($values['gibbonPlannerEntryID']) == false) {
                        try {
                            $dataSub = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                            $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                            $resultSub = $connection2->prepare($sqlSub);
                            $resultSub->execute($dataSub);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultSub->rowCount() == 1) {
                            $hasSubmission = true;
                            $rowSub = $resultSub->fetch();
                            $homeworkDueDateTime = $rowSub['homeworkDueDateTime'];
                            $lessonDate = $rowSub['date'];
                        }
                    }

                    // Grab student submissions
                    foreach ($students as &$student) {
                        $student['submission'] = '';

                        if ($hasSubmission) {
                            $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                            $sql = "SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC LIMIT 1";
                            $result = $pdo->executeQuery($data, $sql);
                            $submission = ($result->rowCount() > 0)? $result->fetch() : '';

                            $student['submission'] = renderStudentSubmission($submission, $homeworkDueDateTime, $lessonDate);

                            // Hook into WordpressCommentPush
                            if ($wordpressCommentPush == 'On' && $submission['type'] == 'Link') {
                                $student['submission'] .= "<div id='wordpressCommentPush$count' style='float: right'></div>";
                                $student['submission'] .= '<script type="text/javascript">';
                                $student['submission'] .= "$(\"#wordpressCommentPush$count\").load(\"".$_SESSION[$guid]['absoluteURL'].'/modules/Markbook/markbook_edit_dataAjax.php", { location: "'.$submission['location'].'", count: "'.$count.'" } );';
                                $student['submission'] .= '</script>';
                            }
                        }
                    }


                    $form = Form::create('markbookEditData', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/markbook_edit_dataProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonMarkbookColumnID='.$gibbonMarkbookColumnID.'&address='.$_SESSION[$guid]['address']);
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    if (count($students) == 0) {
                        $form->addRow()->addHeading(__('Students'));
                        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                    } else {
                        $attainmentScale = '';
                        if ($hasAttainment) {
                            $form->addHiddenValue('scaleAttainment', $values['gibbonScaleIDAttainment']);
                            $form->addHiddenValue('lowestAcceptableAttainment', $values['lowestAcceptableAttainment']);
                            $attainmentScale = ' - '.$values['scaleNameAttainment'];
                            $attainmentScale .= $values['usageAttainment']? ': '.$values['usageAttainment'] : '';
                        }

                        if ($hasAttainment && $hasRawAttainment) {
                            $form->addHiddenValue('attainmentRawMax', $values['attainmentRawMax']);

                            $scaleType = (strpos( strtolower($values['scaleNameAttainment']), 'percent') !== false)? '%' : '';
                            $form->addHiddenValue('attainmentScaleType', $scaleType);
                        }

                        $effortScale = '';
                        if ($hasEffort) {
                            $form->addHiddenValue('scaleEffort', $values['gibbonScaleIDEffort']);
                            $form->addHiddenValue('lowestAcceptableEffort', $values['lowestAcceptableEffort']);
                            $effortScale = ' - '.$values['scaleNameEffort'];
                            $effortScale .= $values['usageEffort']? ': '.$values['usageEffort'] : '';
                        }

                        // Create a rubric link object (for reusabilty)
                        $rubricLink = $form->getFactory()
                            ->createWebLink('<img title="'.__('Mark Rubric').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/rubric.png" style="margin-left:4px;"/>')
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php')
                            ->setClass('thickbox')
                            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                            ->addParam('gibbonMarkbookColumnID', $gibbonMarkbookColumnID)
                            ->addParam('width', '1100')
                            ->addParam('height', '550');

                        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding noBorder');

                        $detailsText = ($values['unitName'] != '')? $values['unitName'].'<br/>' : '';
                        $detailsText .= !empty($values['completeDate'])? __('Marked on').' '.dateConvertBack($guid, $values['completeDate']) : __('Unmarked');
                        $detailsText .= '<br/>'.$values['type'];

                        if ($values['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$values['attachment'])) {
                            $detailsText .= " | <a title='".__('Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$values['attachment']."'>".__('More info').'</a>';
                        }

                        $header = $table->addHeaderRow();

                        $header->addTableCell(__('Student'))->rowSpan(2);

                        $header->if($hasTarget)
                            ->addTableCell(__('Target'))
                            ->setTitle(__('Personalised target grade').' | '.$course['targetGradeScale'].' '.__('Scale'))
                            ->rowSpan(2)
                            ->addClass('textCenter smallColumn dataColumn noPadding')
                            ->wrap('<div class="verticalText">', '</div>');
                        
                        $header->addTableCell($values['name'])
                            ->setTitle($values['description'])
                            ->append('<br><span class="small emphasis" style="font-weight:normal;">'.$detailsText.'</span>')
                            ->setClass('textCenter')
                            ->colSpan(5);

                        $header = $table->addHeaderRow();

                        $header->if($hasSubmission)
                            ->addContent(__('Sub'))
                            ->setTitle(__('Submitted Work'))
                            ->setClass('textCenter');
                        
                        $header->if($hasAttainment && $hasRawAttainment)
                            ->addContent(__('Mark'))
                            ->setTitle(__('Raw Attainment Mark'))
                            ->setClass('textCenter');
                        
                        $header->if($hasAttainment)
                            ->addContent($hasAttainmentName? $attainmentAlternativeNameAbrev : __('Att'))
                            ->setTitle(($hasAttainmentName? $attainmentAlternativeName : __('Attainment')).$attainmentScale)
                            ->setClass('textCenter');
                        
                        $header->if($hasEffort)
                            ->addContent($hasEffortName? $effortAlternativeNameAbrev : __('Eff'))
                            ->setTitle(($hasEffortName? $effortAlternativeName : __('Effort')).$effortScale)
                            ->setClass('textCenter');
                        
                        $header->if($hasComment || $hasUpload)
                            ->addContent(__('Com'))
                            ->setTitle(__('Comment'))
                            ->setClass('textCenter');
                    }

                    $count = 0;
                    foreach ($students as $gibbonPersonID => $student) {
                        $count = $count+1;
                        $rollOrder = ($studentOrderBy == 'rollOrder')? $student['rollOrder'] : $count;

                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
                        
                        if (!$hasRawAttainment) {
                            $form->addHiddenValue($count.'-attainmentValueRaw', $student['attainmentValueRaw']);
                        }

                        $row = $table->addRow();
            
                        $row->addWebLink(formatName('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Markbook')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($rollOrder.') ');

                        $row->if($hasTarget)
                            ->addContent($student['targetScaleGrade']);

                        $row->if($hasSubmission)
                            ->addContent($student['submission']);

                        $row->if($hasAttainment && $hasRawAttainment)
                            ->addNumber($count.'-attainmentValueRaw')
                            ->setClass('smallColumn')
                            ->setValue($student['attainmentValueRaw'])
                            ->append(' / '.floatval($values['attainmentRawMax']));

                        $attainment = $row->if($hasAttainment)
                            ->addSelectGradeScaleGrade($count.'-attainmentValue', $values['gibbonScaleIDAttainment'])
                            ->setClass('textCenter gradeSelect')
                            ->selected($student['attainmentValue']);

                        if ($hasAttainment && $hasAttainmentRubric) {
                            $rubricLink->addParam('gibbonPersonID', $student['gibbonPersonID']);
                            $rubricLink->addParam('gibbonRubricID', $values['gibbonRubricIDAttainment']);
                            $rubricLink->addParam('type', 'attainment');
                            $attainment->append($rubricLink->getOutput());
                        }

                        $effort = $row->if($hasEffort)
                            ->addSelectGradeScaleGrade($count.'-effortValue', $values['gibbonScaleIDEffort'])
                            ->setClass('textCenter gradeSelect')
                            ->selected($student['effortValue']);

                        if ($hasEffort && $hasEffortRubric) {
                            $rubricLink->addParam('gibbonPersonID', $student['gibbonPersonID']);
                            $rubricLink->addParam('gibbonRubricID', $values['gibbonRubricIDEffort']);
                            $rubricLink->addParam('type', 'effort');
                            $effort->append($rubricLink->getOutput());
                        }

                        $col = $row->if($hasComment || $hasUpload)->addColumn()->addClass('stacked');

                            $col->if($hasComment)->addTextArea('comment'.$count)->setRows(6)->setValue($student['comment']);

                            $col->if($hasUpload)
                                ->addFileUpload('response'.$count)
                                ->setAttachment('attachment'.$count, $_SESSION[$guid]['absoluteURL'], $student['response'])
                                ->setMaxUpload(false);
                    }

                    $form->addHiddenValue('count', $count);

                    $form->addRow()->addHeading(__('Assessment Complete?'));

                    $row = $form->addRow();
                        $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                        $row->addDate('completeDate');

                    $row = $form->addRow();
                        $row->addContent(getMaxUpload($guid, true));
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);
        
                    echo $form->getOutput();
                }
            }
        }
    }

    // Print the sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'markbook_view.php');
}
?>
