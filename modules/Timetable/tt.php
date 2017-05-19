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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Timetable by Person').'</div>';
        echo '</div>';

        $gibbonPersonID = null;
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $allUsers = null;
        if (isset($_GET['allUsers'])) {
            $allUsers = $_GET['allUsers'];
        }

        if ($highestAction == 'View Timetable by Person' || $highestAction == 'View Timetable by Person_allYears') {

            echo '<h2>';
            echo __($guid, 'Filters');
            echo '</h2>';

            ?>
            <form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
                <table class='noIntBorder' cellspacing='0' style="width: 100%">
                    <tr><td style="width: 30%"></td><td></td></tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Search For') ?></b><br/>
                            <span class="emphasis small">Preferred, surname, username.</span>
                        </td>
                        <td class="right">
                            <input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" class="standardWidth">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'All Users') ?></b><br/>
                            <span class="emphasis small"><?php echo __($guid, 'Include non-staff, non-student users.') ?></span>
                        </td>
                        <td class="right">
                            <?php
                            $checked = '';
                            if ($allUsers == 'on') {
                                $checked = 'checked';
                            }
                            echo "<input $checked name=\"allUsers\" id=\"allUsers\" type=\"checkbox\">";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2 class="right">
                            <input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/tt.php">
                            <input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
                            <?php
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/tt.php'>".__($guid, 'Clear Filters').'</a>'; ?>
                            <input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
                        </td>
                    </tr>
                </table>
            </form>
            <?php

        }

        echo '<h2>';
        echo __($guid, 'Choose A Person');
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
            if ($highestAction == 'View Timetable by Person_my') {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "(
                        SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type
                        FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                        WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonPerson.status='Full'
                    ) UNION (
                        SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type
                        FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                        WHERE type='Teaching' AND gibbonPerson.status='Full' AND gibbonStaff.gibbonPersonID=:gibbonPersonID
                    ) ORDER BY surname, preferredName";
            } else if ($highestAction == 'View Timetable by Person_myChildren') {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type
                        FROM gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID)
                        JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND gibbonPerson.status='Full' AND gibbonFamilyAdult.childDataAccess='Y'
                        GROUP BY gibbonPerson.gibbonPersonID
                        ORDER BY surname, preferredName";
            } else if ($highestAction == 'View Timetable by Person' || $highestAction == 'View Timetable by Person_allYears') {
                if ($allUsers == 'on') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
                    if ($search != '') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3)) ORDER BY surname, preferredName";
                    }
                } else {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full') UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full') ORDER BY surname, preferredName";
                    if ($search != '') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%", 'search4' => "%$search%", 'search5' => "%$search%", 'search6' => "%$search%");
                        $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND ((preferredName LIKE :search1) OR (surname LIKE :search2) OR (username LIKE :search3))) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full' AND ((preferredName LIKE :search4) OR (surname LIKE :search5) OR (username LIKE :search6))) ORDER BY surname, preferredName";
                    }
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
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "allUsers=$allUsers&search=$search");
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
                if ($row['type'] == 'Staff') {
                    echo formatName($row['title'], $row['preferredName'], $row['surname'], $row['type'], true);
                }
                if ($row['type'] == 'Student') {
                    echo formatName($row['title'], $row['preferredName'], $row['surname'], $row['type'], true);
                }
                echo '</td>';
                echo '<td>';
                if ($row['yearGroup'] != '') {
                    echo __($guid, $row['yearGroup']);
                }
                echo '</td>';
                echo '<td>';
                if ($row['rollGroup'] != '') {
                    echo $row['rollGroup'];
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt_view.php&gibbonPersonID='.$row['gibbonPersonID']."&allUsers=$allUsers&search=$search'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "allUsers=$allUsers&search=$search");
            }
        }
    }
}
?>
