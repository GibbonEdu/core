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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $search = $_GET['search'] ?? '';
        $allUsers = $_GET['allUsers'] ?? '';
        $gibbonTTID = $_GET['gibbonTTID'] ?? '';


        $canViewAllTimetables = $highestAction == 'View Timetable by Person' || $highestAction == 'View Timetable by Person_allYears';

        try {
            if ($highestAction == 'View Timetable by Person_myChildren') {
                $data = array('gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $gibbon->session->get('gibbonPersonID'), 'gibbonPersonID2' => $gibbonPersonID);
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
                if ($allUsers == 'on' && $canViewAllTimetables && $gibbon->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
                    $data = array('gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) 
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                } else {
                    $data = array('gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID1) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup, 'Staff' AS type, gibbonRoleIDPrimary FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE gibbonStaff.type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID2) ORDER BY surname, preferredName";
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else if ($highestAction == 'View Timetable by Person_my' && $gibbonPersonID != $gibbon->session->get('gibbonPersonID')) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            $page->breadcrumbs
                ->add(__('View Timetable by Person'), 'tt.php', ['allUsers' => $allUsers])
                ->add(Format::name($row['title'], $row['preferredName'], $row['surname'], $row['type']));


            $canEdit = isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php');
            $roleCategory = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
            if ($allUsers == 'on' or $search != '' or $canEdit) {
                echo "<div class='linkTop'>";
                if ($search != '') {
                    echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Timetable/tt.php&search='.$search."&allUsers=$allUsers'>".__('Back to Search Results').'</a>';
                }
                if ($canEdit && ($roleCategory == 'Student' or $roleCategory == 'Staff')) {
                    if ($search != '') {
                        echo ' | ';
                    }
                    echo "<a href='".$gibbon->session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$gibbon->session->get('gibbonSchoolYearID')."&type=$roleCategory&allUsers=$allUsers'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                }
                echo '</div>';
            }

            // DISPLAY PERSON DATA
            $table = DataTable::createDetails('personal');
            $table->addColumn('name', __('Name'))->format(Format::using('name', ['title', 'preferredName', 'surname', 'type', 'false']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('rollGroup', __('Roll Group'));

            echo $table->render([$row]);


            $ttDate = null;
            if (isset($_POST['ttDate'])) {
                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
            }

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_view.php', "&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            }

            //Set sidebar
            $gibbon->session->set('sidebarExtra', getUserPhoto($guid, $row['image_240'], 240));
        }
    }
}
