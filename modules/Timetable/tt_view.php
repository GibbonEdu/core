<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\DataSet;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $search = $_GET['search'] ?? '';
        $allUsers = $_GET['allUsers'] ?? '';
        $gibbonTTID = $_GET['gibbonTTID'] ?? '';


        $canViewAllTimetables = $highestAction == 'View Timetable by Person' || $highestAction == 'View Timetable by Person_allYears';

        try {
            if ($highestAction == 'View Timetable by Person_myChildren') {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $session->get('gibbonPersonID'), 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, 'Student' AS type, gibbonRoleIDPrimary
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID1)
                    JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPerson.gibbonPersonID=:gibbonPersonID2
                    AND gibbonPerson.status='Full' AND gibbonFamilyAdult.childDataAccess='Y'
                    GROUP BY gibbonPerson.gibbonPersonID";
            } else {
                if ($allUsers == 'on' && $canViewAllTimetables && $session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $gibbonPersonID, 'gibbonPersonID2' => $gibbonPersonID);
                    $sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, 'Student' AS type, gibbonRoleIDPrimary FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonFormGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID1) UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS formGroup, 'Staff' AS type, gibbonRoleIDPrimary FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary) WHERE gibbonStaff.type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID2) ORDER BY surname, preferredName";
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else if ($highestAction == 'View Timetable by Person_my' && $gibbonPersonID != $session->get('gibbonPersonID')) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $row = $result->fetch();

            $page->breadcrumbs
                ->add(__('View Timetable by Person'), 'tt.php', ['allUsers' => $allUsers])
                ->add(Format::name($row['title'], $row['preferredName'], $row['surname'], $row['type']));

            $canEdit = isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php');

            /** @var RoleGateway */
            $roleGateway = $container->get(RoleGateway::class);

            $roleCategory = $roleGateway->getRoleCategory($row['gibbonRoleIDPrimary']);

            // DISPLAY PERSON DATA
            $table = DataTable::createDetails('personal');

            if ($search != '') {
                $params = [
                    "search" => $search,
                    "allUsers" => $allUsers,
                ];
                $table->addHeaderAction('back', __('Back to Search Results'))
                    ->setURL('/modules/Timetable/tt.php')
                    ->addParams($params)
                    ->setIcon('search')
                    ->displayLabel();
            }
            if ($canEdit && ($roleCategory == 'Student' or $roleCategory == 'Staff')) {
                $params = [
                    "gibbonPersonID" => $gibbonPersonID,
                    "gibbonSchoolYearID" => $session->get('gibbonSchoolYearID'),
                    "type" => $roleCategory,
                    "allUsers" => $allUsers,
                ];
                $table->addHeaderAction('edit', __('Edit'))
                    ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
                    ->addParams($params)
                    ->setIcon('config')
                    ->displayLabel()
                    ->prepend((!empty($search)) ? ' | ' : '');
                }

                $table->addHeaderAction('print', __('Print'))
                    ->setURL('/report.php')
                    ->addParam('q', '/modules/Timetable/tt_view.php')
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('gibbonTTID', $gibbonTTID)
                    ->addParam('ttDate', $_REQUEST['ttDate'] ?? '')
                    ->setIcon('print')
                    ->setTarget('_blank')
                    ->directLink()
                    ->displayLabel()
                    ->prepend(' | ');

                if ($_GET['gibbonPersonID'] == $session->get('gibbonPersonID')) {
                    $table->addHeaderAction('export', __('Export'))
                        ->modalWindow()
                        ->setURL('/modules/Timetable/tt_manage_subscription.php')
                        ->addParam('gibbonPersonID', $_GET['gibbonPersonID'])
                        ->setIcon('download')
                        ->displayLabel()
                        ->prepend(' | ');
                }


            $table->addColumn('name', __('Name'))->format(Format::using('name', ['title', 'preferredName', 'surname', 'type', 'false']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('formGroup', __('Form Group'));

            echo $table->render([$row]);

            $ttDate = null;
            if (!empty($_REQUEST['ttDate'])) {
                $ttDate = Format::timestamp(Format::dateConvert($_REQUEST['ttDate']));
            }

            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_view.php', "&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search");
            if ($tt != false) {
                echo $tt;
            } else {
                echo $page->getBlankSlate();
            }

            //Set sidebar
            $session->set('sidebarExtra', Format::userPhoto($row['image_240'], 240));
        }
    }
}
