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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$publicUnits = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'publicUnits');

if (!isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') || !isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

        //Get params
        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
        $canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all';
        $showInactive = ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N';
        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';
        $view = $_GET['view'] ?? '';
        if ($view != 'grid' and $view != 'map') {
            $view = 'list';
        }
        $gibbonPersonID = ($canManage and isset($_GET['gibbonPersonID'])) ? $_GET['gibbonPersonID'] : $session->get('gibbonPersonID');

        //Get action with highest precendence
        $urlParams = compact('freeLearningUnitID', 'showInactive', 'gibbonDepartmentID', 'difficulty', 'name', 'view', 'gibbonPersonID');

        $page->breadcrumbs
             ->add(__m('Browse Units'), 'units_browse.php', $urlParams);

        $urlParams["sidebar"] = "true";
        $page->breadcrumbs->add(__m('Unit Details'), 'units_browse_details.php', $urlParams)
             ->add(__m('Add Multiple'));

        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $session->get('gibbonPersonID'), $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
                $data = $unitList[0];
                $sql = $unitList[1];
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $values = $result->fetch();

                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/units_browse_details_enrolMultipleProcess.php?freeLearningUnitID=$freeLearningUnitID&showInactive=$showInactive&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view");

                $form->addHiddenValue('address', $session->get('address'));

                $row = $form->addRow();
                    $row->addLabel('unit', __('Unit'));
                    $row->addTextField('unit')->readonly()->setValue($values['name'])->required();

                $highestAction2 = getHighestGroupedAction($guid, '/modules/Free Learning/units_manage.php', $connection2);
                if ($highestAction2 == 'Manage Units_all') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sql = "SELECT gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort, ' (', gibbonCourse.name, ')') AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name";
                } else {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = "SELECT gibbonCourseClass.gibbonCourseClassID AS value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort, ' (', gibbonCourse.name, ')') AS name FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE (role='Teacher' OR role='Assistant') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY name";
                }
                $row = $form->addRow();
                    $row->addLabel('gibbonCourseClassID', __m('Class'));
                    $row->addSelect('gibbonCourseClassID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = "SELECT CONCAT(gibbonCourseClassID, '-', gibbonPerson.gibbonPersonID) AS value, CONCAT(gibbonFormGroup.name, ' - ', surname, ', ', preferredName) AS name, gibbonCourseClassID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";

                $classList = $pdo->select($sql, $data)->fetchAll();
                $classListChained = array_combine(array_column($classList, 'value'), array_column($classList, 'gibbonCourseClassID'));
                $classListOptions = array_combine(array_column($classList, 'value'), array_column($classList, 'name'));

                $row = $form->addRow();
                    $row->addLabel('gibbonPersonIDMulti', __m('Participants'));
                    $row->addSelect('gibbonPersonIDMulti')
                        ->fromArray($classListOptions)
                        ->chainedTo('gibbonCourseClassID', $classListChained)
                        ->required()
                        ->selectMultiple();

                $statuses = [
                    'Exempt' => __m('Exempt')
                ];
                $row = $form->addRow();
                    $row->addLabel('status', __m('Status'));
                    $row->addSelect('status')->fromArray($statuses)->required();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
?>
