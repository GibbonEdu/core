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
use Gibbon\Forms\DatabaseFormFactory;

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Behaviour Records').'</div>';
        echo '</div>';

        $search = null;

        if ($highestAction == 'View Behaviour Records_all') {
            echo '<h2>';
            echo __($guid, 'Search');
            echo '</h2>';

            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }

            $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
                $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', '/modules/Behaviour/behaviour_view.php');

            $row = $form->addRow();
                $row->addLabel('search',__('Search For'))->description('Preferred, surname, username.');
                $row->addTextField('search')->setValue($search)->maxLength(30);

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session, __('Clear Search'));

            echo $form->getOutput();

        }

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
            if ($highestAction == 'View Behaviour Records_all') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                if ($search != '') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                }
            } else {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonFamilyChild ON (gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND childDataAccess='Y') WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
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
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
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
                echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID']."&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
                echo '</td>';
                echo '<td>';
                echo __($guid, $row['yearGroup']);
                echo '</td>';
                echo '<td>';
                echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/behaviour_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
            }
        }
    }
}
?>