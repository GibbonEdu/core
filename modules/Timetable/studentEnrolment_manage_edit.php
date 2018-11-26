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
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    if ($gibbonCourseClassID == '' or $gibbonCourseID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassID=:gibbonCourseClassID";
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

            $page->breadcrumbs
                ->add(__('Manage Student Enrolment'), 'studentEnrolment_manage.php')
                ->add(__('Edit %1$s.%2$s Enrolment', [
                    '%1$s' => $values['courseNameShort'],
                    '%2$s' => $values['name']
                ]));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo '<h2>';
            echo __('Add Participants');
            echo '</h2>';

            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID");

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
                    $group[$item['gibbonPersonID']] = $item['rollGroupName'].' - '.Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')';
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
                $row->addSelect('role')->fromArray($roles)->isRequired();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo '<h2>';
            echo __('Current Participants');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND (role='Student' OR role='Teacher') ORDER BY role DESC, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/studentEnrolment_manage_editProcessBulk.php');
                $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
                $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

                $bulkActions = array('Mark as left'  => __('Mark as left'));

                $row = $form->addBulkActionRow($bulkActions);
                $row->addSubmit(__('Go'));

                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                    $header->addContent(__('Name'));
                    $header->addContent(__('Email'));
                    $header->addContent(__('Role'));
                    $header->addContent(__('Actions'));
                    $header->addCheckAll();

                while ($student = $result->fetch()) {
                    $row = $table->addRow();
                    $name = Format::name('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', true);
                    if ($student['role'] == 'Student') {
                        $row->addWebLink($name)
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='. $student['gibbonPersonID'].'&subpage=Timetable');
                    } else {
                        $row->addContent($name);
                    }

                    $row->addContent($student['email']);
                    $row->addContent($student['role']);
                    $col = $row->addColumn()->addClass('inline');
                    if ($student['role'] == 'Student') {
                        $col->addWebLink('<img title="' . __('Edit') . '" src="./themes/' . $_SESSION[$guid]['gibbonThemeName'] . '/img/config.png"/>')
                            ->setURL($_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/' . $_SESSION[$guid]['module'] . '/studentEnrolment_manage_edit_edit.php')
                            ->addParam('gibbonCourseID', $gibbonCourseID)
                            ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                            ->addParam('gibbonPersonID', $student['gibbonPersonID']);
                        $row->addCheckbox('gibbonPersonID[]')->setValue($student['gibbonPersonID'])->setClass('textCenter');
                    }
                    else {
                        $row->addContent();
                    }

                }

                echo $form->getOutput();
            }

            echo '<h2>';
            echo __('Former Students');
            echo '</h2>';

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role='Student - Left' ORDER BY role DESC, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __('Name');
                echo '</th>';
                echo '<th>';
                echo __('Email');
                echo '</th>';
                echo '<th>';
                echo __('Class Role');
                echo '</th>';
                echo '<th>';
                echo __('Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                    echo '<td>';
                    if ($row['role'] == 'Student - Left') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&subpage=Timetable'>".Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</a>';
                    } else {
                        echo Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['email'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/studentEnrolment_manage_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonPersonID=".$row['gibbonPersonID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
