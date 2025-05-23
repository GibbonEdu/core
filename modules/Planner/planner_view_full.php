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
use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Attendance\AttendanceLogCourseClassGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Http\Url;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/../Attendance/moduleFunctions.php';
require_once __DIR__ . '/../Attendance/src/AttendanceView.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {

        $settingGateway = $container->get(SettingGateway::class);
        $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
        $homeworkNameSingular = $settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');
        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'] ?? '';
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'] ?? '';
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'] ?? date('Y-m-d');
            if (isset($_GET['dateHuman'])) {
                $date = Format::dateConvert($_GET['dateHuman']);
            }

            [$dateYear, $dateMonth, $dateDay] = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'] ?? '';
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        }
        $gibbonPersonID = null;

        //Proceed!
        //Get class variable
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
        if ($gibbonPlannerEntryID == '') {
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            $data = array();
            $gibbonPersonID = null;
            if (isset($_GET['search'])) {
                $gibbonPersonID = $_GET['search'] ?? '';
            }
            if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                if ($gibbonPersonID == '') {
                    echo "<div class='warning'>";
                    echo __('Your request failed because some required values were not unique.');
                    echo '</div>';
                } else {

                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);

                    if ($resultChild->rowCount() < 1) {
                        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                    } else {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $gibbonPersonID, 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                        $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance, gibbonCourseClassPerson.dateEnrolled FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance, NULL as dateEnrolled FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                    }
                }
            } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                $data = array('date' => $date, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance, gibbonCourseClassPerson.dateEnrolled FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonCourseClass.attendance, NULL as dateEnrolled FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=gibbonPersonID AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) ORDER BY date, timeStart";
            } elseif ($highestAction == 'Lesson Planner_viewOnly') {
                $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Other' AS role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonDepartmentID, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart";
                $teacher = false;
            }
            elseif ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses'  or $highestAction == 'Lesson Planner_viewOnly') {
                $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, 'Teacher' AS role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired, gibbonDepartmentID, gibbonCourseClass.attendance FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY date, timeStart";
                $teacher = false;

                    $dataTeacher = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID, 'date2' => $date);
                    $sqlTeacher = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
							AND role='Teacher'
							AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID)
						UNION
						(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.fields, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkTimeCap, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType, homeworkSubmissionRequired
						FROM gibbonPlannerEntry
						JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID)
						JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
						WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2)
						ORDER BY date, timeStart";
                    $resultTeacher = $connection2->prepare($sqlTeacher);
                    $resultTeacher->execute($dataTeacher);
                if ($resultTeacher->rowCount() > 0) {
                    $teacher = true;
                }
            }

            if (isset($sql)) {

                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() != 1) {
                    echo "<div class='warning'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $values = $result->fetch();
                    $gibbonDepartmentID = null;
                    if (isset($values['gibbonDepartmentID'])) {
                        $gibbonDepartmentID = $values['gibbonDepartmentID'];
                    }

                    $gibbonUnitID = $values['gibbonUnitID'];

                    //Get gibbonUnitClassID

                        $dataUnitClass = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                        $sqlUnitClass = 'SELECT gibbonUnitClassID FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID';
                        $resultUnitClass = $connection2->prepare($sqlUnitClass);
                        $resultUnitClass->execute($dataUnitClass);
                    if ($resultUnitClass->rowCount() == 1) {
                        $rowUnitClass = $resultUnitClass->fetch();
                        $gibbonUnitClassID = $rowUnitClass['gibbonUnitClassID'];
                    }

                    // target of the planner
                    $target = ($viewBy === 'class') ? $values['course'].'.'.$values['class'] : Format::date($date);

                    // planner's parameters
                    $params = [];
                    $params['gibbonPlannerEntryID'] = $gibbonPlannerEntryID;
                    $params['gibbonCourseClassID'] = $values['gibbonCourseClassID'];
                    $params['date'] = $values['date'];
                    $params['viewBy'] = $viewBy;
                    $params['subView'] = $subView;
                    $params['search'] = $gibbonPersonID;
                    $paramsVar = '&' . http_build_query($params); // for backward compatibile uses below (should be get rid of)

                    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

                    $page->breadcrumbs
                        ->add(__('Planner for {classDesc}', [
                            'classDesc' => $target,
                        ]), 'planner.php', $params)
                        ->add(__('View Lesson Plan'));

                    $returns = array();
                    $returns['error6'] = __('An error occured with your submission, most likely because a submitted file was too large.');
                    $returns['error7'] = __('The specified date is in the future: it must be today or earlier.');
                    $page->return->addReturns($returns);

                    if ($gibbonCourseClassID == '') {
                        $gibbonCourseClassID = $values['gibbonCourseClassID'];
                    }
                    if (($values['role'] == 'Student' and $values['viewableStudents'] == 'N') and ($highestAction == 'Lesson Planner_viewMyChildrensClasses' and $values['viewableParents'] == 'N')) {
                        echo "<div class='warning'>";
                        echo __('The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        echo "<div style='height:50px'>";
                        echo '<h2>';
                        if (strlen($values['name']) <= 34) {
                            echo $values['name'].'<br/>';
                        } else {
                            echo substr($values['name'], 0, 34).'...<br/>';
                        }
                        $unit = getUnit($connection2, $values['gibbonUnitID'], $values['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            if ($unit[0] != '') {
                                if ($unit[1] != '') {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>$unit[1] ".__('Unit:').' '.$unit[0].'</div>';
                                    $unitType = $unit[1];
                                } else {
                                    echo "<div style='font-weight: normal; font-style: italic; font-size: 60%; margin-top: 0px'>".__('Unit:').' '.$unit[0].'</div>';
                                }
                            }
                        }
                        echo '</h2>';

                        echo '</div>';

                        
                    
                        $dataMarkbook = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlMarkbook = 'SELECT gibbonMarkbookColumnID FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                        $gibbonMarkbookColumnID = $pdo->selectOne($sqlMarkbook, $dataMarkbook);
                           

                        // Details Table
                        $table = DataTable::createDetails('overview');

                        if (strstr($values['role'], 'Guest') == false) {
                            $previousLesson = $plannerEntryGateway->getPreviousLesson($gibbonCourseClassID, $values['date'], $values['timeStart'], $values['role']);
                            $nextLesson = $plannerEntryGateway->getNextLesson($gibbonCourseClassID, $values['date'], $values['timeStart'], $values['role']);

                            $form = Form::createBlank('nav', '');
                            $form->addHiddenValue('address', $session->get('address'));
                            $form->addClass('flex-grow flex justify-start items-end');
                        
                            $navUrl = Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams($params);

                            $row = $form->addRow();
                            $col = $row->addColumn()->addClass('flex-1 flex items-center');
                                $col->addButton(__('Previous Lesson'))
                                    ->groupAlign('left')
                                    ->addClass('text-xs')
                                    ->setAction(!empty($previousLesson) 
                                        ? $navUrl->withQueryParam('gibbonPlannerEntryID', $previousLesson['gibbonPlannerEntryID']) : '');
                                $col->addButton(__('Next Lesson'))
                                    ->groupAlign('right')
                                    ->addClass('text-xs')
                                    ->setAction(!empty($nextLesson) ? 
                                        $navUrl->withQueryParam('gibbonPlannerEntryID', $nextLesson['gibbonPlannerEntryID']) : '');
                            
                            $table->addHeaderContent($form->getOutput());
                        }

                        if ($values['role'] == 'Teacher') {
                            $table->addHeaderContent("<input type='checkbox' x-model='globalShowHide' name='confidentialPlan' class='confidentialPlan rounded' value='Yes' /><span title='".__('Includes student data & teacher\'s notes')."' class='text-xs italic mr-2' > ".__('Show Confidential Data').'</span>');
                        }

                        if (!empty($values['gibbonUnitID'])) {
                            $table->addHeaderAction('book-open', __('Unit Overview'))
                                ->setURL('/modules/Planner/planner_unitOverview.php')
                                ->addParam('gibbonUnitID', $values['gibbonUnitID'])
                                ->addParams($params)
                                ->displayLabel();
                        }

                        if ($values['role'] == 'Teacher') {
                            if (!empty($gibbonMarkbookColumnID)) {
                                $table->addHeaderAction('markbook', __('Linked Markbook'))
                                    ->setURL('/modules/Markbook/markbook_edit_data.php')
                                    ->addParam('gibbonMarkbookColumnID', $gibbonMarkbookColumnID)
                                    ->addParams($params)
                                    ->displayLabel();
                            }

                            $table->addHeaderAction('edit', __('Edit'))
                                ->setURL('/modules/Planner/planner_edit.php')
                                ->addParams($params)
                                ->displayLabel();
                        }

                        $col = $table->addColumn('Basic Information');

                        $col->addColumn('class', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
                        $col->addColumn('date', __('Date'))->format(Format::using('date', 'date'));
                        $col->addColumn('time', __('Time'))->format(Format::using('timeRange', ['timeStart', 'timeEnd']));

                        $col->addColumn('summary', __('Summary'))->addClass('col-span-3');

                        // CUSTOM FIELDS
                        $container->get(CustomFieldHandler::class)->addCustomFieldsToTable($table, 'Lesson Plan', [], $values['fields'] ?? '');

                        echo $table->render([$values]);

                        //Lesson outcomes
                        $dataOutcomes = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                        $sqlOutcomes = "SELECT scope, name, nameShort, category, gibbonYearGroupIDList, sequenceNumber, content FROM gibbonPlannerEntryOutcome JOIN gibbonOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND active='Y' ORDER BY (sequenceNumber='') ASC, sequenceNumber, category, name";
                        $resultOutcomes = $connection2->prepare($sqlOutcomes);
                        $resultOutcomes->execute($dataOutcomes);

                        if ($resultOutcomes->rowCount() > 0) {
                            echo '<h2>'.__('Lesson Outcomes').'</h2>';
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo __('Scope');
                            echo '</th>';
                            echo '<th>';
                            echo __('Category');
                            echo '</th>';
                            echo '<th>';
                            echo __('Name');
                            echo '</th>';
                            echo '<th>';
                            echo __('Year Groups');
                            echo '</th>';
                            echo '<th>';
                            echo __('Actions');
                            echo '</th>';
                            echo '</tr>';

                            $count = 0;
                            $rowNum = 'odd';
                            while ($rowOutcomes = $resultOutcomes->fetch()) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['scope'].'</b><br/>';
                                if ($rowOutcomes['scope'] == 'Learning Area' and $gibbonDepartmentID != '') {

                                    $dataLearningArea = array('gibbonDepartmentID' => $gibbonDepartmentID);
                                    $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                                    $resultLearningArea = $connection2->prepare($sqlLearningArea);
                                    $resultLearningArea->execute($dataLearningArea);
                                    if ($resultLearningArea->rowCount() == 1) {
                                        $rowLearningAreas = $resultLearningArea->fetch();
                                        echo "<span style='font-size: 75%; font-style: italic'>".$rowLearningAreas['name'].'</span>';
                                    }
                                }
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['category'].'</b><br/>';
                                echo '</td>';
                                echo '<td>';
                                echo '<b>'.$rowOutcomes['nameShort'].'</b><br/>';
                                echo "<span style='font-size: 75%; font-style: italic'>".$rowOutcomes['name'].'</span>';
                                echo '</td>';
                                echo '<td>';
                                echo getYearGroupsFromIDList($guid, $connection2, $rowOutcomes['gibbonYearGroupIDList']);
                                echo '</td>';
                                echo '<td>';
                                echo "<script type='text/javascript'>";
                                echo 'htmx.onLoad(function (content) {';
                                echo "\$(\".description-$count\").hide();";
                                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                                echo "\$(\".show_hide-$count\").click(function(){";
                                echo "\$(\".description-$count\").fadeToggle(1000);";
                                echo '});';
                                echo '});';
                                echo '</script>';
                                if ($rowOutcomes['content'] != '') {
                                    echo "<a title='".__('View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                                }
                                echo '</td>';
                                echo '</tr>';
                                if ($rowOutcomes['content'] != '') {
                                    echo "<tr class='description-$count' id='description-$count'>";
                                    echo '<td colspan=6>';
                                    echo $rowOutcomes['content'];
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                echo '</tr>';

                                ++$count;
                            }
                            echo '</table>';
                        }

                        // Get Lesson Planner Hooks
                        $hookGateway = $container->get(HookGateway::class);
                        $hooks = $hookGateway->selectHooksByType('Lesson Planner')->fetchGroupedUnique();
                        foreach ($hooks as $hook) {
                            $options = unserialize($hook['options']);

                            // Check for permission to hook
                            $hookPermission = $hookGateway->getHookPermission($hook['gibbonHookID'], $session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                            if (!empty($options) && !empty($hookPermission)) {
                                $include = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
                                if (!file_exists($include)) {
                                    echo Format::alert(__('The selected page cannot be displayed due to a hook error.'), 'error');
                                } else {
                                    include $include;
                                }
                            }
                        }


                        //Get Smart Blocks
                        $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlBlocks = "SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber";
                        $blocks = $pdo->select($sqlBlocks, $dataBlocks)->fetchAll();

                        // Get TT information
                        $ttPeriod = $container->get(TimetableDayDateGateway::class)->getTimetabledPeriodByClassAndTime($gibbonCourseClassID, $values['date'], $values['timeStart'], $values['timeEnd']);

                        // LESSON CONTENTS
                        $form = Form::createBlank('smartBlockCompletion', $session->get('absoluteURL').'/modules/Planner/planner_view_full_smartProcess.php');

                        $form->setTitle(__('Lesson Content'));
                        $description = '';
                        if (!empty($values['description'])) {
                            $description = '<div class="unit-block rounded p-8 mb-4 border bg-gray-100 text-gray-700">'.$values['description'].'</div>';
                        }

                        if (empty($blocks) and empty($values['description'])) {
                            $description = Format::alert(__('This lesson has not had any content assigned to it.'));
                        }

                        if (!empty($values['teachersNotes']) and ($highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses') and ($values['role'] == 'Teacher' or $values['role'] == 'Assistant' or $values['role'] == 'Technician')) {
                            $description .= '<div x-cloak x-show="globalShowHide" x-transition id="teachersNotes" class="unit-block rounded p-8 mb-4 border bg-blue-50 text-gray-700"><h3 class="m-0">'.__('Teacher\'s Notes').'</h3>'.$values['teachersNotes'].'</div>';
                        }

                        $form->setDescription($description);

                        if (!empty($blocks)) {
                            $form->addHiddenValue('address', $session->get('address'));
                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                            $form->addHiddenValue('date', $values['date']);
                            $form->addHiddenValue('mode', 'view');
                            $form->addHiddenValues($params);

                            if ($values['role'] == 'Teacher' and $teacher == true) {
                                $form->addHeaderAction('blocks', __m('Edit Blocks'))
                                    ->setURL('/modules/Planner/planner_edit.php', '#SmartBlocks')
                                    ->addParams($params)
                                    ->displayLabel()
                                    ->prepend(__('Smart Blocks').': ');

                                if (!empty($values['gibbonUnitID'])) {
                                    $form->addHeaderAction('unit', __m('Edit Unit'))
                                        ->setURL('/modules/Planner/units_edit_working.php')
                                        ->addParams($params)
                                        ->addParam('gibbonCourseID', $values['gibbonCourseID'] ?? '')
                                        ->addParam('gibbonUnitID', $values['gibbonUnitID'] ?? '')
                                        ->addParam('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'))
                                        ->addParam('subView', $subView ?? '')
                                        ->displayLabel();
                                }
                            }

                            $templateView = $container->get(View::class);
                            $blockCount = 0;

                            foreach ($blocks as $block) {
                                $blockContent = $templateView->fetchFromTemplate('ui/unitBlock.twig.html', $block + [
                                    'roleCategory' => $roleCategory, 'gibbonPersonID' => $session->get('username') ?? '', 'blockCount' => $blockCount, 'checked' => ($block['complete'] == 'Y' ? 'checked' : ''), 'role' => $values['role'], 'teacher' => $values['role'] == 'Teacher' and $teacher == true ?? ''
                                ]);

                                $form->addRow()->addContent($blockContent);
                                $blockCount++;
                            }

                            if ($values['role'] == 'Teacher' and $teacher == true) {
                                $row = $form->addRow()->addSubmit();
                            }
                        }

                        echo $form->getOutput();

                        echo "<h2 style='padding-top: 30px'>".__($homeworkNamePlural).'</h2>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                        if ($values['role'] == 'Student') {
                            echo "<tr class='break'>";
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__('Teacher Recorded {homeworkName}', ['homeworkName' => __($homeworkNameSingular)]).'</h3>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<tr>';
                        echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                        if ($values['homework'] == 'Y') {

                            if ($values['role'] == 'Student' && !empty($values['homeworkTimeCap'])) {
                                echo Format::alert(__('Your teacher has indicated a <b><u>{timeCap} minute</u></b> time cap for this work. Aim to spend no more than {timeCap} minutes on this {homeworkName} and let your teacher know if you were unable to complete it within this time frame.', ['timeCap' => $values['homeworkTimeCap'], 'homeworkName' => mb_strtolower(__($homeworkNameSingular))]), 'message');
                            }

                            // Account for students who joined the class after the lesson date
                            if ($values['role'] == 'Student' && !empty($values['dateEnrolled']) && $values['dateEnrolled'] >= $values['date']) {
                                echo Format::alert(__('This lesson occurred prior to enrolling in the class. Due dates and incomplete work will not be counted for this lesson.'), 'message');
                            }

                            echo Format::alert(__('Due on {date} at {time}.', ['date' => Format::date(substr($values['homeworkDueDateTime'], 0, 10)), 'time' => substr($values['homeworkDueDateTime'], 11, 5)]), 'warning');

                            if (!empty($values['homeworkTimeCap'])) {
                                echo Format::alert(__('A time cap of <b><u>{timeCap} minute</u></b> has been set for this work.', ['timeCap' => $values['homeworkTimeCap']]), 'message');
                            }

                            echo $values['homeworkDetails'].'<br/>';
                            if ($values['homeworkSubmission'] == 'Y') {
                                if ($values['role'] == 'Student' and ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses')) {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Online Submission').'</span><br/>';
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';
                                    if (date('Y-m-d') < $values['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.Format::date($values['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
                                        $dataVersion = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                                        $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY count';
                                        $resultVersion = $connection2->prepare($sqlVersion);
                                        $resultVersion->execute($dataVersion);

                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Count') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
													</td>
													<?php
													if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
														echo '<th>';
														echo __('Actions').'<br/>';
														echo '</td>';
													}
												?>
												</tr>
												<?php
												while ($rowVersion = $resultVersion->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>

														<td>
															<?php echo $rowVersion['count'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['version'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['status'] ?><br/>
														</td>
														<td>
															<?php echo substr($rowVersion['timestamp'], 11, 5).' '.Format::date(substr($rowVersion['timestamp'], 0, 10)) ?><br/>
														</td>
														<td style='max-width: 180px; word-wrap: break-word;'>
															<?php
															if ($rowVersion['type'] == 'File') {
                                                                $rowVersion['location'] = str_replace(['?','#'], ['%3F', '%23'], $rowVersion['location'] ?? '');
																echo "<a href='".$session->get('absoluteURL').'/'.$rowVersion['location']."' target='_blank'>".$rowVersion['location'].'</a>';
															} else {
                                                                if (strlen($rowVersion['location'])<=40) {
                                                                    echo "<a href='".$rowVersion['location']."' target='_blank'>".$rowVersion['location'].'</a>';
                                                                }
                                                                else {
                                                                    echo "<a href='".$rowVersion['location']."' target='_blank'>".substr($rowVersion['location'], 0, 50).'...'.'</a>';
                                                                }
															}
														?>
														</td>
														<?php
														if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
															echo '<td>';
															echo "<a onclick='return confirm(\"".__('Are you sure you wish to delete this record?')."\")' href='".$session->get('absoluteURL')."/modules/Planner/planner_view_full_submit_studentDeleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a><br/>";
															echo '</td>';
														}
													?>
													</tr>
													<?php
													$latestVersion = $rowVersion['version'];
												}
											?>
											</table>
											<?php
                                        }

                                        if ($latestVersion != 'Final') {
                                            $status = 'On Time';
                                            if (date('Y-m-d H:i:s') > $values['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__('The due date has passed. Your work will be marked as late.').'</span><br/>';
                                                $status = 'Late';
                                            }

                                            // SUBMIT HOMEWORK - Teacher Recorded
                                            $form = Form::create('homeworkTeacher', $session->get('absoluteURL').'/modules/Planner/planner_view_full_submitProcess.php?address='.$_GET['q'].$paramsVar.'&gibbonPlannerEntryID='.$values['gibbonPlannerEntryID']);

                                            $form->addHiddenValue('address', $session->get('address'));
                                            $form->addHiddenValue('lesson', $values['name']);
                                            $form->addHiddenValue('count', $count);
                                            $form->addHiddenValue('status', $status);
                                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                                            $form->addHiddenValue('currentDate', $values['date']);

                                            $row = $form->addRow();
                                                $row->addLabel('type', __('Type'));

                                            if ($values['homeworkSubmissionType'] == 'Link') {
                                                $row->addTextField('type')->readonly()->required()->setValue('Link');
                                            } elseif ($values['homeworkSubmissionType'] == 'File') {
                                                $row->addTextField('type')->readonly()->required()->setValue('File');
                                            } else {
                                                $types = ['Link' => __('Link'), 'File' => __('File')];
                                                $row->addRadio('type')->fromArray($types)->inline()->required()->checked('Link');

                                                $form->toggleVisibilityByClass('submitFile')->onRadio('type')->when('File');
                                                $form->toggleVisibilityByClass('submitLink')->onRadio('type')->when('Link');
                                            }

                                            if ($values['homeworkSubmissionDrafts'] > 0 and $status != 'Late' and $resultVersion->rowCount() < $values['homeworkSubmissionDrafts']) {
                                                $versions = ['Draft' => __('Draft'), 'Final' => __('Final')];
                                            } else {
                                                $versions = ['Final' => __('Final')];
                                            }

                                            $row = $form->addRow();
                                                $row->addLabel('version', __('Version'));
                                                $row->addSelect('version')->fromArray($versions)->required();

                                            // File
                                            if ($values['homeworkSubmissionType'] != 'Link') {
                                                $fileUploader = $container->get(FileUploader::class);
                                                $row = $form->addRow()->addClass('submitFile');
                                                    $row->addLabel('file', __('Submit File'));
                                                    $row->addFileUpload('file')->accepts($fileUploader->getFileExtensions())->required();
                                            }

                                            // Link
                                            if ($values['homeworkSubmissionType'] != 'File') {
                                                $row = $form->addRow()->addClass('submitLink');
                                                    $row->addLabel('link', __('Submit Link'));
                                                    $row->addURL('link')->maxLength(255)->required();
                                            }


                                            $row = $form->addRow();
                                                $row->addSubmit();

                                            echo $form->getOutput();
                                        }
                                    }
                                } elseif ($values['role'] == 'Student' and $highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Online Submission')."</span><br/>";
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';
                                    if (date('Y-m-d') < $values['homeworkSubmissionDateOpen']) {
                                        echo '<i>Submission opens on '.Format::date($values['homeworkSubmissionDateOpen']).'</i>';
                                    } else {
                                        //Check previous submissions!
                                        $dataVersion = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $values['gibbonPlannerEntryID']);
                                        $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                        $resultVersion = $connection2->prepare($sqlVersion);
                                        $resultVersion->execute($dataVersion);
                                        $latestVersion = '';
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultVersion->rowCount() < 1) {
                                            if (date('Y-m-d H:i:s') > $values['homeworkDueDateTime']) {
                                                echo "<span style='color: #C00; font-stlye: italic'>".__('The due date has passed, and no work has been submitted.').'</span><br/>';
                                            }
                                        } else {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Count') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
													</td>
												</tr>
												<?php
												while ($rowVersion = $resultVersion->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>
														<td>
															<?php echo $rowVersion['count'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['version'] ?><br/>
														</td>
														<td>
															<?php echo $rowVersion['status'] ?><br/>
														</td>
														<td>
															<?php echo substr($rowVersion['timestamp'], 11, 5).' '.Format::date(substr($rowVersion['timestamp'], 0, 10)) ?><br/>
														</td>
														<td style='max-width: 180px; word-wrap: break-word;'>
															<?php
															if ($rowVersion['type'] == 'File') {
                                                                $rowVersion['location'] = str_replace(['?','#'], ['%3F', '%23'], $rowVersion['location'] ?? '');
																echo "<a href='".$session->get('absoluteURL').'/'.$rowVersion['location']."' target='_blank'>".$rowVersion['location'].'</a>';
															} else {
                                                                if (strlen($rowVersion['location'])<=40) {
                                                                    echo "<a href='".$rowVersion['location']."' target='_blank'>".$rowVersion['location'].'</a>';
                                                                }
                                                                else {
                                                                    echo "<a href='".$rowVersion['location']."' target='_blank'>".substr($rowVersion['location'], 0, 40).'...'.'</a>';
                                                                }
															}
                                                            ?>
														</td>
													</tr>
													<?php
													$latestVersion = $rowVersion['version'];
												}
												?>
											</table>
											<?php
                                        }
                                    }
                                } elseif ($values['role'] == 'Teacher') {
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Online Submission').'</span><br/>';
                                    echo '<i>'.__('Online submission is {required} for this {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'required' => '<b>'.strtolower($values['homeworkSubmissionRequired']).'</b>']).'</i><br/>';

                                    $teacherViewOnlyAccess = $highestAction == 'Lesson Planner_viewAllEditMyClasses' || $highestAction == "Lesson Planner_viewEditAllClasses";
                                    if ($teacher || $teacherViewOnlyAccess) {

                                        //List submissions
                                        $dataClass = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'today' => date('Y-m-d'), 'gibbonTTDayRowClassID' => ($ttPeriod['gibbonTTDayRowClassID'] ?? ''));
                                        $sqlClass = "SELECT gibbonCourseClassPerson.*, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.dateStart, gibbonPerson.dateEnd FROM gibbonCourseClassPerson 
                                        INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID 
                                        LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonTTDayRowClassException.gibbonTTDayRowClassID=:gibbonTTDayRowClassID)
                                        WHERE gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID 
                                        AND gibbonPerson.status='Full' 
                                        AND (dateStart IS NULL OR dateStart<=:today) 
                                        AND (dateEnd IS NULL  OR dateEnd>=:today) 
                                        AND gibbonCourseClassPerson.role='Student' 
                                        AND gibbonTTDayRowClassException.gibbonTTDayRowClassExceptionID IS NULL
                                        ORDER BY gibbonCourseClassPerson.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";
                                        $resultClass = $connection2->prepare($sqlClass);
                                        $resultClass->execute($dataClass);
                                        $count = 0;
                                        $rowNum = 'odd';
                                        if ($resultClass->rowCount() > 0) {
                                            ?>
											<table cellspacing='0' style="width: 100%">
												<tr class='head'>
													<th>
														<?php echo __('Student') ?><br/>
													</th>
													<th>
														<?php echo __('Status') ?><br/>
													</th>
													<th>
														<?php echo __('Version') ?><br/>
													</th>
													<th>
														<?php echo __('Date/Time') ?><br/>
													</th>
													<th>
														<?php echo __('View') ?><br/>
													</th>
                                                    <?php if ($teacher) { ?>
													<th>
														<?php echo __('Action') ?><br/>
													</th>
                                                    <?php } ?>
												</tr>
												<?php
												while ($rowClass = $resultClass->fetch()) {
													if ($count % 2 == 0) {
														$rowNum = 'even';
													} else {
														$rowNum = 'odd';
													}
													++$count;

													echo "<tr class=$rowNum>";
													?>

														<td>
															<?php echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClass['gibbonPersonID']."'>".Format::name('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'</a>' ?><br/>
														</td>

														<?php
                                                        $dataVersion = array('gibbonPlannerEntryID' => $values['gibbonPlannerEntryID'], 'gibbonPersonID' => $rowClass['gibbonPersonID']);
                                                        $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                        $resultVersion = $connection2->prepare($sqlVersion);
                                                        $resultVersion->execute($dataVersion);
													    if ($resultVersion->rowCount() < 1) {
														?>
															<td colspan=4>
																<?php
																//Before deadline
																if (date('Y-m-d H:i:s') < $values['homeworkDueDateTime']) {
																	echo 'Pending';
																}
																//After
																else {
																	if ($rowClass['dateStart'] > $values['date']) {
																		echo "<span title='".__('Student joined school after lesson was taught.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
																	} else {
																		if ($values['homeworkSubmissionRequired'] == 'Required') {
																			echo "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__('Incomplete').'</span>';
																		} else {
																			echo __('Not submitted online');
																		}
																	}
																}
                                                            echo '</td>';

                                                            if ($teacher) {
                                                                echo '<td>';
																echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID.'&gibbonPersonID='.$rowClass['gibbonPersonID']."&submission=false'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                                                echo '</td>';
                                                            }

													} else {
														$rowVersion = $resultVersion->fetch();
														?>
															<td>
																<?php
																if ($rowVersion['status'] == 'On Time' or $rowVersion['status'] == 'Exemption') {
																	echo $rowVersion['status'];
																} else {
																	echo "<span style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".$rowVersion['status'].'</span>';
																}
														?>
															</td>
															<td>
																<?php
																echo $rowVersion['version'];
														if ($rowVersion['version'] == 'Draft') {
															echo ' '.$rowVersion['count'];
														}
														?>
															</td>
															<td>
																<?php echo substr($rowVersion['timestamp'], 11, 5).' '.Format::date(substr($rowVersion['timestamp'], 0, 10)) ?><br/>
															</td>
															<td>
																<?php
																$locationPrint = $rowVersion['location'];
														if (strlen($locationPrint) > 15) {
															$locationPrint = substr($locationPrint, 0, 15).'...';
														}
														if ($rowVersion['type'] == 'File') {
                                                            $rowVersion['location'] = str_replace(['?','#'], ['%3F', '%23'], $rowVersion['location'] ?? '');
															echo "<a href='".$session->get('absoluteURL').'/'.$rowVersion['location']."' target='_blank'>".$locationPrint.'</a>';
														} else {
															echo "<a target='_blank' href='".$rowVersion['location']."'>".$locationPrint.'</a>';
														}

                                                        echo '</td>';

                                                                if ($teacher) {
                                                                    echo '<td>';
																echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full_submit_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=".$gibbonPersonID.'&gibbonPlannerEntryHomeworkID='.$rowVersion['gibbonPlannerEntryHomeworkID']."&submission=true'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
														echo "<a onclick='return confirm(\"".__('Are you sure you wish to delete this record?')."\")' href='".$session->get('absoluteURL')."/modules/Planner/planner_view_full_submit_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$gibbonPersonID&gibbonPlannerEntryHomeworkID=".$rowVersion['gibbonPlannerEntryHomeworkID']."'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>";
														echo '</td>';
                                                    }
															
													}
													echo '</tr>';

												}
                                            	echo '</table>';
                                        }
                                    }
                                }
                            }
                        } elseif ($values['homework'] == 'N') {
                            echo __('No').'<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';

                        if ($values['role'] == 'Student') { //MY HOMEWORK
                            $myHomeworkFail = false;
                            try {
                                if ($roleCategory != 'Student') { //Parent
                                    $dataMyHomework = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                } else { //Student
                                    $dataMyHomework = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                                }
                                $sqlMyHomework = 'SELECT * FROM gibbonPlannerEntryStudentHomework WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                                $resultMyHomework = $connection2->prepare($sqlMyHomework);
                                $resultMyHomework->execute($dataMyHomework);
                            } catch (PDOException $e) {
                                $myHomeworkFail = true;
                            }

                            echo "<tr class='break'>";
                            echo "<td style='padding-top: 5px; width: 33%; vertical-align: top' colspan=3>";
                            echo '<h3>'.__('Student Recorded {homeworkName}', ['homeworkName' => __($homeworkNameSingular)]).'</h3>';
                            if ($roleCategory == 'Student') {
                                echo '<p>'.__('You can use this section to record your own {homeworkName}.', ['homeworkName' => mb_strtolower(__($homeworkNamePlural))]).'</p>';
                            }
                            echo '</td>';
                            echo '</tr>';
                            if ($myHomeworkFail or $resultMyHomework->rowCount() > 1) {
                                $page->addError(__('Your request failed due to a database error.'));
                            } else {
                                if ($resultMyHomework->rowCount() == 1) {
                                    $rowMyHomework = $resultMyHomework->fetch();
                                    $rowMyHomework['homework'] = 'Y';
                                } else {
                                    $rowMyHomework = array();
                                    $rowMyHomework['homework'] = 'N';
                                    $rowMyHomework['homeworkDetails'] = '';
                                }

                                if ($roleCategory != 'Student') { //Parent, so show readonly
									?>
									<tr>
										<td>
											<b><?php echo __('Add {homeworkName}?', ['homeworkName' => __($homeworkNameSingular)]) ?> *</b><br/>
										</td>
										<td>
											<?php
											if ($rowMyHomework['homework'] == 'Y') {
												echo __('Yes');
											} else {
												echo __('No');
											}
										?>
										</td>
									</tr>

									<?php
									if ($rowMyHomework['homework'] == 'Y') {
										?>
										<tr>
											<td>
												<b><?php echo __('{homeworkName} Due Date', ['homeworkName' => __($homeworkNameSingular)]) ?> *</b><br/>
											</td>
											<td>
												<?php if ($rowMyHomework['homework'] == 'Y') { echo Format::date(substr($rowMyHomework['homeworkDueDateTime'], 0, 10)); } ?>
											</td>
										</tr>
										<tr >
											<td>
												<b><?php echo __('{homeworkName} Due Date Time', ['homeworkName' => __($homeworkNameSingular)]) ?></b><br/>
												<span class="italic small"><?php echo __('Format: hh:mm (24hr)') ?><br/></span>
											</td>
											<td >
												<?php if ($rowMyHomework['homework'] == 'Y') { echo substr($rowMyHomework['homeworkDueDateTime'], 11, 5); } ?>
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]) ?></b><br/>
											</td>
											<td class="right">
												<?php echo $rowMyHomework['homeworkDetails'] ?>
											</td>
										</tr>
									<?php

									}
                                } else { //Student so show edit view
                                            $checkedYes = '';
                                    $checkedNo = '';
                                    if ($rowMyHomework['homework'] == 'Y') {
                                        $checkedYes = 'checked';
                                    } else {
                                        $checkedNo = 'checked';
                                    }
                                    ?>

									<script type="text/javascript">
										/* Homework Control */
										htmx.onLoad(function (content) {
											<?php
											if ($checkedNo == 'checked') {
												?>
												$("#homeworkDueDateRow").css("display","none");
												$("#homeworkDueDateTimeRow").css("display","none");
												$("#homeworkDetailsRow").css("display","none");
												<?php

											}
										?>

											//Response to clicking on homework control
											$(".homework").click(function(){
												if ($('input[name=homework]:checked').val()=="Yes" ) {
													homeworkDueDate.enable();
													homeworkDetails.enable();
													$("#homeworkDueDateRow").slideDown("fast", $("#homeworkDueDateRow").css("display","table-row"));
													$("#homeworkDueDateTimeRow").slideDown("fast", $("#homeworkDueDateTimeRow").css("display","table-row"));
													$("#homeworkDetailsRow").slideDown("fast", $("#homeworkDetailsRow").css("display","table-row"));
												} else {
													homeworkDueDate.disable();
													homeworkDetails.disable();
													$("#homeworkDueDateRow").css("display","none");
													$("#homeworkDueDateTimeRow").css("display","none");
													$("#homeworkDetailsRow").css("display","none");
												}
											 });
										});
									</script>

									<?php
									//Try and find the next slot for this class, to use as default HW deadline
									if ($rowMyHomework['homework'] == 'N' and $values['date'] != '' and $values['timeStart'] != '' and $values['timeEnd'] != '') {
										//Get $_GET values
										$homeworkDueDate = '';
										$homeworkDueDateTime = '';


											$dataNext = array('gibbonCourseClassID' => $values['gibbonCourseClassID'], 'date' => $values['date']);
											$sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>:date ORDER BY date, timeStart LIMIT 0, 10';
											$resultNext = $connection2->prepare($sqlNext);
											$resultNext->execute($dataNext);
										if ($resultNext->rowCount() > 0) {
											$rowNext = $resultNext->fetch();
											$homeworkDueDate = $rowNext['date'];
											$homeworkDueDateTime = $rowNext['timeStart'];
										}
									}

                                // SUBMIT HOMEWORK - Student Recorded
                                $form = Form::create('homeworkStudent', $session->get('absoluteURL')."/modules/Planner/planner_view_full_myHomeworkProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&address=".$session->get('address')."&gibbonCourseClassID=$gibbonCourseClassID&date=$date");

                                $form->addHiddenValue('address', $session->get('address'));
                                $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);

                                $row = $form->addRow();
                                    $row->addLabel('homework', __($homeworkNameSingular));
                                    $row->addYesNoRadio('homework')->checked($rowMyHomework['homework'] ?? 'N');

                                $form->toggleVisibilityByClass('showHomework')->onRadio('homework')->when('Y');

                                if (!empty($rowMyHomework['homeworkDueDateTime'])) {
                                    $homeworkDueDate = substr($rowMyHomework['homeworkDueDateTime'], 0, 10);
                                    $homeworkDueDateTime = substr($rowMyHomework['homeworkDueDateTime'], 11, 5);
                                }

                                $row = $form->addRow()->addClass('showHomework');
                                    $row->addLabel('homeworkDueDate', __('{homeworkName} Due Date', ['homeworkName' => __($homeworkNameSingular)]));
                                    $col = $row->addColumn('homeworkDueDate');
                                    $col->addDate('homeworkDueDate')
                                        ->addClass('mr-2')
                                        ->required()
                                        ->setValue(!empty($homeworkDueDate) ? Format::date($homeworkDueDate) : '');
                                    $col->addTime('homeworkDueDateTime')
                                        ->setValue(!empty($homeworkDueDateTime) ? substr($homeworkDueDateTime, 0, 5) : '');

                                $col = $form->addRow()->addClass('showHomework')->addColumn();
                                    $col->addLabel('homeworkDetails', __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]));
                                    $col->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->required()->setValue($rowMyHomework['homeworkDetails'] ?? '');

                                $row = $form->addRow();
                                    $row->addSubmit();

                                echo '<tr><td colspan="3">';
                                echo $form->getOutput();
                                echo '</td></tr>';

                                }
                            }
                        }
                        echo '</table>';

                        if ($highestAction != 'Lesson Planner_viewOnly') {

                          echo "<a name='chat'></a>";
                          echo "<h2 style='padding-top: 30px'>".__('Chat').'</h2>';
                          echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%;'>";
                          echo '<tr>';
                          echo "<td style='text-align: justify; padding-top: 5px; width: 33%; vertical-align: top; max-width: 752px!important;' colspan=3>";

                              echo "<div style='margin: 0px' class='linkTop'>";
                              echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['q'])."/planner_view_full.php$paramsVar#chat'>".__('Refresh')."<img style='margin-left: 5px' title='".__('Refresh')."' src='./themes/".$session->get('gibbonThemeName')."/img/refresh.png'/></a> <a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&search=".$gibbonPersonID."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a> ";
                              echo '</div>';

                              //Get discussion
                              echo getThread($guid, $connection2, $gibbonPlannerEntryID, null, 0, null, $viewBy, $subView, $date, @$class, $gibbonCourseClassID, $gibbonPersonID, $values['role']);

                          echo '</td>';
                          echo '</tr>';
                        }
                        echo '</table>';

                        //Participants & Attendance
                        $gibbonCourseClassID = $values['gibbonCourseClassID'];
                        $columns = 2;

                        $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);

                        $canAccessProfile = ($highestAction == 'View Student Profile_brief' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes' || $highestAction == 'View Student Profile_fullEditAllNotes') ;

                        // Only show certain options if Class Attendance is Enabled school-wide, and for this particular class
                        $attendanceEnabled = $values['attendance'] == 'Y';
                        $canTakeAttendance = $attendanceEnabled && isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php");

                        // Get attendance pre-fill and default settings
                        $defaultAttendanceType = $settingGateway->getSettingByScope('Attendance', 'defaultClassAttendanceType');
                        $crossFillClasses = $settingGateway->getSettingByScope('Attendance', 'crossFillClasses');

                        $attendance = new Gibbon\Module\Attendance\AttendanceView($gibbon, $pdo, $settingGateway);
                        $attendanceGateway = $container->get(AttendanceLogPersonGateway::class);

                        $participants = $container->get(CourseEnrolmentGateway::class)->selectClassParticipantsByDate($gibbonCourseClassID, $values['date'], $values['timeStart'], $values['timeEnd'])->fetchAll();
                        $defaults = ['type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '', 'direction' => '', 'prefill' => 'Y'];

                        // ATTENDANCE FORM
                        $form = Form::createBlank('attendanceByClass', $session->get('absoluteURL') . '/modules/Attendance/attendance_take_byCourseClassProcess.php');
                        $form->setClass('w-full font-sans text-xs text-gray-700');
                        $form->setAutocomplete('off');
                        $form->setTitle($attendanceEnabled ? __('Participants & Attendance') : __('Participants'));

                        // Display the date this attendance was taken, if any
                        if ($canTakeAttendance) {
                            $offTimetableStudents = $container->get(SchoolYearSpecialDayGateway::class)->selectOffTimetableStudentsByClass($session->get('gibbonSchoolYearID'), $gibbonCourseClassID, $values['date'])->fetchKeyPair();

                            // Build attendance data
                            foreach ($participants as $key => $student) {
                                if ($student['role'] != 'Student') continue;

                                $result = $attendanceGateway->selectClassAttendanceLogsByPersonAndDate($gibbonCourseClassID, $student['gibbonPersonID'], $values['date']);

                                $log = ($result->rowCount() > 0) ? $result->fetch() : $defaults;
                                $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                                //Check for school prefill if attendance not taken in this class
                                if ($result->rowCount() == 0) {
                                    $result = $attendanceGateway->selectAttendanceLogsByPersonAndDate($student['gibbonPersonID'], $values['date'], $crossFillClasses);

                                    $log = ($result->rowCount() > 0) ? $result->fetch() : $log;
                                    $log['prefilled'] = $result->rowCount() > 0 ? $log['context'] : '';

                                    if ($log['prefill'] == 'N') {
                                        $log = $defaults;
                                    }
                                }

                                $participants[$key]['cellHighlight'] = '';
                                if ($attendance->isTypeAbsent($log['type'])) {
                                    $participants[$key]['cellHighlight'] = 'bg-red-200';
                                } elseif ($attendance->isTypeOffsite($log['type']) || $log['direction'] == 'Out') {
                                    $participants[$key]['cellHighlight'] = 'bg-blue-200';
                                } elseif ($attendance->isTypeLate($log['type'])) {
                                    $participants[$key]['cellHighlight'] = 'bg-orange-200';
                                } elseif (!empty($offTimetableStudents[$student['gibbonPersonID']])) {
                                    $participants[$key]['cellHighlight'] = 'bg-stripe-overlay';
                                    $participants[$key]['tag'] = Format::tag($offTimetableStudents[$student['gibbonPersonID']], 'dull leading-tight');
                                }

                                $participants[$key]['log'] = $log;
                            }

                            // Try to determine the timetable period for this lesson
                            $form->addHiddenValue('gibbonTTDayRowClassID', $ttPeriod['gibbonTTDayRowClassID'] ?? '');

                            $classLogs = $container->get(AttendanceLogCourseClassGateway::class)->selectClassAttendanceLogsByDate($gibbonCourseClassID, $values['date'])->fetchAll();
                            if (empty($classLogs)) {
                                $form->setDescription(Format::alert(__('Attendance has not been taken. The entries below are a best-guess, not actual data.')));
                            } else {
                                $logText = '<ul class="ml-4">';
                                foreach ($classLogs as $log) {
                                    $linkText = Format::time($log['timestampTaken']).' '.Format::date($log['date']).' '.__('by').' '.Format::name('', $log['preferredName'], $log['surname'], 'Student', true);

                                    $logText .= '<li>'.Format::link('./index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$gibbonCourseClassID.'&currentDate='.Format::date($log['date']), $linkText, ['style' => 'color: inherit']).'</li>';

                                }
                                $logText .= '</ul>';
                                $form->setDescription(Format::alert(__('Attendance has been taken at the following times for this lesson:').$logText, 'success'));
                            }
                        }

                        $grid = $form->addRow()->addGrid('attendance')->setClass('border bg-blue-50 rounded p-2 ')->setBreakpoints('w-1/2');

                        // Display attendance grid
                        $count = 0;

                        $canViewConfidential = ($highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes' || $highestAction == 'View Student Profile_fullEditAllNotes');

                        foreach ($participants as $person) {
                            $form->addHiddenValue($count . '-gibbonPersonID', $person['gibbonPersonID']);
                            $form->addHiddenValue($count . '-prefilled', $person['log']['prefilled'] ?? '');

                            $cell = $grid->addCell()
                                ->setClass('text-center py-4 px-1 flex flex-col justify-start')
                                ->addClass($person['cellHighlight'] ?? '');

                            // Display alerts and birthdays, teacher only
                            if ($person['role'] == 'Student' && $values['role'] == 'Teacher' && $teacher == true) {
                                $alert = getAlertBar($guid, $connection2, $person['gibbonPersonID'], $person['privacy'], "x-cloak x-show='globalShowHide'");
                            }

                            if ($person['role'] == 'Student' && $canViewConfidential) {
                                $icon = Format::userBirthdayIcon($person['dob'], $person['preferredName']);
                            }

                            // Display a photo per user
                            $cell->addContent(Format::userPhoto($person['image_240'], 75, ''))
                                ->setClass('relative')
                                ->prepend($alert ?? '')
                                ->append($icon ?? '');

                            if ($person['role'] == 'Student') {
                                // Add attendance fields, teacher only
                                if ($canTakeAttendance) {
                                    $form->toggleVisibilityByClass($count.'-attendance')->onSelect($count . '-type')->whenNot('Present');
                                    $restricted = $attendance->isTypeRestricted($person['log']['type']);
                                    $cell->addSelect($count . '-type')
                                        ->fromArray($attendance->getAttendanceTypes($restricted))
                                        ->selected($person['log']['type'] ?? '')
                                        ->setClass('mx-auto float-none w-24 text-xs sm:text-xs p-1 m-0 mb-px')
                                        ->readOnly($restricted);
                                    $cell->addSelect($count . '-reason')
                                        ->fromArray($attendance->getAttendanceReasons())
                                        ->selected($person['log']['reason'] ?? '')
                                        ->setClass($count.'-attendance mx-auto float-none w-24 text-xs sm:text-xs p-1 m-0 mb-px');
                                    $cell->addTextField($count . '-comment')
                                        ->maxLength(255)
                                        ->setValue($person['log']['comment'] ?? '')
                                        ->setClass($count.'-attendance mx-auto float-none w-24 text-xs sm:text-xs p-1 m-0');
                                }

                                // Display a student profile link if this user has access
                                if (($values['role'] == 'Teacher' && $teacher == true) || $canAccessProfile) {
                                    $cell->addWebLink(Format::name('', $person['preferredName'], $person['surname'], 'Student', false))
                                        ->setURL('index.php?q=/modules/Students/student_view_details.php')
                                        ->addParam('gibbonPersonID', $person['gibbonPersonID'])
                                        ->setClass('font-bold underline mt-1');
                                } else {
                                    $cell->addContent(Format::name('', $person['preferredName'], $person['surname'], 'Student', false))->wrap('<b>', '</b>');
                                }

                                $count++;
                            } else {
                                $cell->addContent(Format::name('', $person['preferredName'], $person['surname'], 'Staff', false))->wrap('<b>', '</b>');
                            }

                            $cell->addContent(__($person['role']));

                            if (!empty($person['tag'])) {
                                $cell->addContent($person['tag']);
                            }
                        }

                        if ($canTakeAttendance && date('Y-m-d') >= $values['date']) {
                            $form->addHiddenValue('address', $session->get('address'));
                            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                            $form->addHiddenValue('gibbonPlannerEntryID', $gibbonPlannerEntryID);
                            $form->addHiddenValue('currentDate', $values['date']);
                            $form->addHiddenValue('count', $count);
                            $form->addHiddenValues($params);

                            $form->addRow()->addSubmit()->addClass('mt-2');
                        }

                        $page->addSidebarExtra($form->getOutput());


                        // GUESTS
                        $guests = $container->get(PlannerEntryGateway::class)->selectPlannerGuests($gibbonPlannerEntryID)->fetchAll();

                        if (!empty($guests)) {
                            $form = Form::create('plannerGuests', '');
                            $form->setClass('noIntBorder w-full');
                            $form->setTitle(__('Guests'));

                            $grid = $form->addRow()->addGrid('attendance')->setClass('-mx-3 -my-2')->setBreakpoints('w-1/2');

                            foreach ($guests as $guest) {
                                $cell = $grid->addCell()->setClass('text-center py-4 px-1 -mr-px -mb-px flex flex-col justify-start');

                                $cell->addContent(Format::userPhoto($guest['image_240'], 75, ''));
                                $cell->addContent(Format::name($guest['title'], $guest['preferredName'], $guest['surname'], 'Staff', false))->wrap('<b>', '</b>');
                                $cell->addContent($guest['role']);
                            }

                            $page->addSidebarExtra($form->getOutput());
                        }

                    }
                }
            }
        }
    }
}
