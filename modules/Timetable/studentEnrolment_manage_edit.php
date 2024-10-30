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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonCourseClassID and gibbonCourseID specified
    $gibbonPersonID = $session->get('gibbonPersonID');
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassID=:gibbonCourseClassID";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Manage Student Enrolment'), 'studentEnrolment_manage.php')
                ->add(__('Edit %1$s.%2$s Enrolment', [
                    '%1$s' => $values['courseNameShort'],
                    '%2$s' => $values['name']
                ]));

            $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

            $form = Form::create('manageEnrolment', $session->get('absoluteURL').'/modules/'.$session->get('module')."/studentEnrolment_manage_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID");
            $form->setTitle(__('Add Participants'));

            $form->addHiddenValue('address', $session->get('address'));

            $people = array();

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupIDList' => $values['gibbonYearGroupIDList']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS formGroupName
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                    AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)
                    ORDER BY formGroupName, surname, preferredName";
            $result = $pdo->executeQuery($data, $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('Enrolable Students').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = $item['formGroupName'].' - '.Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')';
                    return $group;
                }, array());
            }

            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery(array(), $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('All Students').'--'] = array_reduce($result->fetchAll(), function($group, $item) {
                    $expected = ($item['status'] == 'Expected')? '('.__('Expected').')' : '';
                    $group[$item['gibbonPersonID']] = Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')'.$expected;
                    return $group;
                }, array());
            }

            $row = $form->addRow();
                $row->addLabel('Members', __('Participants'));
                $row->addSelect('Members')->fromArray($people)->selectMultiple();

            $roles = array(
                'Student'    => __('Student'),
            );

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($roles)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            // QUERY
            $criteria = $courseEnrolmentGateway->newQueryCriteria(true)
                ->sortBy('roleSortOrder')
                ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->fromPOST();

            $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID, false, true);

            $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/studentEnrolment_manage_editProcessBulk.php');
            $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
            $form->setTitle(__('Current Participants'));

            $bulkActions = array('Mark as left'  => __('Mark as left'));

            $col = $form->createBulkActionColumn($bulkActions);
            $col->addSubmit(__('Go'));

            // DATA TABLE
            $table = $form->addRow()->addDataTable('enrolment', $criteria)->withData($enrolment);
            

            $table->modifyRows(function ($person, $row) {
                if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) $row->addClass('error');
                return $row;
            });
            $table->addMetaData('bulkActions', $col);

            $table->addColumn('name', __('Name'))
                ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->format(function ($person) {
                    $isStudent = stripos($person['role'], 'Student') !== false;
                    $name = Format::name('', $person['preferredName'], $person['surname'], $isStudent ? 'Student' : 'Staff', true, true);
                    
                    return $isStudent
                        ? Format::link('./index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Timetable', $name).'<br/>'.Format::userStatusInfo($person)
                        : $name;
                });
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'))->translatable();

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->addParam('gibbonCourseClassPersonID')
                ->addParam('gibbonPersonID')
                ->format(function ($person, $actions) {
                    if ($person['role'] == 'Teacher') return;
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable/studentEnrolment_manage_edit_edit.php');
                });

            $table->addCheckboxColumn('gibbonPersonID')
                ->format(function ($person) {
                    if ($person['role'] == 'Teacher') return __('N/A');
                });

            echo $form->getOutput();
            

            $enrolmentLeft = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID, true, true);

            $table = DataTable::createPaginated('enrolmentLeft', $criteria);
            $table->setTitle(__('Former Participants'));

            $table->modifyRows(function ($person, $row) {
                if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) $row->addClass('error');
                return $row;
            });
            
            $table->addColumn('name', __('Name'))
                ->sortable(['surname', 'preferredName'])
                ->format(function ($person) {
                    $isStudent = stripos($person['role'], 'Student') !== false;
                    $name = Format::name('', $person['preferredName'], $person['surname'], $isStudent ? 'Student' : 'Staff', true, true);
                    
                    return $isStudent
                        ? Format::link('./index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Timetable', $name).'<br/>'.Format::userStatusInfo($person)
                        : $name;
                });
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->addParam('gibbonCourseClassPersonID')
                ->addParam('gibbonPersonID')
                ->format(function ($person, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable/studentEnrolment_manage_edit_edit.php');
                });

            echo $table->render($enrolmentLeft);

        }
    }
}
