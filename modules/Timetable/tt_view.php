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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
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
        $gibbonTTID = null;
        if (isset($_GET['gibbonTTID'])) {
            $gibbonTTID = $_GET['gibbonTTID'];
        }

        try {
            if ($highestAction == 'View Timetable by Person_myChildren') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID1)
                    JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPerson.gibbonPersonID=:gibbonPersonID2
                    AND gibbonPerson.status='Full' AND gibbonFamilyAdult.childDataAccess='Y'
                    GROUP BY gibbonPerson.gibbonPersonID";
            } else {
                if ($allUsers == 'on') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                } else {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID1) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup, 'Staff' AS type, gibbonRoleIDPrimary FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID2) ORDER BY surname, preferredName";
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else if ($highestAction == 'View Timetable by Person_my' && $gibbonPersonID != $_SESSION[$guid]['gibbonPersonID']) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt.php&allUsers=$allUsers'>".__($guid, 'View Timetable by Person')."</a> > </div><div class='trailEnd'>".formatName($row['title'], $row['preferredName'], $row['surname'], $row['type']).'</div>';
            echo '</div>';

            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                $role = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                if ($role == 'Student' or $role == 'Staff' or $allUsers == 'on' or $search != '') {
                    echo "<div class='linkTop'>";

                    if ($search != '') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable/tt.php&search='.$search."&allUsers=$allUsers'>".__($guid, 'Back to Search Results').'</a>';
                    }
                    if ($role == 'Student' or $role == 'Staff' or $allUsers == 'on') {
                        if ($search != '') {
                            echo ' | ';
                        }
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=$role&allUsers=$allUsers'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    }
                    echo '</div>';
                }
            }

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo formatName($row['title'], $row['preferredName'], $row['surname'], $row['type'], false);
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
            if ($row['yearGroup'] != '') {
                echo '<i>'.__($guid, $row['yearGroup']).'</i>';
            }
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
            echo '<i>'.$row['rollGroup'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            $ttDate = null;
            if (isset($_POST['ttDate'])) {
                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
            }

            if (isset($_POST['fromTT'])) {
                if ($_POST['fromTT'] == 'Y') {
                    if (@$_POST['schoolCalendar'] == 'on' or @$_POST['schoolCalendar'] == 'Y') {
                        $_SESSION[$guid]['viewCalendarSchool'] = 'Y';
                    } else {
                        $_SESSION[$guid]['viewCalendarSchool'] = 'N';
                    }

                    if (@$_POST['personalCalendar'] == 'on' or @$_POST['personalCalendar'] == 'Y') {
                        $_SESSION[$guid]['viewCalendarPersonal'] = 'Y';
                    } else {
                        $_SESSION[$guid]['viewCalendarPersonal'] = 'N';
                    }
                }
            }

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_view.php', "&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }

            //Set sidebar
            $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
        }
    }
}
