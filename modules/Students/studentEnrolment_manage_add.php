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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Timetable\CourseSyncGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Student Enrolment'), 'studentEnrolment_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Add Student Enrolment'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/studentEnrolment_manage_edit.php&gibbonStudentEnrolmentID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    $page->return->setEditLink($editLink);

    //Check if school year specified
    if ($gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__('Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('studentEnrolmentAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID, ['name']);
        $schoolYearName = $schoolYear['name'] ?? $_SESSION[$guid]['gibbonSchoolYearName'];

        $row = $form->addRow();
            $row->addLabel('yearName', __('School Year'));
            $row->addTextField('yearName')->readOnly()->maxLength(20)->setValue($schoolYearName);

        $students = $container->get(StudentGateway::class)->selectUnenrolledStudentsBySchoolYear($gibbonSchoolYearID);
        $row = $form->addRow();
           $row->addLabel('gibbonPersonID', __('Student'))->description(__('Only includes students not enrolled in specified year.'));
           $row->addSelect('gibbonPersonID')->fromResults($students)->required()->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->required();

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID', __('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->required();

        $row = $form->addRow();
            $row->addLabel('rollOrder', __('Roll Order'));
            $row->addNumber('rollOrder')->maxLength(2);

        // Check to see if any class mappings exists -- otherwise this feature is inactive, hide it
        $classMapCount = $container->get(CourseSyncGateway::class)->countAll();
        if ($classMapCount > 0) {
            $autoEnrolDefault = getSettingByScope($connection2, 'Timetable Admin', 'autoEnrolCourses');
            $row = $form->addRow();
                $row->addLabel('autoEnrolStudent', __('Auto-Enrol Courses?'))
                    ->description(__('Should this student be automatically enrolled in courses for their Roll Group?'));
                $row->addYesNo('autoEnrolStudent')->selected($autoEnrolDefault);
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
