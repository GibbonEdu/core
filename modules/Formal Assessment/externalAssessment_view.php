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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction == 'View External Assessments_myChildrens') { //MY CHILDREN
            $page->breadcrumbs->add(__('View My Childrens\'s External Assessments'));

            //Test data access field for permission

                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() < 1) {
                echo $page->getBlankSlate();
            } else {
                //Get child list
                $count = 0;
                $options = array();
                while ($row = $result->fetch()) {
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => date('Y-m-d'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    while ($rowChild = $resultChild->fetch()) {
                        $options[$rowChild['gibbonPersonID']]=Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                    }
                }

                if (count($options) == 0) {
                    echo $page->getBlankSlate();
                } elseif (count($options) == 1) {
                    $gibbonPersonID = key($options);
                } else {
                    echo '<h2>';
                    echo 'Choose Student';
                    echo '</h2>';

                    $gibbonPersonID = (isset($_GET['search']))? $_GET['search'] : null;

                    $form = Form::create("filter", $session->get('absoluteURL')."/index.php", "get");
                    $form->setClass('noIntBorder fullWidth standardForm');

                    $form->addHiddenValue('q', '/modules/Formal Assessment/externalAssessment_view.php');
                    $form->addHiddenValue('address', $session->get('address'));

                    $row = $form->addRow();
                        $row->addLabel('search', __('Student'));
                        $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

                    $row = $form->addRow();
                        $row->addSearchSubmit($session);

                    echo $form->getOutput();
                }

                $settingGateway = $container->get(SettingGateway::class);
                $showParentAttainmentWarning = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning');
                $showParentEffortWarning = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning');

                if ($gibbonPersonID != '' and count($options) > 0) {
                    //Confirm access to this student

                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    if ($resultChild->rowCount() < 1) {
                        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                    } else {
                        $rowChild = $resultChild->fetch();
                        externalAssessmentDetails($guid, $gibbonPersonID, $connection2, null, false);
                    }
                }
            }
        } else { //My External Assessments
            $page->breadcrumbs->add(__('View My External Assessments'));

            echo '<h3>';
            echo __('External Assessments');
            echo '</h3>';

            echo externalAssessmentDetails($guid, $session->get('gibbonPersonID'), $connection2, null, false);
        }
    }
}
?>
