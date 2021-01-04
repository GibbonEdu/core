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

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_bump.php') == false) {
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
        if ($viewBy == 'class') {
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

        if ($viewBy == 'date') {
            echo "<div class='error'>";
            echo __('You do not have access to this action.');
            echo '</div>';
        } else {
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
                $proceed = true;
                try {
                    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                    } else {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $values = $result->fetch();

                    $page->breadcrumbs
                        ->add(__('Planner for {classDesc}', [
                            'classDesc' => $values['course'].'.'.$values['class'],
                        ]), 'planner.php', $params)
                        ->add(__('Bump Lesson Plan'));

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/planner_bumpProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID");

                    $form->addHiddenValue('viewBy', $viewBy);
                    $form->addHiddenValue('subView', $subView);
                    $form->addHiddenValue('date', $date);
                    $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $row = $form->addRow();
                        $row->addLabel('direction', __('Bump Direction'));
                        $row->addSelect('direction')->fromArray(array('forward' => __('Forward'), 'backward' => __('Backward')))->required();

                    $form->addRow()->addContent(sprintf(__('Pressing "Yes" below will move this lesson, and all preceeding or succeeding lessons in this class, to the previous or next available time slot. <b>Are you sure you want to bump %1$s?'), $values['name']));

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    echo $form->getOutput();
                }
            }
            //Print sidebar
            $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]['gibbonPersonID'], $dateStamp, $gibbonCourseClassID);
        }
    }
}
