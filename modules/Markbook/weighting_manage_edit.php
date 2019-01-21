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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {

        if (getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting') != 'Y') {
            //Acess denied
            echo "<div class='error'>";
            echo __('Your request failed because you do not have access to this action.');
            echo '</div>';
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Get class variable
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __('Edit Markbook Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            return;
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Manage Weightings_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Edit Markbook Weighting');
                echo '</h1>';
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $gibbonMarkbookWeightID = (isset($_GET['gibbonMarkbookWeightID']))? $_GET['gibbonMarkbookWeightID'] : null;
                try {
                    $data2 = array('gibbonMarkbookWeightID' => $gibbonMarkbookWeightID);
                    $sql2 = 'SELECT * FROM gibbonMarkbookWeight WHERE gibbonMarkbookWeightID=:gibbonMarkbookWeightID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo '<h1>';
                    echo __('Edit Markbook Weighting');
                    echo '</h1>';
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $course = $result->fetch();
                    $values = $result2->fetch();

                    $page->breadcrumbs
                        ->add(
                            __('Manage {courseClass} Weightings', [
                                'courseClass' => Format::courseClassName($course['course'], $course['class']),
                            ]),
                            'weighting_manage.php',
                            ['gibbonCourseClassID' => $gibbonCourseClassID]
                        )
                        ->add(__('Edit Weighting'));

                    $form = Form::create('manageWeighting', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/weighting_manage_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=$gibbonMarkbookWeightID");
                
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                    $form->addHiddenValue('type', $values['type']);

                    $form->addRow()->addHeading(__('Add Markbook Weighting'));

                    $row = $form->addRow();
                        $row->addLabel('type', __('Type'));
                        $row->addTextField('type')->readonly();

                    $row = $form->addRow();
                        $row->addLabel('description', __('Description'));
                        $row->addTextField('description')->isRequired()->maxLength(50);

                    $row = $form->addRow();
                        $row->addLabel('weighting', __('Weighting'))->description(__('Percent: 0 to 100'));
                        $row->addNumber('weighting')->isRequired()->maxLength(6)->minimum(0)->maximum(100)->onlyInteger(false);

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

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }
    }

    // Print the sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'weighting_manage.php');
}
