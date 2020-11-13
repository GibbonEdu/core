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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_manage.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get class variable
    $gibbonCourseClassID = null;
    if (isset($_GET['gibbonCourseClassID'])) {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    } else {
        
            $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
            $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            $gibbonCourseClassID = $row['gibbonCourseClassID'];
        }
    }
    if ($gibbonCourseClassID == '') {
        echo '<h1>';
        echo 'Manage Internal Assessment';
        echo '</h1>';
        echo "<div class='warning'>";
        echo __('Use the class listing on the right to choose an Internal Assessment to edit.');
        echo '</div>';
    }
    //Check existence of and access to this class.
    else {
        
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo '<h1>';
            echo __('Manage Internal Assessment');
            echo '</h1>';
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $page->breadcrumbs->add(__('Manage').' '.$row['course'].'.'.$row['class'].' '.__('Internal Assessments'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return']);
            }

            //Add multiple columns
            echo "<div class='linkTop'>";
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/internalAssessment_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add Multiple Columns')."<img style='margin-left: 5px' title='".__('Add Multiple Columns')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new_multi.png'/></a>";
            echo '</div>';

            //Get teacher list
            $teaching = false;
            
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonCourseClassPerson.reportable FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() > 0) {
                echo '<h3>';
                echo __('Teachers');
                echo '</h3>';
                echo '<ul>';
                while ($row = $result->fetch()) {
                    if ($row['reportable'] != 'Y') continue;

                    echo '<li>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
                    if ($row['gibbonPersonID'] == $gibbon->session->get('gibbonPersonID')) {
                        $teaching = true;
                    }
                }
                echo '</ul>';
            }

            //Print mark
            echo '<h3>';
            echo __('Internal Assessment Columns');
            echo '</h3>';

            //Set pagination variable
            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }

            
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __('Name').'<br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".__('Type').'</span>';
                echo '</th>';
                echo '<th>';
                echo __('Date<br/>Complete');
                echo '</th>';
                echo '<th>';
                echo __('Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo '<b>'.$row['name'].'</b><br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>".$row['type'].'</span>';
                    echo '</td>';
                    echo '<td>';
                    if ($row['complete'] == 'Y') {
                        echo dateConvertBack($guid, $row['completeDate']);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/internalAssessment_manage_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=".$row['gibbonInternalAssessmentColumnID']."'><img title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$gibbon->session->get('module')."/internalAssessment_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=".$row['gibbonInternalAssessmentColumnID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                    echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/internalAssessment_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=".$row['gibbonInternalAssessmentColumnID']."'><img title='".__('Enter Data')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/markbook.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }
        }
    }

    //Print sidebar
    $gibbon->session->set('sidebarExtra',sidebarExtra($guid, $connection2, $gibbonCourseClassID));
}
