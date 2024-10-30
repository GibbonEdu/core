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

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_add.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {

        $settingGateway = $container->get(SettingGateway::class);

        if ($settingGateway->getSettingByScope('Markbook', 'enableColumnWeighting') != 'Y') {
            //Acess denied
            $page->addError(__('Your request failed because you do not have access to this action.'));
        }

        //Get class variable
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __('Add Markbook Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            return;
        } else {
            //Check existence of and access to this class.
            try {
                if ($highestAction == 'Manage Weightings_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Add Markbook Weighting');
                echo '</h1>';
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $row = $result->fetch();

                $page->breadcrumbs
                    ->add(
                        __('Manage {courseClass} Weightings', [
                            'courseClass' => Format::courseClassName($row['course'], $row['class']),
                        ]),
                        'weighting_manage.php',
                        ['gibbonCourseClassID' => $gibbonCourseClassID]
                    )
                    ->add(__('Add Weighting'));
                // Show add weighting form
                $form = Form::create('manageWeighting', $session->get('absoluteURL').'/modules/'.$session->get('module')."/weighting_manage_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID");

                $form->addHiddenValue('address', $session->get('address'));

                $form->addRow()->addHeading('Add Markbook Weighting', __('Add Markbook Weighting'));

                $types = $settingGateway->getSettingByScope('Markbook', 'markbookType');
                $types = !empty($types)? explode(',', $types) : array();

                // Reduce the available types by the array_diff of the used types
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT type FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID GROUP BY type";
                $result = $pdo->executeQuery($data, $sql);
                $usedTypes = ($result->rowCount() > 0)? $result->fetchAll(PDO::FETCH_COLUMN, 0) : array();

                if (!empty($usedTypes)) {
                    $types = array_diff($types, $usedTypes);
                }

                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray(array_values($types))->required()->placeholder();

                $row = $form->addRow();
                    $row->addLabel('description', __('Description'));
                    $row->addTextField('description')->required()->maxLength(50);

                $row = $form->addRow();
                    $row->addLabel('weighting', __('Weighting'))->description(__('Percent: 0 to 100'));
                    $row->addNumber('weighting')->required()->maxLength(6)->minimum(0)->maximum(100);

                $percentOptions = array(
                    'term' => __('Cumulative Average'),
                    'year' => __('Final Grade'),
                );

                $row = $form->addRow();
                    $row->addLabel('calculate', __('Percent of'));
                    $row->addSelect('calculate')->fromArray($percentOptions);

                $row = $form->addRow();
                    $row->addLabel('reportable', __('Reportable?'));
                    $row->addYesNo('reportable');

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra', sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'weighting_manage.php'));
}
