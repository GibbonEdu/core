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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $type = $_GET['type'] ?? '';
    $allUsers = $_GET['allUsers'] ?? '';
    $search = $_GET['search'] ?? '';

    if ($gibbonPersonID == '' or $gibbonCourseClassID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT role, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonCourseClassPerson.reportable FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $urlParams = ['gibbonCourseClassID' => $gibbonCourseClassID, 'type' => $type, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'allUsers' => $allUsers];

            $page->breadcrumbs
                ->add(__('Course Enrolment by Person'), 'courseEnrolment_manage_byPerson.php', $urlParams)
                ->add(Format::name('', $values['preferredName'], $values['surname'], 'Student'), 'courseEnrolment_manage_byPerson_edit.php', $urlParams)
                ->add(__('Edit Participant'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID&type=$type'>".__('Back').'</a>';
            }
			echo '</div>'; 
			
			$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_edit_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
                
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

			$row = $form->addRow();
				$row->addLabel('yearName', __('School Year'));
				$row->addTextField('yearName')->readonly()->setValue($values['yearName']);
			
			$row = $form->addRow();
				$row->addLabel('courseName', __('Course'));
				$row->addTextField('courseName')->readonly()->setValue($values['courseName']);

			$row = $form->addRow();
				$row->addLabel('name', __('Class'));
				$row->addTextField('name')->readonly()->setValue($values['name']);

			$row = $form->addRow();
				$row->addLabel('participant', __('Participant'));
				$row->addTextField('participant')->readonly()->setValue(Format::name('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student'));

			$roles = array(
                'Student'        => __('Student'),
                'Student - Left' => __('Student - Left'),
                'Teacher'        => __('Teacher'),
                'Teacher - Left' => __('Teacher - Left'),
                'Assistant'      => __('Assistant'),
                'Technician'     => __('Technician'),
                'Parent'         => __('Parent'),
            );

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
				$row->addSelect('role')->fromArray($roles)->isRequired()->selected($values['role']);
			
			$row = $form->addRow();
				$row->addLabel('reportable', __('Reportable'));
				$row->addYesNo('reportable')->isRequired()->selected($values['reportable']);

			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();

			echo $form->getOutput();
        }
    }
}
