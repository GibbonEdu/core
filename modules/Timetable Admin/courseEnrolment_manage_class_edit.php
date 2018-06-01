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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourseClass, gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Enrolment by Class')."</a> > </div><div class='trailEnd'>".sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $values['courseNameShort'], $values['name']).'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo '<h2>';
            echo __($guid, 'Add Participants');
            echo '</h2>'; 
            
            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID");
                
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $people = array();

            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupIDList' => $values['gibbonYearGroupIDList']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonRollGroup.name AS rollGroupName
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' 
                    AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)
                    ORDER BY rollGroupName, surname, preferredName";
            $result = $pdo->executeQuery($data, $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('Enrolable Students').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = $item['rollGroupName'].' - '.formatName('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')';
                    return $group;
                }, array());
            }

            $sql = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery(array(), $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('All Users').'--'] = array_reduce($result->fetchAll(), function($group, $item) {
                    $expected = ($item['status'] == 'Expected')? '('.__('Expected').')' : '';
                    $group[$item['gibbonPersonID']] = formatName('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')'.$expected;
                    return $group;
                }, array());
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
            echo __($guid, 'Current Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND NOT role LIKE '%left' ORDER BY role DESC, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                Format::addFormatter('linkedName', function ($person) use ($guid) {
                    $isStudent = stripos($person['role'], 'Student') !== false;
                    $name = Format::name('', $person['preferredName'], $person['surname'], $isStudent ? 'Student' : 'Staff', true, true);
                    return $isStudent 
                        ? Format::link($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Timetable', $name)
                        : $name;
                });

                $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

                // QUERY
                $criteria = $courseEnrolmentGateway->newQueryCriteria()
                    ->sortBy('roleSortOrder')
                    ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                    ->fromArray($_POST);

                $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID);


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
                    $data= array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID<>:gibbonCourseClassID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
                    $col->addSelect('gibbonCourseClassIDCopyTo')->fromQuery($pdo, $sql, $data)->setClass('shortWidth copyTo');
                    $col->addSubmit(__('Go'));

                $form->toggleVisibilityByClass('copyTo')->onSelect('action')->when('Copy to class');

                // DATA TABLE
                $table = $form->addRow()->addDataTable('enrolment', $criteria)->withData($enrolment);

                $table->addMetaData('bulkActions', $col);

                $table->addColumn('name', __('Name'))
                    ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                    ->format(Format::using('linkedName'));
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
            }

            echo '<h2>';
            echo __($guid, 'Former Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role LIKE '%left' ORDER BY role DESC, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {

                $table = DataTable::create('enrolmentLeft');

                $table->addColumn('name', __('Name'))
                    ->sortable(['surname', 'preferredName'])
                    ->format(Format::using('linkedName'));
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

                echo $table->render($result->toDataSet());
            }
        }
    }
}
