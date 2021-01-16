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
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;

//Module includes for Timetable module
include './modules/Timetable/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if school year specified
    $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $type = isset($_GET['type'])? $_GET['type'] : '';
    $allUsers = isset($_GET['allUsers']) ? $_GET['allUsers'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    if (empty($gibbonPersonID) or empty($gibbonSchoolYearID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $courseGateway = $container->get(CourseGateway::class);
        $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

        try {
            if ($allUsers == 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, NULL AS gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, NULL AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) 
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
            } else {
                if ($type == 'Student') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID)";
                } elseif ($type == 'Staff') {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS gibbonYearGroupID, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE gibbonStaff.type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID) ORDER BY surname, preferredName";
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Course Enrolment by Person'), 'courseEnrolment_manage_byPerson.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'allUsers' => $allUsers])
                ->add(Format::name('', $values['preferredName'], $values['surname'], 'Student'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson.php&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Back to Search Results').'</a> | ';
            }
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers'>".__('View')."<img style='margin: 0 0 -4px 3px' title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
            echo '</div>';

            //INTERFACE TO ADD NEW CLASSES
            echo '<h2>';
            echo __('Add Classes');
            echo '</h2>';
            
            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_edit_addProcess.php?type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
                
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $classes = array();
            if ($type == 'Student') {
                $enrolableClasses = $courseEnrolmentGateway->selectEnrolableClassesByYearGroup($gibbonSchoolYearID, $values['gibbonYearGroupID'])->fetchAll();

                if (!empty($enrolableClasses)) {
                    $classes['--'.__('Enrolable Classes').'--'] = Format::keyValue($enrolableClasses, 'gibbonCourseClassID', function ($item) {
                        $courseClassName = Format::courseClassName($item['course'], $item['class']);
                        $teacherName = Format::name('', $item['preferredName'], $item['surname'], 'Staff');

                        return $courseClassName .' - '. (!empty($teacherName)? $teacherName.' - ' : '') . $item['studentCount'] . ' '.__('students');
                    });
                }
            }

            $allClasses = $courseGateway->selectClassesBySchoolYear($gibbonSchoolYearID)->fetchAll();

            if (!empty($allClasses)) {
                $classes['--'.__('All Classes').'--'] = Format::keyValue($allClasses, 'gibbonCourseClassID', function ($item) {
                    return Format::courseClassName($item['course'], $item['class']) .' - '. $item['courseName'];
                });
            }

            $row = $form->addRow();
                $row->addLabel('Members', __('Classes'));
                $row->addSelect('Members')->fromArray($classes)->selectMultiple();

            $roles = array(
                'Student'    => __('Student'),
                'Teacher'    => __('Teacher'),
                'Assistant'  => __('Assistant'),
                'Technician' => __('Technician'),
            );
            $selectedRole = ($type == 'Staff')? 'Teacher' : $type;

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($roles)->required()->selected($selectedRole);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            
            //SHOW CURRENT ENROLMENT
            echo '<h2>';
            echo __('Current Enrolment');
            echo '</h2>';

            // QUERY
            $criteria = $courseEnrolmentGateway->newQueryCriteria(true)
                ->sortBy('roleSortOrder')
                ->sortBy(['course', 'class'])
                ->fromPOST();

            $enrolment = $courseEnrolmentGateway->queryCourseEnrolmentByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID);

            // FORM
            $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/courseEnrolment_manage_byPerson_editProcessBulk.php?allUsers='.$allUsers);
            $form->addHiddenValue('type', $type);
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $linkParams = array(
                'gibbonSchoolYearID' => $gibbonSchoolYearID,
                'gibbonPersonID'     => $gibbonPersonID,
                'type'               => $type,
                'allUsers'           => $allUsers,
                'search'             => $search,
            );

            $bulkActions = array(
                'Mark as left' => __('Mark as left'),
                'Delete'       => __('Delete'),
            );

            $col = $form->createBulkActionColumn($bulkActions);
                $col->addSubmit(__('Go'));

            // DATA TABLE
            $table = $form->addRow()->addDataTable('enrolment', $criteria)->withData($enrolment);

            $table->addMetaData('bulkActions', $col);

            $table->addColumn('courseClass', __('Class Code'))
                  ->sortable(['course', 'class'])
                  ->format(Format::using('courseClassName', ['course', 'class']));
            $table->addColumn('courseName', __('Course'));
            $table->addColumn('role', __('Class Role'))->translatable();
            $table->addColumn('reportable', __('Reportable'))
                  ->format(Format::using('yesNo', 'reportable'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonCourseClassID')
                ->addParams($linkParams)
                ->format(function ($class, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_delete.php');
                });

            $table->addCheckboxColumn('gibbonCourseClassID');

            echo $form->getOutput();


            //SHOW CURRENT TIMETABLE IN EDIT VIEW
            echo "<a name='tt'></a>";
            echo '<h2>';
            echo __('Current Timetable View');
            echo '</h2>';

            $gibbonTTID = isset($_GET['gibbonTTID'])? $_GET['gibbonTTID'] : null;
            $ttDate = isset($_POST['ttDate'])? dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate'])) : null;

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php', "&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=$gibbonSchoolYearID&type=$type&allUsers=$allUsers#tt", 'full', true);
            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            }

            //SHOW OLD ENROLMENT RECORDS
            echo '<h2>';
            echo __('Old Enrolment');
            echo '</h2>';

            $enrolmentLeft = $courseEnrolmentGateway->queryCourseEnrolmentByPerson($criteria, $gibbonSchoolYearID, $gibbonPersonID, true);

            $table = DataTable::createPaginated('enrolmentLeft', $criteria);

            $table->addColumn('courseClass', __('Class Code'))
                ->sortable(['course', 'class'])
                ->format(Format::using('courseClassName', ['course', 'class']));
            $table->addColumn('courseName', __('Course'));
            $table->addColumn('role', __('Class Role'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonCourseClassID')
                ->addParams($linkParams)
                ->format(function ($class, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php');
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_delete.php');
                });

            echo $table->render($enrolmentLeft);
        }
    }
}
