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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\CourseGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Courses & Classes'), 'course_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Course & Classes'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'success0') {
            $deleteReturnMessage = __('Your request was completed successfully.');
            $class = 'success';
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonCourseID = $_GET['gibbonCourseID'];
    if ($gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonCourseID, gibbonDepartmentID, gibbonCourse.name AS name, gibbonCourse.nameShort as nameShort, orderBy, gibbonCourse.description, gibbonCourse.map, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/course_manage_editProcess.php?gibbonCourseID='.$gibbonCourseID);
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);

			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->required()->readonly()->setValue($values['yearName']);

			$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonDepartmentID', __('Learning Area'));
				$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql)->placeholder();

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
				$row->addTextField('name')->required()->maxLength(60);

			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'));
				$row->addTextField('nameShort')->required()->maxLength(12);

			$row = $form->addRow();
				$row->addLabel('orderBy', __('Order'))->description(__('May be used to adjust arrangement of courses in reports.'));
				$row->addNumber('orderBy')->maxLength(3);

			$row = $form->addRow();
				$column = $row->addColumn('blurb');
				$column->addLabel('description', __('Blurb'));
				$column->addEditor('description', $guid)->setRows(20);

			$row = $form->addRow();
				$row->addLabel('map', __('Include In Curriculum Map'));
                $row->addYesNo('map')->required();

			$row = $form->addRow();
				$row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Enrolable year groups.'));
				$row->addCheckboxYearGroup('gibbonYearGroupIDList')->loadFromCSV($values);

			$row = $form->addRow();
				$row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Classes');
            echo '</h2>';

            $courseGateway = $container->get(CourseGateway::class);

            $classes = $courseGateway->selectClassesByCourseID($gibbonCourseID);

            // DATA TABLE
            $table = DataTable::create('courseClassManage');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/course_manage_class_add.php')
                ->addParam('gibbonSchoolYearID', $values['gibbonSchoolYearID'])
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->displayLabel();

            $table->addColumn('nameShort', __('Short Name'))->width('20%');
            $table->addColumn('name', __('Name'))->width('20%');
            $table->addColumn('participantsTotal', __('Participants'));
            $table->addColumn('reportable', __('Reportable'))->format(Format::using('yesNo', 'reportable'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $values['gibbonSchoolYearID'])
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->addParam('gibbonCourseClassID')
                ->format(function ($class, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/course_manage_class_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/course_manage_class_delete.php');

                    $actions->addAction('enrolment', __('Enrolment'))
                        ->setIcon('attendance')
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit.php');
                });

            echo $table->render($classes->toDataSet());
        }
    }
}
