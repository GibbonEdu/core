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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {

        if (getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting') != 'Y') {
            //Acess denied
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because you do not have access to this action.');
            echo '</div>';
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __($guid, 'Add Markbook Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            return;
        } else {
            //Check existence of and access to this class.
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
                echo __($guid, 'Add Markbook Weighting');
                echo '</h1>';
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Add').' '.$row['course'].'.'.$row['class'].' '.__($guid, ' Weighting').'</div>';
                echo '</div>';

                // Show add weighting form
                $form = Form::create('manageWeighting', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/weighting_manage_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID");
                
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $form->addRow()->addHeading(__('Add Markbook Weighting'));

                $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
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
                    $row->addSelect('type')->fromArray(array_values($types))->isRequired()->placeholder();

                $row = $form->addRow();
                    $row->addLabel('description', __('Description'));
                    $row->addTextField('description')->isRequired()->maxLength(50);

                $row = $form->addRow();
                    $row->addLabel('weighting', __('Weighting'))->description(__('Percent: 0 to 100'));
                    $row->addNumber('weighting')->isRequired()->maxLength(6)->minimum(0)->maximum(100);

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
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'weighting_manage.php');
}
