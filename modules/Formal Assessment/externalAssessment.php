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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View All Assessments').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Search');
    echo '</h2>';

    $search = isset($_GET['search'])? $_GET['search'] : '';
    $allStudents = isset($_GET['allStudents'])? $_GET['allStudents'] : '';

    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth standardForm');
    
    $form->addHiddenValue('q', '/modules/Formal Assessment/externalAssessment.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
        $row->addCheckbox('allStudents')->checked($allStudents);
        
    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));
        
    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'Choose A Student');
    echo '</h2>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        if ($allStudents != 'on') {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            if ($search != '') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                $and = ' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3))';
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' $and ORDER BY surname, preferredName";
            }
        } else {
            $data = array();
            $sql = 'SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) ORDER BY surname, preferredName';
            if ($search != '') {
                $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                $and = ' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3))';
                $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) $and ORDER BY surname, preferredName";
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
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search&allStudents=$allStudents");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
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
            if ($row['yearGroup'] != '') {
                echo __($guid, $row['yearGroup']);
            }
            echo '</td>';
            echo '<td>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/externalAssessment_details.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search&allStudents=$allStudents'><img title='View Assessments' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search&allStudents=$allStudents");
        }
    }
}
?>
