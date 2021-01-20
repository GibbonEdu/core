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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_duplicate.php') == false) {
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
            $date = $_GET['date'];
            if (isset($_GET['dateHuman'])) {
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

        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
        if ($gibbonPlannerEntryID == '' or ($viewBy == 'class' and $gibbonCourseClassID == 'Y')) {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($viewBy == 'date') {
                    $data = array('date' => $date, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, gibbonPlannerEntry.homework, gibbonPlannerEntry.homeworkSubmission FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, gibbonPlannerEntry.homework, gibbonPlannerEntry.homeworkSubmission FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                $otherYearDuplicateSuccess = false;
                //Deal with duplicate to other year
                $returns = array();
                $returns['success0'] = __('Your request was completed successfully, but the target class is in another year, so you cannot see the results here.');
                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, $returns);
                }
                if ($otherYearDuplicateSuccess != true) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                }
            } else {
                //Let's go!
				$values = $result->fetch();

				// target of the planner
				$target = ($viewBy === 'class') ? $values['course'].'.'.$values['class'] : dateConvertBack($guid, $date);

				$page->breadcrumbs
					->add(__('Planner for {classDesc}', [
						'classDesc' => $target,
					]), 'planner.php', $params)
					->add(__('Duplicate Lesson Plan'));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                $step = null;
                if (isset($_GET['step'])) {
                    $step = $_GET['step'];
                }
                if ($step != 1 and $step != 2) {
                    $step = 1;
                }

                if ($step == 1) {
                    echo "<p>".__('This process will duplicate all aspects of the selected lesson. If a lesson is copied into another course, Smart Block content will be added into the lesson body, so it does not get left out.')."</p>";

                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/planner_duplicate.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&step=2");

                    $form->addHiddenValue('viewBy', $viewBy);
                    $form->addHiddenValue('gibbonPlannerEntryID_org',  $gibbonPlannerEntryID);
                    $form->addHiddenValue('subView', $subView);
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = 'SELECT gibbonSchoolYearID AS value, name FROM gibbonSchoolYear WHERE sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY sequenceNumber';
                    $row = $form->addRow();
                        $row->addLabel('gibbonSchoolYearID', __('Target Year'));
                        $row->addSelect('gibbonSchoolYearID')->fromQuery($pdo, $sql, $data)->required()->placeholder()->selected($_SESSION[$guid]['gibbonSchoolYearID']);


                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID AS chainedTo, gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".",gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY gibbonSchoolYear.gibbonSchoolYearID, name';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID AS chainedTo, gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort,".",gibbonCourseClass.nameShort) AS name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.sequenceNumber>=(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) AND gibbonPersonID=:gibbonPersonID ORDER BY name';
                    }
                    $row = $form->addRow();
                        $row->addLabel('gibbonCourseClassID', __('Target Class'));
                        $row->addSelect('gibbonCourseClassID')->fromQueryChained($pdo, $sql, $data, 'gibbonSchoolYearID')->required()->placeholder();

                    //DUPLICATE MARKBOOK COLUMN?
                    
                        $dataMarkbook = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlMarkbook = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                        $resultMarkbook = $connection2->prepare($sqlMarkbook);
                        $resultMarkbook->execute($dataMarkbook);
                    if ($resultMarkbook->rowCount() >= 1) {
                        $row = $form->addRow();
                            $row->addLabel('duplicate', __('Duplicate Markbook Columns?'))->description(__('Will duplicate any columns linked to this lesson.'));
                            $row->addYesNo('duplicate')->selected('N');
                    }

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit(__('Next'));

                    echo $form->getOutput();

                } elseif ($step == 2) {
                    $gibbonPlannerEntryID_org = $_POST['gibbonPlannerEntryID_org'];
                    $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
                    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
                    $duplicate = null;
                    if (isset($_POST['duplicate'])) {
                        $duplicate = $_POST['duplicate'];
                    }
                    if ($gibbonCourseClassID == '' or $gibbonSchoolYearID == '') {
                        echo "<div class='error'>";
                        echo __('You have not specified one or more required parameters.');
                        echo '</div>';
                    } else {
                        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_duplicateProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID");

                        $form->addHiddenValue('duplicate', $duplicate);
                        $form->addHiddenValue('gibbonPlannerEntryID_org', $gibbonPlannerEntryID_org);
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
                        $form->addHiddenValue('viewBy', $viewBy);
                        $form->addHiddenValue('subView', $subView);
                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                        $class='';
                        try {
                            if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                                $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                            } else {
                                $dataSelect = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                            }
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo $e->getMEssage();
                        }
                        if ($resultSelect->rowCount() == 1) {
                            $rowSelect = $resultSelect->fetch();
                            $class = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']);
                        }
                        $row = $form->addRow();
                            $row->addLabel('class', __('Class'));
                            $row->addTextField('class')->setValue($class)->readonly()->required();

                        if ($values['gibbonUnitID'] != '' && $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
                            //KEEP IN UNIT
                            
                                $dataMarkbook = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonUnitID' => $values['gibbonUnitID']);
                                $sqlMarkbook = 'SELECT * FROM gibbonUnitClass WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID';
                                $resultMarkbook = $connection2->prepare($sqlMarkbook);
                                $resultMarkbook->execute($dataMarkbook);

                            if ($resultMarkbook->rowCount() == 1) {
                                $rowMarkbook = $resultMarkbook->fetch();
                                $form->addHiddenValue('gibbonUnitClassID', $rowMarkbook['gibbonUnitClassID']);

                                $row = $form->addRow();
                                    $row->addLabel('keepUnit', __('Keep lesson in original unit?'))->description(__('Only available if source and target classes are in the same course.'));
                                    $row->addYesNo('keepUnit')->selected('Y')->required();

                            }
                        }

                        $row = $form->addRow();
                            $row->addLabel('name', __('Name'));
                            $row->addTextField('name')->setValue($values['name'])->maxLength(50)->required();

                        //Try and find the next unplanned slot for this class.
                        
                            $dataNext = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => date('Y-m-d'));
                            $sqlNext = 'SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10';
                            $resultNext = $connection2->prepare($sqlNext);
                            $resultNext->execute($dataNext);
                        $next = array('date' => null, 'start' => null, 'end' => null, 'date2' => null, 'start2' => null);
                        $nextSet = false;
                        while ($rowNext = $resultNext->fetch()) {
                            if ($nextSet == false) {
                                
                                    $dataPlanner = array('date' => $rowNext['date'], 'timeStart' => $rowNext['timeStart'], 'timeEnd' => $rowNext['timeEnd'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlPlanner = 'SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID';
                                    $resultPlanner = $connection2->prepare($sqlPlanner);
                                    $resultPlanner->execute($dataPlanner);
                                if ($resultPlanner->rowCount() == 0) {
                                    $nextSet = true;
                                    $next['date'] = $rowNext['date'];
                                    $next['start'] = $rowNext['timeStart'];
                                    $next['end'] = $rowNext['timeEnd'];
                                }
                            }
                            else {
                                $next['date2'] = $rowNext['date'];
                                $next['start2'] = $rowNext['timeStart'];
                                break;
                            }
                        }
                        $row = $form->addRow();
                            $row->addLabel('date', __('Date'));
                            $row->addDate('date')->setValue(dateConvertBack($guid, $next['date']))->required();

                        $row = $form->addRow();
                            $row->addLabel('timeStart', __('Start Time'))->description("Format: hh:mm (24hr)");
                            $row->addTime('timeStart')->setValue(substr($next['start'], 0, 5))->required();

                        $row = $form->addRow();
                            $row->addLabel('timeEnd', __('End Time'))->description("Format: hh:mm (24hr)");
                            $row->addTime('timeEnd')->setValue(substr($next['end'], 0, 5))->required();

                        if ($values['homework'] == 'Y') {
                            $form->addRow()->addHeading(__($homeworkNamePlural));

                            $row = $form->addRow();
                                $row->addLabel('homeworkDueDate', __('{homeworkName} Due Date', ['homeworkName' => __($homeworkNameSingular)]));
                                $row->addDate('homeworkDueDate')->setValue(dateConvertBack($guid, $next['date2']))->required();

                            $row = $form->addRow();
                                $row->addLabel('homeworkDueDateTime', __('{homeworkName} Due Date Time', ['homeworkName' => __($homeworkNameSingular)]))->description("Format: hh:mm (24hr)");
                                $row->addTime('homeworkDueDateTime')->setValue(substr($next['start2'], 0, 5))->required();

                            if ($values['homeworkSubmission'] == 'Y') {
                                $row = $form->addRow();
                                    $row->addLabel('homeworkSubmissionDateOpen', __('Submission Open Date'));
                                    $row->addDate('homeworkSubmissionDateOpen')->setValue(dateConvertBack($guid, $next['date']))->required();
                            }
                        }

                        $row = $form->addRow();
                            $row->addFooter();
                            $row->addSubmit();

                        echo $form->getOutput();
                    }
                }
            }
        }
        //Print sidebar
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
    }
}
