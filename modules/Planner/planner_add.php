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
use Gibbon\Module\Planner\Forms\PlannerFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Set variables
        $today = date('Y-m-d');

        $homeworkNameSingular = getSettingByScope($connection2, 'Planner', 'homeworkNameSingular');
        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');

        //Proceed!
        //Get viewBy, date and class variables
        $params = [];
        $viewBy = null;
        if (isset($_GET['viewBy'])) {
            $viewBy = $_GET['viewBy'];
        }
        $subView = null;
        if (isset($_GET['subView'])) {
            $subView = $_GET['subView'];
        }
        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }
        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;
        if ($viewBy == 'date') {
            $date = $_GET['date'] ?? '';
            if (isset($_GET['dateHuman']) == true) {
                $date = dateConvert($guid, $_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
            $params += [
                'viewBy' => 'date',
                'date' => $date,
            ];
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
            }
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
            $params += [
                'viewBy' => 'class',
                'date' => $class,
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'subView' => $subView,
            ];
        }

        list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
        $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);

        $proceed = true;
        $extra = '';
        if ($viewBy == 'class') {
            if ($gibbonCourseClassID == '') {
                $proceed = false;
            } else {
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    $proceed = false;
                } else {
                    $values = $result->fetch();
                    $extra = $values['course'].'.'.$values['class'];
                    $gibbonDepartmentID = $values['gibbonDepartmentID'];
                    $gibbonYearGroupIDList = $values['gibbonYearGroupIDList'];
                }
            }
        } else {
            $extra = dateConvertBack($guid, $date);
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __('Your request failed because you do not have access to this action.');
            echo '</div>';
        } else {
            $page->breadcrumbs
                ->add(
                    empty($extra) ?
                        __('Planner') :
                        __('Planner for {classDesc}', ['classDesc' => $extra]),
                    'planner.php',
                    $params
                )
                ->add(__('Add Lesson Plan'));

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?' . http_build_query($params + [
                    'q' => '/modules/Planner/planner_edit.php',
                    'gibbonPlannerEntryID' => $_GET['editID'] ?? '',
                ]);
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_addProcess.php?viewBy=$viewBy&subView=$subView&address=".$_SESSION[$guid]['address']);
            $form->setFactory(PlannerFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            //BASIC INFORMATION
            $form->addRow()->addHeading(__('Basic Information'));

            if ($viewBy == 'class') {
                $form->addHiddenValue('gibbonCourseClassID', $values['gibbonCourseClassID']);
                $row = $form->addRow();
                    $row->addLabel('schoolYearName', __('Class'));
                    $row->addTextField('schoolYearName')->setValue($values['course'].'.'.$values['class'])->required()->readonly();
            } else {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                } else {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".", gibbonCourseClass.nameShort) AS name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name';
                }
                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassID', __('Class'));
                    $row->addSelect('gibbonCourseClassID')->fromQuery($pdo, $sql, $data)->required()->placeholder();
            }

            if ($viewBy == 'class') {
                $data = array('gibbonCourseClassID' => $values['gibbonCourseClassID']);
                $sql = "SELECT gibbonCourseClassID AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND active='Y' AND running='Y' ORDER BY ordering, name";
                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $row->addSelect('gibbonUnitID')->fromQuery($pdo, $sql, $data)->placeholder();
            }
            else {
                $sql = "SELECT GROUP_CONCAT(gibbonCourseClassID SEPARATOR ' ') AS chainedTo, gibbonUnit.gibbonUnitID as value, name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE active='Y' AND running='Y'  GROUP BY gibbonUnit.gibbonUnitID ORDER BY ordering, name";
                $row = $form->addRow();
                    $row->addLabel('gibbonUnitID', __('Unit'));
                    $row->addSelect('gibbonUnitID')->fromQueryChained($pdo, $sql, [], 'gibbonCourseClassID')->placeholder();
            }

            $row = $form->addRow();
                $row->addLabel('name', __('Lesson Name'));
                $row->addTextField('name')->setValue()->maxLength(50)->required();

            $row = $form->addRow();
                $row->addLabel('summary', __('Summary'));
                $row->addTextField('summary')->setValue()->maxLength(255);

            //Try and find the next unplanned slot for this class.
            if ($viewBy == 'class') {
                //Get $_GET values
                $nextDate = null;
                if (isset($_GET['date'])) {
                    $nextDate = $_GET['date'];
                }
                $nextTimeStart = null;
                if (isset($_GET['timeStart'])) {
                    $nextTimeStart = $_GET['timeStart'];
                }
                $nextTimeEnd = null;
                if (isset($_GET['timeEnd'])) {
                    $nextTimeEnd = $_GET['timeEnd'];
                }

                if ($nextDate == '') {
                    
                        $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => date('Y-m-d'));
                        $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10';
                        $resultNext = $connection2->prepare($sqlNext);
                        $resultNext->execute($dataNext);
                    $nextDate = '';
                    $nextTimeStart = '';
                    $nextTimeEnd = '';
                    while ($rowNext = $resultNext->fetch()) {
                        
                            $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                            $resultPlanner = $connection2->prepare($sqlPlanner);
                            $resultPlanner->execute($dataPlanner);
                        if ($resultPlanner->rowCount() == 0) {
                            $nextDate = $rowNext['date'];
                            $nextTimeStart = $rowNext['timeStart'];
                            $nextTimeEnd = $rowNext['timeEnd'];
                            break;
                        }
                    }
                }
            }

            if ($viewBy == 'date') {
                $row = $form->addRow();
                    $row->addLabel('date', __('Date'));
                    $row->addDate('date')->setValue(dateConvertBack($guid, $date))->required()->readonly();
            }
            else {
                $row = $form->addRow();
                    $row->addLabel('date', __('Date'));
                    $row->addDate('date')->setValue(dateConvertBack($guid, $nextDate))->required();
            }

            $nextTimeStart = (isset($nextTimeStart)) ? substr($nextTimeStart, 0, 5) : null;
            $row = $form->addRow();
                $row->addLabel('timeStart', __('Start Time'))->description(__("Format: hh:mm (24hr)"));
                $row->addTime('timeStart')->setValue($nextTimeStart)->required();

            $nextTimeEnd = (isset($nextTimeEnd)) ? substr($nextTimeEnd, 0, 5) : null;
            $row = $form->addRow();
                $row->addLabel('timeEnd', __('End Time'))->description(__("Format: hh:mm (24hr)"));
                $row->addTime('timeEnd')->setValue($nextTimeEnd)->required();

            $form->addRow()->addHeading(__('Lesson Content'));

            $description = getSettingByScope($connection2, 'Planner', 'lessonDetailsTemplate') ;
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('description', __('Lesson Details'));
                $column->addEditor('description', $guid)->setRows(25)->showMedia()->setValue($description);

            $teachersNotes = getSettingByScope($connection2, 'Planner', 'teachersNotesTemplate');
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('teachersNotes', __('Teacher\'s Notes'));
                $column->addEditor('teachersNotes', $guid)->setRows(25)->showMedia()->setValue($teachersNotes);

            //HOMEWORK
            $form->addRow()->addHeading(__($homeworkNameSingular));

            $form->toggleVisibilityByClass('homework')->onRadio('homework')->when('Y');
            $row = $form->addRow();
                $row->addLabel('homework', __('Add {homeworkName}?', ['homeworkName' => __($homeworkNameSingular)]));
                $row->addRadio('homework')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkDueDate', __('Due Date'))->description(__('Date is required, time is optional.'));
                $col = $row->addColumn('homeworkDueDate')->addClass('homework');
                $col->addDate('homeworkDueDate')->addClass('mr-2')->required();
                $col->addTime('homeworkDueDateTime');
                
            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkTimeCap', __('Time Cap?'))->description(__('The maximum time, in minutes, for students to work on this.'));
                $row->addNumber('homeworkTimeCap');

            $row = $form->addRow()->addClass('homework');
                $column = $row->addColumn();
                $column->addLabel('homeworkDetails', __('{homeworkName} Details', ['homeworkName' => __($homeworkNameSingular)]));
                $column->addEditor('homeworkDetails', $guid)->setRows(15)->showMedia()->setValue($description)->required();

            $form->toggleVisibilityByClass('homeworkSubmission')->onRadio('homeworkSubmission')->when('Y');
            $row = $form->addRow()->addClass('homework');
                $row->addLabel('homeworkSubmission', __('Online Submission?'));
                $row->addRadio('homeworkSubmission')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            $row = $form->addRow()->setClass('homeworkSubmission');
                $row->addLabel('homeworkSubmissionDateOpen', __('Submission Open Date'));
                $row->addDate('homeworkSubmissionDateOpen')->required();

            $row = $form->addRow()->setClass('homeworkSubmission');
                $row->addLabel('homeworkSubmissionDrafts', __('Drafts'));
                $row->addSelect('homeworkSubmissionDrafts')->fromArray(array('0' => __('None'), '1' => __('1'), '2' => __('2'), '3' => __('3')))->required();

            $row = $form->addRow()->setClass('homeworkSubmission');
                $row->addLabel('homeworkSubmissionType', __('Submission Type'));
                $row->addSelect('homeworkSubmissionType')->fromArray(array('Link' => __('Link'), 'File' => __('File'), 'Link/File' => __('Link/File')))->required();

            $row = $form->addRow()->setClass('homeworkSubmission');
                $row->addLabel('homeworkSubmissionRequired', __('Submission Required'));
                $row->addSelect('homeworkSubmissionRequired')->fromArray(array('Optional' => __('Optional'), 'Required' => __('Required')))->required();

            if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php')) {
                $form->toggleVisibilityByClass('homeworkCrowdAssess')->onRadio('homeworkCrowdAssess')->when('Y');
                $row = $form->addRow()->addClass('homeworkSubmission');
                    $row->addLabel('homeworkCrowdAssess', __('Crowd Assessment?'));
                    $row->addRadio('homeworkCrowdAssess')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

                $row = $form->addRow()->addClass('homeworkCrowdAssess');
                    $row->addLabel('homeworkCrowdAssessControl', __('Access Controls?'))->description(__('Decide who can see this {homeworkName}.', ['homeworkName' => __($homeworkNameSingular)]));
                    $column = $row->addColumn()->setClass('flex-col items-end');
                        $column->addCheckbox('homeworkCrowdAssessClassTeacher')->checked(true)->description(__('Class Teacher'))->disabled();
                        $column->addCheckbox('homeworkCrowdAssessClassSubmitter')->checked(true)->description(__('Submitter'))->disabled();
                        $column->addCheckbox('homeworkCrowdAssessClassmatesRead')->description(__('Classmates'));
                        $column->addCheckbox('homeworkCrowdAssessOtherStudentsRead')->description(__('Other Students'));
                        $column->addCheckbox('homeworkCrowdAssessOtherTeachersRead')->description(__('Other Teachers'));
                        $column->addCheckbox('homeworkCrowdAssessSubmitterParentsRead')->description(__('Submitter\'s Parents'));
                        $column->addCheckbox('homeworkCrowdAssessClassmatesParentsRead')->description(__('Classmates\'s Parents'));
                        $column->addCheckbox('homeworkCrowdAssessOtherParentsRead')->description(__('Other Parents'));
            }

            //MARKBOOK
            $form->addRow()->addHeading(__('Markbook'));

            $form->toggleVisibilityByClass('homework')->onRadio('homework')->when('Y');
            $row = $form->addRow();
                $row->addLabel('markbook', __('Create Markbook Column?'))->description(__('Linked to this lesson by default.'));
                $row->addRadio('markbook')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->required()->checked('N')->inline(true);

            //ADVANCED
            $form->addRow()->addHeading(__('Advanced Options'));

            $form->toggleVisibilityByClass('advanced')->onCheckbox('advanced')->when('Y');
            $row = $form->addRow();
                $row->addCheckbox('advanced')->setValue('Y')->description(__('Show Advanced Options'));

            // OUTCOMES
            if ($viewBy == 'date') {
                $form->addRow()->addHeading(__('Outcomes'))->addClass('advanced');
                $form->addRow()->addAlert(__('Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.'), 'warning')->addClass('advanced');
            } else {
                $form->addRow()->addHeading(__('Outcomes'))->addClass('advanced');
                $form->addRow()->addContent(__('Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.'))->addClass('advanced');

                $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');

                $row = $form->addRow()->addClass('advanced');
                    $row->addPlannerOutcomeBlocks('outcome', $gibbon->session, $gibbonYearGroupIDList, $gibbonDepartmentID, $allowOutcomeEditing);
            }
            
            //Access
            $form->addRow()->addHeading(__('Access'))->addClass('advanced');

            $sharingDefaultStudents = getSettingByScope($connection2, 'Planner', 'sharingDefaultStudents');
            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('viewableStudents', __('Viewable to Students'));
                $row->addYesNo('viewableStudents')->required()->selected($sharingDefaultStudents);

            $sharingDefaultParents = getSettingByScope($connection2, 'Planner', 'sharingDefaultParents');
            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('viewableParents', __('Viewable to Parents'));
                $row->addYesNo('viewableParents')->required()->selected($sharingDefaultParents);

            //Guests
            $form->addRow()->addHeading(__('Guests'))->addClass('advanced');

            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('guests', __('Guest List'));
                $row->addSelectUsers('guests')->selectMultiple();

            $roles = array(
                'Guest Student' => __('Guest Student'),
                'Guest Teacher' => __('Guest Teacher'),
                'Guest Assistant' => __('Guest Assistant'),
                'Guest Technician' => __('Guest Technician'),
                'Guest Parent' => __('Guest Parent'),
                'Other Guest' => __('Other Guest'),
            );
            $row = $form->addRow()->addClass('advanced');
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($roles);

            $row = $form->addRow();
                $row->addFooter();
                $row->addCheckbox('notify')->description(__('Notify all class participants'));
                $row->addSubmit();

            echo $form->getOutput();
        }

        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
    }
}
