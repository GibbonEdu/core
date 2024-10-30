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

use Gibbon\Services\Format;

//Rubric includes
require_once __DIR__ . '/../Rubrics/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Proceed!
    $page->scripts->add('chart');

    //Check if gibbonCourseClassID and gibbonMarkbookColumnID and gibbonPersonID and gibbonRubricID specified
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
    if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '' or $gibbonPersonID == '' or $gibbonRubricID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $roleCategory = $session->get('gibbonRoleIDCurrentCategory');
        $contextDBTableGibbonRubricIDField = 'gibbonRubricID';
        if ($_GET['type'] == 'attainment') {
            $contextDBTableGibbonRubricIDField = 'gibbonRubricIDAttainment';
        } elseif ($_GET['type'] == 'effort') {
            $contextDBTableGibbonRubricIDField = 'gibbonRubricIDEffort';
        }

        try {
            if ($roleCategory == 'Staff') {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
            } elseif ($roleCategory == 'Student') {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND (role='Student' OR role='Student - Left') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
            } elseif ($roleCategory == 'Parent') {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND (role='Student' OR role='Student - Left') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {

                $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                $sql2 = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                $result2 = $connection2->prepare($sql2);
                $result2->execute($data2);

            if ($result2->rowCount() != 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

                    $data3 = array('gibbonRubricID' => $gibbonRubricID);
                    $sql3 = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    $result3 = $connection2->prepare($sql3);
                    $result3->execute($data3);

                if ($result3->rowCount() != 1) {
                    $page->addError(__('The specified record does not exist.'));
                } else {

                        $data4 = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql4 = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (role='Student' OR role='Student - Left')";
                        $result4 = $connection2->prepare($sql4);
                        $result4->execute($data4);

                    if ($result4->rowCount() != 1) {
                        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                    } else {
                        //Let's go!
                        $row = $result->fetch();
                        $row2 = $result2->fetch();
                        $row3 = $result3->fetch();
                        $row4 = $result4->fetch();

                        echo "<h2 style='margin-bottom: 10px;'>";
                        echo $row3['name'].'<br/>';
                        echo "<span style='font-size: 65%; font-style: italic'>".Format::name('', $row4['preferredName'], $row4['surname'], 'Student', true).'</span>';
                        echo '</h2>';

                        $mark = $session->get('gibbonRoleIDCurrentCategory') == 'Staff';
                        if (isset($_GET['mark']) && $_GET['mark'] == 'FALSE') {
                            $mark = false;
                        }

                        echo rubricView($guid, $connection2, $gibbonRubricID, $mark, $row4['gibbonPersonID'], 'gibbonMarkbookColumn', 'gibbonMarkbookColumnID', $gibbonMarkbookColumnID,  $contextDBTableGibbonRubricIDField, 'name', 'completeDate');
                    }
                }
            }
        }
    }
}
