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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

use Gibbon\Forms\Form;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Module\Planner\Forms\PlannerFormFactory;

// common variables
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
    ])
    ->add(__('Edit Unit'), 'units_edit.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
        'gibbonUnitID' => $gibbonUnitID,
    ])
    ->add(__('Copy Unit Forward'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_copyForward.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if courseschool year specified
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {

            $courseGateway = $container->get(CourseGateway::class);

            // Check access to specified course
            if ($highestAction == 'Unit Planner_all') {
                $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $gibbon->session->get('gibbonPersonID'));
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $values = $result->fetch();
                $year = $values['schoolYear'];
                $course = $values['course'];
                $class = $values['class'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);

                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __('The specified record cannot be found.');
                        echo '</div>';
                    } else {
                        //Let's go!
                        $values = $result->fetch();

                        echo '<p>';
                        echo sprintf(__('This function allows you to take the selected working unit (%1$s in %2$s) and use its blocks, and the master unit details, to create a new unit. In this way you can use your refined and improved unit as a new master unit whilst leaving your existing master unit untouched.'), $values['name'], "$course.$class");
                        echo '</p>';

                        $form = Form::create('unitsEditCopyForward', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_edit_copyForwardProcess.php?gibbonUnitID=$gibbonUnitID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID");
                        $form->setFactory(PlannerFormFactory::create($pdo));
                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
                        $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
                        $form->addHiddenValue('gibbonUnitID', $gibbonUnitID);
                        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
                        
                        $form->addRow()->addHeading(__('Source'));
                            $row = $form->addRow();
                            $row->addLabel('yearName', __('School Year'));
                            $row->addTextField('yearName')->readonly()->setValue($year)->required();
                            
                            $row = $form->addRow();
                            $row->addLabel('class', __('Class'));
                            $row->addTextField('class')->readonly()->setValue($course.'.'.$class)->required();
                            
                            $row = $form->addRow();
                            $row->addLabel('unit', __('Unit'));
                            $row->addTextField('unit')->readonly()->setValue($values['name'])->required();
                            
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sql = "SELECT gibbonSchoolYearID as value,name FROM gibbonSchoolYear WHERE (status='Upcoming' OR status='Current') ORDER BY sequenceNumber";
                            $form->addRow()->addHeading(__('Target'));
                            $row = $form->addRow();
                                $row->addLabel('gibbonSchoolYearIDCopyTo', __('Year'));
                                $row->addSelect('gibbonSchoolYearIDCopyTo')->fromQuery($pdo, $sql)->placeholder()->isRequired();
                            
                            
                                            try {
                                                if ($highestAction == 'Unit Planner_all') {
                                                    $dataSelect = array();
                                                    $sqlSelect = 'SELECT gibbonCourse.nameShort AS name, gibbonCourseID AS value, gibbonSchoolYear.gibbonSchoolYearID AS chainedTo FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY nameShort';
                                                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                                                    $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                                    $sqlSelect = "SELECT gibbonCourse.nameShort AS name, gibbonCourseID AS value, gibbonSchoolYear.gibbonSchoolYearID AS chainedTo FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonCourse.nameShort";
                                                }
                                                $resultSelect = $connection2->prepare($sqlSelect);
                                                $resultSelect->execute($dataSelect);
                                            } catch (PDOException $e) {
                                            }
                                            
                                $row = $form->addRow();
                                    $row->addLabel('gibbonCourseIDTarget', __('Course'));
                                    $row->addSelect('gibbonCourseIDTarget')->fromQueryChained($pdo, $sqlSelect, $dataSelect, 'gibbonSchoolYearIDCopyTo')->placeholder()->isRequired();
                            
                                $row = $form->addRow();
                                    $row->addLabel('nameTarget', __('New Unit Name'));
                                    $row->addTextField('nameTarget')->required()->setValue($values['name'])->maxLength(40);
                            
  
                            $form->loadAllValuesFrom($values);
                            $row = $form->addRow();
                                $row->addFooter();
                                $row->addSubmit();
                            
                        echo $form->getOutput();
                    }
                }
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
