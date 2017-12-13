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

//Module includes for Timetable module
include './modules/Timetable/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $type = $_GET['type'];
    $allUsers = '';
    if (isset($_GET['allUsers'])) {
        $allUsers = $_GET['allUsers'];
    }
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if ($gibbonPersonID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            if ($allUsers == 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, NULL AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
            } else {
                if ($type == 'Student') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID)";
                } elseif ($type == 'Staff') {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS gibbonYearGroupID, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE (gibbonStaff.type='Teaching' OR gibbonRole.category = 'Staff') AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID) ORDER BY surname, preferredName";
                }
            }
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
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&allUsers=$allUsers'>".__($guid, 'Enrolment by Person')."</a> > </div><div class='trailEnd'>".$values['preferredName'].' '.$values['surname'].'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson.php&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Back to Search Results').'</a> | ';
            }
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers'>".__($guid, 'View')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> ";
            echo '</div>';

            //INTERFACE TO ADD NEW CLASSES
            echo '<h2>';
            echo __($guid, 'Add Classes');
            echo '</h2>'; 
            
            $form = Form::create('manageEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/courseEnrolment_manage_byPerson_edit_addProcess.php?type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
                
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $classes = array();
            if ($type == 'Student') {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupID' => $values['gibbonYearGroupID']);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS name, 
                            (SELECT count(*) FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND (status='Full' OR status='Expected') AND role='Student') AS studentCount
                        FROM gibbonCourse
                        JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                        AND FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList) 
                        GROUP BY gibbonCourseClass.gibbonCourseClassID
                        ORDER BY name";

                $result = $pdo->executeQuery($data, $sql);
                if ($result->rowCount() > 0) {
                    $classes['--'.__('Enrolable Classes').'--'] = array_reduce($result->fetchAll(), function($group, $item) use (&$pdo) {
                        $data = array('gibbonCourseClassID' => $item['value']);
                        $sql = "SELECT surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID";
                        $result = $pdo->executeQuery($data, $sql);
                        $teachers = array_map(function ($teacher) {
                            return formatName('', $teacher['preferredName'], $teacher['surname'], 'Staff', false);
                        }, $result->fetchAll());

                        $item['name'] .= (!empty($teachers))? ' - '.implode(', ', $teachers) : '';
                        $item['name'] .= ' - '.$item['studentCount'].' '.__('students');

                        $group[$item['value']] = $item['name'];
                        return $group;
                    }, array());
                }
            }

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort, ' - ', gibbonCourse.name) AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
            $result = $pdo->executeQuery($data, $sql);
            if ($result->rowCount() > 0) {
                $classes['--'.__('All Classes').'--'] = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
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
                $row->addSelect('role')->fromArray($roles)->isRequired()->selected($selectedRole);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            
            //SHOW CURRENT ENROLMENT
            echo '<h2>';
            echo __($guid, 'Current Enrolment');
            echo '</h2>';

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role, gibbonCourseClassPerson.reportable FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%left' ORDER BY class, course";
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

                $row = $form->addBulkActionRow($bulkActions);
                    $row->addSubmit(__('Go'));

                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                    $header->addContent(__('Class Code'));
                    $header->addContent(__('Course'));
                    $header->addContent(__('Class Role'));
                    $header->addContent(__('Reportable'));
                    $header->addContent(__('Actions'));
                    $header->addCheckAll();

                while ($class = $result->fetch()) {
                    $row = $table->addRow();
                        $row->addContent($class['course'].'.'.$class['class']);
                        $row->addContent($class['name']);
                        $row->addContent($class['role']);
                        $row->addContent($class['reportable']);
                        $col = $row->addColumn()->addClass('inline');
                            $col->addWebLink('<img title="'.__('Edit').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/config.png"/>')
                                ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_edit.php')
                                ->addParam('gibbonCourseClassID', $class['gibbonCourseClassID'])
                                ->addParams($linkParams);
                            $col->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/>')
                                ->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_delete.php&width=650&height=135')
                                ->setClass('thickbox')
                                ->addParam('gibbonCourseClassID', $class['gibbonCourseClassID'])
                                ->addParams($linkParams);
                        $row->addCheckbox('gibbonCourseClassID[]')->setValue($class['gibbonCourseClassID'])->setClass('textCenter');
                }

                echo $form->getOutput();
            }


            //SHOW CURRENT TIMETABLE IN EDIT VIEW
            echo "<a name='tt'></a>";
            echo '<h2>';
            echo 'Current Timetable View';
            echo '</h2>';

            $gibbonTTID = null;
            if (isset($_GET['gibbonTTID'])) {
                $gibbonTTID = $_GET['gibbonTTID'];
            }
            $ttDate = null;
            if (isset($_REQUEST['ttDate'])) {
                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_REQUEST['ttDate']));
            }

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php', "&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=$gibbonSchoolYearID&type=$type#tt", 'full', true);
            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }

            //SHOW OLD ENROLMENT RECORDS
            echo '<h2>';
            echo __('Old Enrolment');
            echo '</h2>';

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND role LIKE '%left' ORDER BY course, class";
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
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Class Code');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Course');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Class Role');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
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
                    echo $row['course'].'.'.$row['class'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['name'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['role'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_edit.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit_delete.php&gibbonCourseClassID='.$row['gibbonCourseClassID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>
