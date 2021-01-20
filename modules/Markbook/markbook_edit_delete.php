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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'] ?? '';
        if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList 
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    LEFT JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID)
                    LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID)
                    WHERE ((gibbonCourseClassPerson.gibbonCourseClassPersonID IS NOT NULL AND gibbonCourseClassPerson.role='Teacher') 
                        OR (gibbonDepartmentStaff.gibbonDepartmentStaffID IS NOT NULL AND (gibbonDepartmentStaff.role = 'Coordinator' OR gibbonDepartmentStaff.role = 'Assistant Coordinator' OR gibbonDepartmentStaff.role= 'Teacher (Curriculum)'))
                        )
                    AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
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
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                
                    $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql2 = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();
                    $row2 = $result2->fetch();

                    if ($row2['groupingID'] != '' && ($row2['gibbonPersonIDCreator'] != $_SESSION[$guid]['gibbonPersonID'] && $highestAction != 'Edit Markbook_everything' && $highestAction != 'Edit Markbook_multipleClassesAcrossSchool' && $highestAction != 'Edit Markbook_multipleClassesInDepartment')) {
                        echo "<div class='error'>";
                        echo __('This column is part of a set of columns, and so cannot be individually deleted.');
                        echo '</div>';
                    } else {
                        if (isset($_GET['return'])) {
                            returnProcess($guid, $_GET['return'], null, null);
                        }

                        $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/markbook_edit_deleteProcess.php?gibbonMarkbookColumnID=$gibbonMarkbookColumnID");
                        echo $form->getOutput();
                    }
                }
            }
        }
    }
}
