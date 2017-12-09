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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Course Enrolment by Person').'</div>';
    echo '</div>';

    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';

    if (empty($gibbonSchoolYearID) || $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    } else {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);
        
        $gibbonSchoolYearName = ($result->rowCount() > 0)? $result->fetchColumn(0) : '';
    }

    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearName)) {
        echo '<div class="error">';
        echo __('The specified record does not exist.');
        echo '</div>';
    } else {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
			echo ' | ';
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
				echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
			} else {
				echo __($guid, 'Next Year').' ';
			}
        echo '</div>';

        $allUsers = isset($_GET['allUsers'])? $_GET['allUsers'] : '';
        $search = isset($_GET['search'])? $_GET['search'] : '';

        echo '<h3>';
        echo __($guid, 'Filters');
        echo '</h3>'; 
        
        $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addLabel('allUsers', __('All Users'))->description(__('Include non-staff, non-student users.'));
            $row->addCheckbox('allUsers')->setValue('on')->checked($allUsers);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __($guid, 'View');
        echo '</h3>';

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        try {
            if ($allUsers == 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
                if ($search != '') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) ORDER BY surname, preferredName";
                }
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full') UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.status='Full') ORDER BY surname, preferredName";
                if ($search != '') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%", 'search4' => "%$search%", 'search5' => "%$search%", 'search6' => "%$search%");
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3))) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.status='Full' AND ((preferredName LIKE :search4) OR (surname LIKE :search5) OR (username LIKE :search6))) ORDER BY surname, preferredName";
                }
            }
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
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
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Role Category');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Year Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                echo '<td>';
                if ($allUsers == 'on') {
                    if ($row['yearGroup'] != '') {
                        echo __($guid, 'Student');
                    } else {
                        echo __($guid, 'Non-student');
                    }
                } else {
                    echo $row['type'];
                }
                echo '</td>';
                echo '<td>';
                if ($row['yearGroup'] != '') {
                    echo __($guid, $row['yearGroup']);
                }
                echo '</td>';
                echo '<td>';
                echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID='.$row['gibbonPersonID']."&gibbonSchoolYearID=$gibbonSchoolYearID&type=".$row['type']."&allUsers=$allUsers&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }
        }
    }
}
?>
