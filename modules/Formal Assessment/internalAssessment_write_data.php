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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Get alternative header names
$settingGateway = $container->get(SettingGateway::class);
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$hasAttainmentName = ($attainmentAlternativeName != '' && $attainmentAlternativeNameAbrev != '');

$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');
$hasEffortName = ($effortAlternativeName != '' && $effortAlternativeNameAbrev != '');

echo "<script type='text/javascript'>";
    echo '$(document).ready(function(){';
        echo "autosize($('textarea'));";
    echo '});';
echo '</script>';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write_data.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if gibbonCourseClassID and gibbonInternalAssessmentColumnID specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        $gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'] ?? '';
        if ($gibbonCourseClassID == '' or $gibbonInternalAssessmentColumnID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            try {
                if ($highestAction == 'Write Internal Assessments_all') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

                    $data2 = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID);
                    $sql2 = "SELECT gibbonInternalAssessmentColumn.*, attainmentScale.name as scaleNameAttainment, attainmentScale.usage as usageAttainment, attainmentScale.lowestAcceptable as lowestAcceptableAttainment, effortScale.name as scaleNameEffort, effortScale.usage as usageEffort, effortScale.lowestAcceptable as lowestAcceptableEffort
                        FROM gibbonInternalAssessmentColumn
                        LEFT JOIN gibbonScale as attainmentScale ON (attainmentScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDAttainment)
                        LEFT JOIN gibbonScale as effortScale ON (effortScale.gibbonScaleID=gibbonInternalAssessmentColumn.gibbonScaleIDEffort)
                        WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID";
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);

                if ($result2->rowCount() != 1) {
                    $page->addError(__('The selected column does not exist, or you do not have access to it.'));
                } else {
                    //Let's go!
                    $class = $result->fetch();
                    $values = $result2->fetch();

                    $page->breadcrumbs
                        ->add(__('Write {courseClass} Internal Assessments', ['courseClass' => $class['course'].'.'.$class['class']]), 'internalAssessment_write.php', ['gibbonCourseClassID' => $gibbonCourseClassID])
                        ->add(__('Enter Internal Assessment Results'));

                    $page->return->addReturns(['error3' => __('Your request failed due to an attachment error.'), 'success0' => __('Your request was completed successfully.')]);

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

                    $form = Form::create('internalAssessment', $session->get('absoluteURL').'/modules/'.$session->get('module').'/internalAssessment_write_dataProcess.php?gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonInternalAssessmentColumnID='.$gibbonInternalAssessmentColumnID.'&address='.$session->get('address'));
                    $form->setFactory(DatabaseFormFactory::create($pdo));
                    $form->addHiddenValue('address', $session->get('address'));

                    $form->addRow()->addHeading('Assessment Details', __('Assessment Details'));

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->required()->maxLength(1000);

                    $row = $form->addRow();
                        $row->addLabel('file', __('Attachment'));
                        $row->addFileUpload('file')->setAttachment('attachment', $session->get('absoluteURL'), $values['attachment']);


                    if (count($students) == 0) {
                        $form->addRow()->addHeading('Students', __('Students'));
                        $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                    } else {
                        $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth colorOddEven noMargin noPadding noBorder');

                        $completeText = !empty($values['completeDate'])? __('Marked on').' '.Format::date($values['completeDate']) : __('Unmarked');
                        $detailsText = $values['type'];
                        if ($values['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$values['attachment'])) {
                            $detailsText .= " | <a title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$values['attachment']."'>".__('More info').'</a>';
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

                        $row->addWebLink(Format::name('', $student['preferredName'], $student['surname'], 'Student', true))
                            ->setURL($session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php')
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
                                $col->addFileUpload('response'.$count)->setAttachment('attachment'.$count, $session->get('absoluteURL'), $student['response'])->setMaxUpload(false);
                            }
                        }
                        $form->addHiddenValue($count.'-gibbonPersonID', $student['gibbonPersonID']);
                    }

                    $form->addHiddenValue('count', $count);

                    $form->addRow()->addHeading('Assessment Complete?', __('Assessment Complete?'));

                    $row = $form->addRow();
                        $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                        $row->addDate('completeDate');

                    $row = $form->addRow();
                        $row->addContent(getMaxUpload(true));
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }

        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write'));
    }
}
