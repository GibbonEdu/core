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
use Gibbon\Services\Format;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Module\Planner\Tables\HomeworkTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$style = '';

$highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_deadlines.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Set variables
    $today = date('Y-m-d');

    $plannerGateway = $container->get(PlannerEntryGateway::class);
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
        if (isset($_GET['date'])) {
            $date = $_GET['date'];
        }
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
        ];
    }
    list($todayYear, $todayMonth, $todayDay) = explode('-', $today);
    $todayStamp = mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
    $show = null;
    if (isset($_GET['show'])) {
        $show = $_GET['show'];
    }
    $gibbonCourseClassIDFilter = null;
    if (isset($_GET['gibbonCourseClassIDFilter'])) {
        $gibbonCourseClassIDFilter = $_GET['gibbonCourseClassIDFilter'];
    }
    $gibbonPersonID = null;
    if (isset($_GET['search'])) {
        $gibbonPersonID = $_GET['search'];
    }

    //My children's classes
    if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {

        $page->breadcrumbs
            ->add(__('My Children\'s Classes'), 'planner.php')
            ->add(__('{homeworkName} + Due Dates', ['homeworkName' => __($homeworkNamePlural)]));

        //Test data access field for permission

            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('Access denied.');
            echo '</div>';
        } else {
            //Get child list
            $count = 0;
            $options = array();
            while ($row = $result->fetch()) {

                    $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);

                while ($rowChild = $resultChild->fetch()) {
                    $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student');
                    $gibbonPersonIDArray[$count] = $rowChild['gibbonPersonID'];
                    ++$count;
                }
            }

            if ($count == 0) {
                echo "<div class='error'>";
                echo __('Access denied.');
                echo '</div>';
            } elseif ($count == 1) {
                $gibbonPersonID = $gibbonPersonIDArray[0];
            } else {
                echo '<h3>';
                echo __('Choose');
                echo '</h3>';

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

                $form->setClass('noIntBorder fullWidth');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/planner_deadlines.php');
                if (isset($gibbonCourseClassID) && $gibbonCourseClassID != '') {
                    $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                    $form->addHiddenValue('viewBy', 'class');
                }
                else {
                    $form->addHiddenValue('viewBy', 'date');
                }

                $row = $form->addRow();
                $row->addLabel('search', __('Student'));
                $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSearchSubmit($gibbon->session);

                echo $form->getOutput();
            }

            if ($gibbonPersonID != '' and $count > 0) {
                //Confirm access to this student

                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                if ($resultChild->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $rowChild = $resultChild->fetch();



                    $proceed = true;
                    if ($viewBy == 'class') {
                        if ($gibbonCourseClassID == '') {
                            $proceed = false;
                        } else {

                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                                $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            if ($result->rowCount() != 1) {
                                $proceed = false;
                            }
                        }
                    }

                    if ($proceed == false) {
                        echo Format::alert(__('Your request failed because you do not have access to this action.'));
                    } else {
                        // DEADLINES
                        $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, 'viewableParents')->fetchAll();

                        echo $page->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                            'gibbonPersonID' => $gibbonPersonID,
                            'deadlines' => $deadlines,
                            'heading' => 'h3',
                            'viewBy' => $viewBy,
                        ]);

                        // HOMEWORK TABLE
                        $table = $container->get(HomeworkTable::class)->create($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, 'Parent');
                        $table->setTitle($homeworkNamePlural);

                        echo $table->getOutput();
                    }

                }
            }
        }
    } elseif ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewOnly') {
        //Get current role category
        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        $page->breadcrumbs
            ->add(__('Planner'), 'planner.php', $params)
            ->add(__('{homeworkName} + Due Dates', ['homeworkName' => __($homeworkNamePlural)]));

        //Proceed!
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $proceed = true;
        if ($viewBy == 'class') {
            if ($gibbonCourseClassID == '') {
                $proceed = false;
            } else {
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() != 1) {
                    $proceed = false;
                }
            }
        }

        if ($proceed == false) {
            $page->addError(__('Your request failed because you do not have access to this action.'));
        } else {
            // DEADLINES
            if ($highestAction == 'Lesson Planner_viewEditAllClasses' and $show == 'all') {
                $deadlines = $plannerGateway->selectAllUpcomingHomework($gibbon->session->get('gibbonSchoolYearID'))->fetchAll();
            } else {
                $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
                $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetchAll();
            }

            echo $page->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                'gibbonPersonID' => $gibbonPersonID,
                'deadlines' => $deadlines,
                'heading' => 'h3',
                'viewBy' => $viewBy,
            ]);

            // HOMEWORK TABLE
            $table = $container->get(HomeworkTable::class)->create($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, $category);
            $table->setTitle($homeworkNamePlural);

            echo $table->getOutput();
        }
    }

    //Print sidebar
    $gibbonPersonID = empty($gibbonPersonID) ? $_SESSION[$guid]['gibbonPersonID'] : $gibbonPersonID ;
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp, $gibbonCourseClassID);
}
