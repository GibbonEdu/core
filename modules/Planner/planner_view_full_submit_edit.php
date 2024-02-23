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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full_submit_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $viewBy = $_GET['viewBy'] ?? '';
		$subView = $_GET['subView'] ?? '';
		$class = null;
		$date = null;
		$gibbonCourseClassID = null;
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        if ($viewBy == 'date') {
            $date = $_GET['date'] ?? '';
            if (!empty($_GET['dateHuman'])) {
                $date = Format::dateConvert($_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
        } elseif ($viewBy == 'class') {
            if (isset($_GET['class'])) {
                $class = $_GET['class'] ?? '';
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        }

        //Get class variable
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';

        if ($gibbonPlannerEntryID == '') {
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                    $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                } elseif ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = "SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                echo "<div class='warning'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $values = $result->fetch();

                // target of the planner
                $target = ($viewBy === 'class') ? $values['course'].'.'.$values['class'] : Format::date($date);

                // planner's parameters
                $params = [];
                if ($date != '') {
                    $params['date'] = $_GET['date'] ?? '';
                }
                if ($viewBy != '') {
                    $params['viewBy'] = $_GET['viewBy'] ?? '';
                }
                if ($gibbonCourseClassID != '') {
                    $params['gibbonCourseClassID'] = $gibbonCourseClassID;
                }
                $params['subView'] = $subView;
                $paramsVar = '&' . http_build_query($params); // for backward compatibile uses below (should be get rid of)

                $page->breadcrumbs
                    ->add(__('Planner for {classDesc}', [
                        'classDesc' => $target,
                    ]), 'planner.php', $params)
                    ->add(__('View Lesson Plan'), 'planner_view_full.php', $params + ['gibbonPlannerEntryID' => $gibbonPlannerEntryID])
                    ->add(__('Add Submission'));

                if ($_GET['submission'] != 'true' and $_GET['submission'] != 'false') {
                    echo "<div class='warning'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    $gibbonPersonID = '';
                    $gibbonPlannerEntryHomeworkID = '';

                    if ($_GET['submission'] == 'true') {
                        $submission = true;
                        $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'] ?? '';
                    } else {
                        $submission = false;
                        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
                    }

                    if (($submission == true and $gibbonPlannerEntryHomeworkID == '') or ($submission == false and $gibbonPersonID == '')) {
                        echo "<div class='warning'>";
                        echo __('You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        if ($submission == true) {
                            echo '<h2>';
                            echo __('Update Submission');
                            echo '</h2>';

                            
                                $dataSubmission = array('gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID);
                                $sqlSubmission = 'SELECT gibbonPlannerEntryHomework.*, surname, preferredName FROM gibbonPlannerEntryHomework JOIN gibbonPerson ON (gibbonPlannerEntryHomework.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';
                                $resultSubmission = $connection2->prepare($sqlSubmission);
                                $resultSubmission->execute($dataSubmission);

                            if ($resultSubmission->rowCount() != 1) {
                                echo "<div class='warning'>";
                                echo __('The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            } else {
                                $rowSubmission = $resultSubmission->fetch();

                                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/planner_view_full_submit_editProcess.php');

                                $form->addHiddenValue('search', '');
                                $form->addHiddenValue('params', $paramsVar);
                                $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                                $form->addHiddenValue('submission', 'true');
                                $form->addHiddenValue('gibbonPlannerEntryHomeworkID', $gibbonPlannerEntryHomeworkID);
                                $form->addHiddenValue('address', $session->get('address'));

                                $row = $form->addRow();
                                    $row->addLabel('student', __('Student'));
                                    $row->addTextField('student')->setValue(Format::name('', htmlPrep($rowSubmission['preferredName']), htmlPrep($rowSubmission['surname']), 'Student'))->readonly()->required();

                                $statuses = array(
                                    'On Time' => __('On Time'),
                                    'Late' => __('Late'),
                                    'Exemption' => __('Exemption')
                                );
                                $row = $form->addRow();
                                    $row->addLabel('status', __('Status'));
                                    $row->addSelect('status')->fromArray($statuses)->required()->selected($rowSubmission['status']);


                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();

                                echo $form->getOutput();
                            }
                        } else {
                            echo '<h2>';
                            echo __('Add Submission');
                            echo '</h2>';

                            
                                $dataSubmission = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlSubmission = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                                $resultSubmission = $connection2->prepare($sqlSubmission);
                                $resultSubmission->execute($dataSubmission);

                            if ($resultSubmission->rowCount() != 1) {
                                echo "<div class='warning'>";
                                echo 'There are no records to display.';
                                echo '</div>';
                            } else {
                                $rowSubmission = $resultSubmission->fetch();

                                $count = 0;
                                
                                    $dataVersion = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                    $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                    $resultVersion = $connection2->prepare($sqlVersion);
                                    $resultVersion->execute($dataVersion);
                                if ($resultVersion->rowCount() < 1) {
                                    $count = $resultVersion->rowCount();
                                }

                                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/planner_view_full_submit_editProcess.php');

                                $form->addHiddenValue('count', $count);
                                $form->addHiddenValue('lesson', $values['name']);
                                $form->addHiddenValue('search', '');
                                $form->addHiddenValue('params', $paramsVar);
                                $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                                $form->addHiddenValue('submission', 'false');
                                $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                $form->addHiddenValue('address', $session->get('address'));

                                $row = $form->addRow();
                                    $row->addLabel('student', __('Student'));
                                    $row->addTextField('student')->setValue(Format::name('', htmlPrep($rowSubmission['preferredName']), htmlPrep($rowSubmission['surname']), 'Student'))->readonly()->required();

                                $types = array(
                                    'None' => __('None')
                                );
                                if ($values['homeworkSubmissionType'] == 'Link' || $values['homeworkSubmissionType'] == 'Link/File') {
                                    $types['Link'] = __('Link');
                                }
                                if ($values['homeworkSubmissionType'] == 'File' || $values['homeworkSubmissionType'] == 'Link/File') {
                                    $types['File'] = __('File');
                                }
                                $row = $form->addRow();
                                    $row->addLabel('type', __('Type'));
                                    $row->addRadio('type')->fromArray($types)->required()->checked('None')->inline(true);

                                $versions = array();
                                if ($values['homeworkSubmissionDrafts'] > 0) {
                                    $versions['Draft'] = __('Draft');
                                }
                                $versions['Final'] = __('Final');
                                $row = $form->addRow();
                                    $row->addLabel('version', __('Version'));
                                    $row->addSelect('version')->fromArray($versions)->required();

                                $form->toggleVisibilityByClass('file')->onRadio('type')->when('File');
                                $row = $form->addRow()->addClass('file');
                                    $row->addLabel('file', __('Submit File'));
                                    $row->addFileUpload('file')->required();

                                    $form->toggleVisibilityByClass('link')->onRadio('type')->when('Link');
                                    $row = $form->addRow()->addClass('link');
                                    $row->addLabel('link', __('Submit Link'));
                                    $row->addURL('link')->required();

                                $statuses = array(
                                    'On Time' => __('On Time'),
                                    'Late' => __('Late'),
                                    'Exemption' => __('Exemption')
                                );
                                $row = $form->addRow();
                                    $row->addLabel('status', __('Status'));
                                    $row->addSelect('status')->fromArray($statuses)->required();


                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();

                                echo $form->getOutput();
                            }
                        }
                    }
                }
            }
        }
    }
}
