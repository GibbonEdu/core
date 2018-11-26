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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceChange_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        $page->breadcrumbs
            ->add(__('Manage Facility Changes'), 'spaceChange_manage.php')
            ->add(__('Add Facility Change'));

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

        //Step 1
        if ($step == 1) {
            echo '<h2>';
            echo __('Step 1 - Choose Class');
            echo '</h2>';

            $form = Form::create('spaceChangeStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/spaceChange_manage_add.php&step=2');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $classes = array();

            // My Classes
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name";
            $results = $pdo->executeQuery($data, $sql);
            if ($results->rowCount() > 0) {
                $classes['--'.__('My Classes').'--'] = $results->fetchAll(\PDO::FETCH_KEY_PAIR);
            }

            // All Classes, if we have access
            if ($highestAction == 'Manage Facility Changes_allClasses') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
                $results = $pdo->executeQuery($data, $sql);
                if ($results->rowCount() > 0) {
                    $classes['--'.__('All Classes').'--'] = $results->fetchAll(\PDO::FETCH_KEY_PAIR);
                }
            }

            // Classed by Department, if we have access
            if ($highestAction == 'Manage Facility Changes_myDepartment') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND role='Coordinator') ORDER BY name";
                $results = $pdo->executeQuery($data, $sql);
                if ($results->rowCount() > 0) {
                    $classes['--'.__('My Department').'--'] = $results->fetchAll(\PDO::FETCH_KEY_PAIR);
                }
            }

            $row = $form->addRow();
                $row->addLabel('gibbonCourseClassID', __('Class'));
                $row->addSelect('gibbonCourseClassID')->fromArray($classes)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addSubmit(__('Proceed'));

            echo $form->getOutput();

        } elseif ($step == 2) {
            echo '<h2>';
            echo __('Step 2 - Choose Options');
            echo '</h2>';
            echo '<p>';
            echo __('When choosing a facility, remember that they are not mutually exclusive: you can change two classes into one facility, change one class to join another class in their normal room, or assign no facility at all. The facilities listed below are not necessarily free at the requested time: please use the View Available Facilities report to check availability.');
            echo '</p>';

            $gibbonCourseClassID = null;
            if (isset($_POST['gibbonCourseClassID'])) {
                $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
            }

            try {
                if ($highestAction == 'Manage Facility Changes_allClasses') {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else if ($highestAction == 'Manage Facility Changes_myDepartment') {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID2' => $gibbonCourseClassID);
                    $sqlSelect = '(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
                    UNION
                    (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND (gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID2 AND role=\'Coordinator\') AND gibbonCourseClassID=:gibbonCourseClassID2)';
                } else {
                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sqlSelect = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                }
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo __('Your request failed due to a database error.');
                echo '</div>';
            }

            if ($resultSelect->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('Your request failed due to a database error.');
                echo '</div>';
            } else {
                $rowSelect = $resultSelect->fetch();

                $form = Form::create('spaceChangeStep2', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/spaceChange_manage_addProcess.php');
                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

                $row = $form->addRow();
                    $row->addLabel('class', __('Class'));
                    $row->addTextField('class')->readonly()->setValue($rowSelect['course'].'.'.$rowSelect['class']);

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date1' => date('Y-m-d'), 'date2' => date('Y-m-d'), 'time' => date('H:i:s'));
                $sql = 'SELECT gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.name AS period, timeStart, timeEnd, gibbonTTDay.name AS day, gibbonTTDayDate.date, gibbonTTSpaceChangeID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) LEFT JOIN gibbonTTSpaceChange ON (gibbonTTSpaceChange.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTSpaceChange.date=gibbonTTDayDate.date) WHERE gibbonTTDayRowClass.gibbonCourseClassID=:gibbonCourseClassID AND (gibbonTTDayDate.date>:date1 OR (gibbonTTDayDate.date=:date2 AND timeEnd>:time)) ORDER BY gibbonTTDayDate.date, timeStart';
                $results = $pdo->executeQuery($data, $sql);
                $classSlots = array_reduce($results->fetchAll(), function($array, $item) use ($guid) {
                    $key = $item['gibbonTTDayRowClassID'].'-'.$item['date'];
                    $array[$key] = dateConvertBack($guid, $item['date']).' ('.$item['day'].' - '.$item['period'].')';
                    return $array;
                }, array());

                $row = $form->addRow();
                    $row->addLabel('gibbonTTDayRowClassID', __('Upcoming Class Slots'));
                    $row->addSelect('gibbonTTDayRowClassID')->fromArray($classSlots)->isRequired()->placeholder();

                $row = $form->addRow();
                    $row->addLabel('gibbonSpaceID', __('Facility'));
                    $row->addSelectSpace('gibbonSpaceID');

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
