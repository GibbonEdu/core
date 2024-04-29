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


use Gibbon\Http\Url;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

        $page->breadcrumbs
            ->add(__('View Behaviour Records'), 'behaviour_manage.php')
            ->add(__('View Student Record'));

        $search = $_GET['search'] ?? '';
        if (!empty($search)) {
            $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Behaviour', 'behaviour_view.php')->withQueryParam('search', $search));
        }
        try {
            if ($highestAction == 'View Behaviour Records_all') {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)  JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
            } else if ($highestAction == 'View Behaviour Records_myChildren') {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonFamilyChild ON (gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND childDataAccess='Y') WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID2 ORDER BY surname, preferredName";
            } else if ($highestAction == 'View Behaviour Records_my') {
                $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];               
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup
                 FROM gibbonBehaviour 
                 JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID)
                 JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                 JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                 JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                 WHERE gibbonBehaviour.gibbonPersonIDCreator=:gibbonPersonIDCreator AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today) AND gibbonPerson.gibbonPersonID=:gibbonPersonID
                GROUP BY gibbonPerson.gibbonPersonID, yearGroup, formGroup
                ORDER BY surname, preferredName";
            } else {
                return;
            }

            $result = $connection2->prepare($sql);
            $result->execute($data);

        } catch (PDOException $e) {
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $row = $result->fetch();

            // DISPLAY STUDENT DATA
            $table = DataTable::createDetails('personal');
            $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', 'true']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('formGroup', __('Form Group'));

            echo $table->render([$row]);

            if ($highestAction == 'View Behaviour Records_my') {
                echo getBehaviourRecord($container, $gibbonPersonID, $session->get('gibbonPersonID'));
            } else {
                echo getBehaviourRecord($container, $gibbonPersonID);
            }
        }
    }
}
