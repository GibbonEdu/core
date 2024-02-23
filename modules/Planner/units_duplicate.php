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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Timetable\CourseGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// common variables
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
    ])
    ->add(__('Duplicate Unit'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if courseschool year specified
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            $courseGateway = $container->get(CourseGateway::class);

            // Check access to specified course
            if ($highestAction == 'Unit Planner_all') {
                $result = $courseGateway->selectCourseDetailsByCourse($gibbonCourseID);
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $result = $courseGateway->selectCourseDetailsByCourseAndPerson($gibbonCourseID, $session->get('gibbonPersonID'));
            }

            if ($result->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $values = $result->fetch();
                $courseName = $values['name'];
                $yearName = $values['schoolYear'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    $page->addError(__('You have not specified one or more required parameters.'));
                } else {
                    if ($gibbonUnitID == '') {
                        $page->addError(__('You have not specified one or more required parameters.'));
                    } else {

                            $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                            $sql = "SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);

                        if ($result->rowCount() != 1) {
                            $page->addError(__('The specified record cannot be found.'));
                        } else {
                            //Let's go!
                            $values = $result->fetch();

                            $step = null;
                            if (isset($_GET['step'])) {
                                $step = $_GET['step'] ?? '';
                            }
                            if ($step != 1 and $step != 2 and $step != 3) {
                                $step = 1;
                            }

                            //Step 1
                            if ($step == 1) {
                                echo '<h2>';
                                echo __('Step 1');
                                echo '</h2>';

                                $form = Form::create('action', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/units_duplicate.php&step=2&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID");
                                $form->setFactory(DatabaseFormFactory::create($pdo));

                                $form->addHiddenValue('address', $session->get('address'));

                                $form->addRow()->addHeading('Source', __('Source'));

                                $row = $form->addRow();
                                    $row->addLabel('yearName', __('School Year'));
                                    $row->addTextField('yearName')->readonly()->setValue($yearName);

                                $row = $form->addRow();
                                    $row->addLabel('courseName', __('Course'));
                                    $row->addTextField('courseName')->readonly()->setValue($values['courseName']);

                                $row = $form->addRow();
                                    $row->addLabel('unitName', __('Unit'));
                                    $row->addTextField('unitName')->readonly()->setValue($values['name']);

                                $form->addRow()->addHeading('Target', __('Target'));

                                $row = $form->addRow();
                                    $row->addLabel('gibbonSchoolYearIDCopyTo', __('School Year'));
                                    $row->addSelectSchoolYear('gibbonSchoolYearIDCopyTo', 'Active')->required();

                                if ($highestAction == 'Unit Planner_all') {
                                    $data = array();
                                    $sql = 'SELECT gibbonCourse.gibbonSchoolYearID as chainedTo, gibbonCourseID AS value, gibbonCourse.nameShort AS name FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY nameShort';
                                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                                    $sql = "SELECT gibbonCourse.gibbonSchoolYearID as chainedTo, gibbonCourseID AS value, gibbonCourse.nameShort AS name FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonCourse.nameShort";
                                }
                                $row = $form->addRow();
                                    $row->addLabel('gibbonCourseIDTarget', __('Course'));
                                    $row->addSelect('gibbonCourseIDTarget')->fromQueryChained($pdo, $sql, $data, 'gibbonSchoolYearIDCopyTo')->required()->placeholder();

                                $row = $form->addRow();
                                    $row->addLabel('unitName', __('Unit'));
                                    $row->addTextField('unitName')->readonly()->setValue($values['name']);

                                $row = $form->addRow();
                                    $row->addFooter();
                                    $row->addSubmit();

                                echo $form->getOutput();

                            } elseif ($step == 2) {
                                echo '<h2>';
                                echo __('Step 2');
                                echo '</h2>';

                                $gibbonCourseIDTarget = $_POST['gibbonCourseIDTarget'] ?? '';

                                if ($gibbonCourseIDTarget == '') {
                                    $page->addError(__('You have not specified one or more required parameters.'));
                                } else {


                                        $dataSelect2 = array('gibbonCourseID' => $gibbonCourseIDTarget);
                                        $sqlSelect2 = 'SELECT gibbonCourse.name AS course, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID';
                                        $resultSelect2 = $connection2->prepare($sqlSelect2);
                                        $resultSelect2->execute($dataSelect2);
                                    if ($resultSelect2->rowCount() == 1) {
                                        $rowSelect2 = $resultSelect2->fetch();
                                        $access = true;
                                        $course = $rowSelect2['course'];
                                        $year = $rowSelect2['year'];
                                    }

                                    $form = Form::create('action', $session->get('absoluteURL') . "/modules/" . $session->get('module') ."/units_duplicateProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=".$_GET['q']);
                                    $form->setFactory(DatabaseFormFactory::create($pdo));

                                    $form->addHiddenValue('address', $session->get('address'));
                                    $form->addHiddenValue('gibbonCourseIDTarget', $gibbonCourseIDTarget);

                                    $row = $form->addRow();
                                        $row->addLabel('copyLessons', __('Copy Lessons?'));
                                        $row->addYesNoRadio('copyLessons')->required()->setClass('copyLessons right');

                                    $form->toggleVisibilityByClass('targetClass')->onRadio('copyLessons')->when('Y');

                                    $form->addRow()->addHeading('Source', __('Source'));

                                    $row = $form->addRow();
                                        $row->addLabel('yearName', __('School Year'));
                                        $row->addTextField('yearName')->readonly()->setValue($yearName);

                                    $row = $form->addRow();
                                        $row->addLabel('courseName', __('Course'));
                                        $row->addTextField('courseName')->readonly()->setValue($values['courseName']);

                                    $row = $form->addRow();
                                        $row->addLabel('unitName', __('Unit'));
                                        $row->addTextField('unitName')->readonly()->setValue($values['name']);

                                    $dataSelectClassSource= array('gibbonCourseID' => $gibbonCourseID);
                                    $sqlSelectClassSource = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID ORDER BY name";

                                    $row = $form->addRow()->addClass('targetClass');
                                        $row->addLabel('gibbonCourseClassIDSource', __('Source Class'));
                                        $row->addSelect('gibbonCourseClassIDSource')->fromQuery($pdo, $sqlSelectClassSource, $dataSelectClassSource)->required()->placeholder();

                                    $form->addRow()->addHeading('Target', __('Target'));

                                    $row = $form->addRow();
                                        $row->addLabel('year', __('School Year'));
                                        $row->addTextField('year')->readonly()->setValue($year);

                                    $row = $form->addRow();
                                        $row->addLabel('course', __('Course'));
                                        $row->addTextField('course')->readonly()->setValue($course);

                                    $row = $form->addRow();
                                        $row->addLabel('unitName', __('Unit'));
                                        $row->addTextField('unitName')->readonly()->setValue($values['name']);

                                    $dataSelectClassTarget= array('gibbonCourseID' => $gibbonCourseIDTarget);
                                    $sqlSelectClassTarget = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID ORDER BY name";

                                    $row = $form->addRow()->addClass('targetClass');
                                        $row->addLabel('gibbonCourseClassIDTarget[]', __('Classes'));
                                        $row->addSelect('gibbonCourseClassIDTarget[]')->fromQuery($pdo, $sqlSelectClassTarget, $dataSelectClassTarget)->required()->selectMultiple();

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
    //Print sidebar
    $session->set('sidebarExtra', sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
?>
