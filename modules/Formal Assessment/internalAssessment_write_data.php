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

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$hasAttainmentName = ($attainmentAlternativeName != '' && $attainmentAlternativeNameAbrev != '');

$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');
$hasEffortName = ($effortAlternativeName != '' && $effortAlternativeNameAbrev != '');

echo "<script type='text/javascript'>";
    echo '$(document).ready(function(){';
        echo "autosize($('textarea'));";
    echo '});';
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write_data.php') == false) {
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
        $gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'];
        if ($gibbonCourseClassID == '' or $gibbonInternalAssessmentColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Write Internal Assessments_all') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";
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
                    $data2 = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID);
                    $sql2 = "SELECT gibbonInternalAssessmentColumn.*, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort
                        FROM gibbonInternalAssessmentColumn 
                        LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDAttainment)
                        LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDEffort)
                        WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID";
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo 'The selected column does not exist, or you do not have access to it.';
                    echo '</div>';
                } else {
                    //Let's go!
                    $class = $result->fetch();
                    $values = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/internalAssessment_write.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'Write').' '.$class['course'].'.'.$class['class'].' '.__($guid, 'Internal Assessments')."</a> > </div><div class='trailEnd'>".__($guid, 'Enter Internal Assessment Results').'</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed due to an attachment error.', 'success0' => 'Your request was completed successfully.'));
                    }

                    $hasAttainment = $values['attainment'] == 'Y';
                    $hasEffort = $values['effort'] == 'Y';
                    $hasComment = $values['comment'] == 'Y';
                    $hasUpload = $values['uploadedResponse'] == 'Y';

                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'today' => date('Y-m-d'));
                    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.dateStart, gibbonInternalAssessmentEntry.*
                        FROM gibbonCourseClassPerson 
                        JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                        LEFT JOIN gibbonInternalAssessmentEntry ON (gibbonInternalAssessmentEntry.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID)
                        WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                        AND gibbonCourseClassPerson.reportable='Y' AND gibbonCourseClassPerson.role='Student' 
                        AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) 
                        ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
                    $result = $pdo->executeQuery($data, $sql);
                    $students = ($result->rowCount() > 0)? $result->fetchAll() : array();

                    $form = Form::create('internalAssessment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/internalAssessment_write_dataProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonInternalAssessmentColumnID='.$gibbonInternalAssessmentColumnID.'&address='.$_SESSION[$guid]['address']);
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $form->addRow()->addHeading(__('Assessment Details'));

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->isRequired()->maxLength(1000);

                    $row = $form->addRow();
                        $row->addLabel('file', __('Attachment'));
                        $row->addFileUpload('file')->setAttachment('attachment', $_SESSION[$guid]['absoluteURL'], $values['attachment']);


                    if (count($students) == 0) {
                        $form->addRow()->addHeading(__('Students'));
                        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                    } else {
                        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding noBorder');

                        $completeText = !empty($values['completeDate'])? __('Marked on').' '.dateConvertBack($guid, $values['completeDate']) : __('Unmarked');
                        $detailsText = $values['type'];
                        if ($values['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$values['attachment'])) {
                            $detailsText .= " | <a title='".__('Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$values['attachment']."'>".__('More info').'</a>';
                        }

                        $header = $table->addHeaderRow();
                            $header->addTableCell(__('Student'))->rowSpan(2);
                            $header->addTableCell($values['name'])
                                ->setTitle($values['description'])
                                ->append('<br><span class="small emphasis" style="font-weight:normal;">'.$completeText.'</span>')
                                ->append('<br><span class="small emphasis" style="font-weight:normal;">'.$detailsText.'</span>')
                                ->setClass('textCenter')
                                ->colSpan(3);

                        $header = $table->addHeaderRow();
                            if ($hasAttainment) {
                                $scale = '';
                                if (!empty($values['gibbonScaleIDAttainment'])) {
                                    $form->addHiddenValue('scaleAttainment', $values['gibbonScaleIDAttainment']);
                                    $form->addHiddenValue('lowestAcceptableAttainment', $values['lowestAcceptableAttainment']);
                                    $scale = ' - '.$values['scaleNameAttainment'];
                                    $scale .= $values['usageAttainment']? ': '.$values['usageAttainment'] : '';
                                }
                                $header->addContent($hasAttainmentName? $attainmentAlternativeNameAbrev : __('Att'))
                                    ->setTitle(($hasAttainmentName? $attainmentAlternativeName : __('Attainment')).$scale)
                                    ->setClass('textCenter');
                            }
        
                            if ($hasEffort) {
                                $scale = '';
                                if (!empty($values['gibbonScaleIDEffort'])) {
                                    $form->addHiddenValue('scaleEffort', $values['gibbonScaleIDEffort']);
                                    $form->addHiddenValue('lowestAcceptableEffort', $values['lowestAcceptableEffort']);
                                    $scale = ' - '.$values['scaleNameEffort'];
                                    $scale .= $values['usageEffort']? ': '.$values['usageEffort'] : '';
                                }
                                $header->addContent($hasEffortName? $effortAlternativeNameAbrev : __('Eff'))
                                    ->setTitle(($hasEffortName? $effortAlternativeName : __('Effort')).$scale)
                                    ->setClass('textCenter');
                            }
        
                            if ($hasComment || $hasUpload) {
                                $header->addContent(__('Com'))->setTitle(__('Comment'))->setClass('textCenter');
                            }
                    }

                    foreach ($students as $index => $student) {
                        $count = $index+1;
                        $row = $table->addRow();
            
                        $row->addWebLink(formatName('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php')
                            ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                            ->addParam('subpage', 'Internal Assessment')
                            ->wrap('<strong>', '</strong>')
                            ->prepend($count.') ');

                        if ($hasAttainment) {
                            $attainment = $row->addSelectGradeScaleGrade($count.'-attainmentValue', $values['gibbonScaleIDAttainment'])->setClass('textCenter gradeSelect');
                            if (!empty($student['attainmentValue'])) $attainment->selected($student['attainmentValue']);
                        }
    
                        if ($hasEffort) {
                            $effort = $row->addSelectGradeScaleGrade($count.'-effortValue', $values['gibbonScaleIDEffort'])->setClass('textCenter gradeSelect');
                            if (!empty($student['effortValue'])) $effort->selected($student['effortValue']);
                        }
    
                        if ($hasComment || $hasUpload) {
                            $col = $row->addColumn()->addClass('stacked');

                            if ($hasComment) {
                                $col->addTextArea('comment'.$count)->setRows(6)->setValue($student['comment']);
                            }

                            if ($hasUpload) {
                                $col->addFileUpload('response'.$count)->setAttachment('attachment'.$count, $_SESSION[$guid]['absoluteURL'], $student['response'])->setMaxUpload(false);
                            }
                        }
                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
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

        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write');
    }
}
