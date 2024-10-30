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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $search = $_GET['search'] ?? '';
    $urlParams = compact('gibbonSchoolYearID', 'search');

    $page->breadcrumbs
        ->add(__('Manage Courses & Classes'), 'course_manage.php', $urlParams)
        ->add(__('Edit Course & Classes'), 'course_manage_edit.php', $urlParams + ['gibbonCourseID' => $gibbonCourseID])
        ->add(__('Add Class'));

    if (!empty($search)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Timetable Admin', 'course_manage.php')->withQueryParams($urlParams));
    }

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/course_manage_class_edit.php&gibbonCourseClassID='.$_GET['editID'].'&gibbonCourseID='.$gibbonCourseID.'&gibbonSchoolYearID='.$gibbonSchoolYearID;
    }

    $page->return->setEditLink($editLink);

    if ($gibbonSchoolYearID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = 'SELECT gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
			$values = $result->fetch();

            $settingGateway = $container->get(SettingGateway::class);

			$form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/course_manage_class_addProcess.php');

			$form->addHiddenValue('address', $session->get('address'));
			$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
			$form->addHiddenValue('gibbonCourseID', $gibbonCourseID);

            $row = $form->addRow()->addHeading('Basic Details', __('Basic Details'));

			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->required()->readonly()->setValue($values['yearName']);

			$row = $form->addRow();
				$row->addLabel('courseName', __('Course'));
				$row->addTextField('courseName')->required()->readonly()->setValue($values['courseName']);

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this course.'));
				$row->addTextField('name')->required()->maxLength(30);

			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this course.'));
				$row->addTextField('nameShort')->required()->maxLength(8);

			$row = $form->addRow();
				$row->addLabel('reportable', __('Reportable?'))->description(__('Should this class show in reports?'));
				$row->addYesNo('reportable');

			if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
				$row = $form->addRow();
				$row->addLabel('attendance', __('Track Attendance?'))->description(__('Should this class allow attendance to be taken?'));
				$row->addYesNo('attendance');
			}

            $row = $form->addRow()->addHeading('Advanced Options', __('Advanced Options'));

            $enrolmentMinDefault = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMinDefault');
            $row = $form->addRow();
				$row->addLabel('enrolmentMin', __('Minimum Enrolment'))->description(__('Class should not run below this number of students.'));
				$row->addNumber('enrolmentMin')->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue(is_numeric($enrolmentMinDefault) ? $enrolmentMinDefault : '');

            $enrolmentMaxDefault = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMaxDefault');
            $row = $form->addRow();
				$row->addLabel('enrolmentMax', __('Maximum Enrolment'))->description(__('Enrolment should not exceed this number of students.'));
				$row->addNumber('enrolmentMax')->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue(is_numeric($enrolmentMaxDefault) ? $enrolmentMaxDefault : '');

            // Custom Fields
            $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Class', []);

			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();

			echo $form->getOutput();
        }
    }
}
