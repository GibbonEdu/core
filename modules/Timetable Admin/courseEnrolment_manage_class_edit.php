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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $search = $_GET['search'] ?? '';

    if (empty($gibbonCourseID) or empty($gibbonSchoolYearID) or empty($gibbonCourseClassID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $userGateway = $container->get(UserGateway::class);
        $courseGateway = $container->get(CourseGateway::class);
        $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

        $values = $courseGateway->getCourseClassByID($gibbonCourseClassID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $page->breadcrumbs
                ->add(__('Course Enrolment by Class'), 'courseEnrolment_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
                ->add(__('Edit %1$s.%2$s Enrolment', ['%1$s' => $values['courseNameShort'], '%2$s' => $values['name']]));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage.php&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Back to Search Results').'</a>';
            }
            echo '</div>';
            
            echo '<h2>';
            echo __('Add Participants');
            echo '</h2>';

            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
                
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $people = array();

            $enrolableStudents = $courseEnrolmentGateway->selectEnrolableStudentsByYearGroup($gibbonSchoolYearID, $values['gibbonYearGroupIDList'])->fetchAll();
            if (!empty($enrolableStudents)) {
                $people['--'.__('Enrolable Students').'--'] = Format::keyValue($enrolableStudents, 'gibbonPersonID', function ($item) {
                    return $item['rollGroupName'].' - '.Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].')';
                });
            }

            $allUsers = $userGateway->selectUserNamesByStatus(['Full', 'Expected'])->fetchAll();
            if (!empty($allUsers)) {
                $people['--'.__('All Users').'--'] = Format::keyValue($allUsers, 'gibbonPersonID', function ($item) {
                    $expected = ($item['status'] == 'Expected')? '('.__('Expected').')' : '';
                    return Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].', '.__($item['roleCategory']).')'.$expected;
                });
            }

            $row = $form->addRow();
                $row->addLabel('Members', __('Participants'));
                $row->addSelect('Members')->fromArray($people)->selectMultiple();

            $roles = array(
                'Student'    => __('Student'),
                'Teacher'    => __('Teacher'),
                'Assistant'  => __('Assistant'),
                'Technician' => __('Technician'),
                'Parent'     => __('Parent'),
            );

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($roles)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            $linkedName = function ($person) use ($guid) {
                $isStudent = stripos($person['role'], 'Student') !== false;
                $name = Format::name('', $person['preferredName'], $person['surname'], $isStudent ? 'Student' : 'Staff', true, true);
                return $isStudent
                    ? Format::link('./index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Timetable', $name).'<br/>'.Format::userStatusInfo($person)
                    : $name;
            };

            // QUERY
            $criteria = $courseEnrolmentGateway->newQueryCriteria(true)
                ->sortBy('roleSortOrder')
                ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->fromPOST();

            $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID, false, true);

            // FORM
            $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_class_editProcessBulk.php');
            $form->setTitle(__('Current Participants'));

            $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            $form->addHiddenValue('search', $search);

            $linkParams = array(
                'gibbonCourseID'      => $gibbonCourseID,
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'gibbonSchoolYearID'  => $gibbonSchoolYearID,
                'search'  => $search,
            );

            $bulkActions = array(
                'Copy to class' => __('Copy to class'),
                'Mark as left'  => __('Mark as left'),
                'Delete'        => __('Delete'),
            );

            $col = $form->createBulkActionColumn($bulkActions);
                $classesBySchoolYear = $courseGateway->selectClassesBySchoolYear($gibbonSchoolYearID)->fetchAll();
                $classesBySchoolYear = Format::keyValue($classesBySchoolYear, 'gibbonCourseClassID', 'courseClassName', ['course', 'class']);
                $col->addSelect('gibbonCourseClassIDCopyTo')->fromArray($classesBySchoolYear)->setClass('shortWidth copyTo');
                $col->addSubmit(__('Go'));

            $form->toggleVisibilityByClass('copyTo')->onSelect('action')->when('Copy to class');

            // DATA TABLE
            $table = $form->addRow()->addDataTable('enrolment', $criteria)->withData($enrolment);

            $table->modifyRows(function ($person, $row) {
                if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) $row->addClass('error');
                return $row;
            });
            $table->addMetaData('bulkActions', $col);

            $table->addColumn('name', __('Name'))
                  ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                  ->format($linkedName);
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'))->translatable();
            $table->addColumn('reportable', __('Reportable'))
                  ->format(Format::using('yesNo', 'reportable'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonCourseClassPersonID')
                ->addParam('gibbonPersonID')
                ->addParams($linkParams)
                ->format(function ($person, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_delete.php');
                });

            $table->addCheckboxColumn('gibbonCourseClassPersonID');

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
                ->format($linkedName);
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonCourseClassPersonID')
                ->addParam('gibbonPersonID')
                ->addParams($linkParams)
                ->format(function ($person, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_delete.php');
                });

            echo $table->render($enrolmentLeft);
        }
    }
}
