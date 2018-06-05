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
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = isset($_GET['gibbonCourseClassID'])? $_GET['gibbonCourseClassID'] : '';
    $gibbonCourseID = isset($_GET['gibbonCourseID'])? $_GET['gibbonCourseID'] : '';
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';

    if (empty($gibbonCourseID) or empty($gibbonSchoolYearID) or empty($gibbonCourseClassID)) {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $userGateway = $container->get(UserGateway::class);
        $courseGateway = $container->get(CourseGateway::class);
        $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

        $values = $courseGateway->getCourseClassByID($gibbonCourseClassID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Enrolment by Class')."</a> > </div><div class='trailEnd'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $values['courseNameShort'], $values['name']).'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo '<h2>';
            echo __('Add Participants');
            echo '</h2>'; 
            
            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID");
                
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
                    return Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].', '.$item['roleCategory'].')'.$expected;
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
                $row->addSelect('role')->fromArray($roles)->isRequired();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo '<h2>';
            echo __('Current Participants');
            echo '</h2>';

            $linkedName = function ($person) use ($guid) {
                $isStudent = stripos($person['role'], 'Student') !== false;
                $name = Format::name('', $person['preferredName'], $person['surname'], $isStudent ? 'Student' : 'Staff', true, true);
                return $isStudent 
                    ? Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Timetable', $name)
                    : $name;
            };

            // QUERY
            $criteria = $courseEnrolmentGateway->newQueryCriteria()
                ->sortBy('roleSortOrder')
                ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->fromArray($_POST);

            $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID);

            // FORM
            $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_class_editProcessBulk.php');
            $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
            $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $linkParams = array(
                'gibbonCourseID'      => $gibbonCourseID,
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'gibbonSchoolYearID'  => $gibbonSchoolYearID,
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

            $table->addMetaData('bulkActions', $col);

            $table->addColumn('name', __('Name'))
                  ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                  ->format($linkedName);
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'));
            $table->addColumn('reportable', __('Reportable'))
                  ->format(Format::using('yesNo', 'reportable'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonPersonID')
                ->addParams($linkParams)
                ->format(function ($person, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit_delete.php');
                });

            $table->addCheckboxColumn('gibbonPersonID');

            echo $form->getOutput();


            echo '<h2>';
            echo __('Former Participants');
            echo '</h2>';

            $enrolmentLeft = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID, true);

            $table = DataTable::createPaginated('enrolmentLeft', $criteria);

            $table->addColumn('name', __('Name'))
                ->sortable(['surname', 'preferredName'])
                ->format($linkedName);
            $table->addColumn('email', __('Email'));
            $table->addColumn('role', __('Class Role'));

            // ACTIONS
            $table->addActionColumn()
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
