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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') == false) {
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
        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        } else {
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonCourseClassID = $row['gibbonCourseClassID'];
            }
        }
        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo 'Edit Markbook';
            echo '</h1>';
            echo "<div class='warning'>";
            echo __($guid, 'Use the class listing on the right to choose a Markbook to edit.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __($guid, 'Edit Markbook');
                echo '</h1>';
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').' '.$row['course'].'.'.$row['class'].' '.__($guid, 'Markbook').'</div>';
                echo '</div>';

                //Add multiple columns
                if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                    $highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);
                    if ($highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_multipleClassesInDepartment' or $highestAction2 == 'Edit Markbook_everything') {
                        //Check highest role in any department
                        try {
                            $dataRole = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                            $sqlRole = "SELECT role FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')";
                            $resultRole = $connection2->prepare($sqlRole);
                            $resultRole->execute($dataRole);
                        } catch (PDOException $e) {
                        }
                        if ($resultRole->rowCount() >= 1 or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything') {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add Multiple Columns')."<img style='margin-left: 5px' title='".__($guid, 'Add Multiple Columns')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                            echo '</div>';
                        }
                    }
                }

                //Get teacher list
                $teaching = false;
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() > 0) {
                    echo '<h3>';
                    echo __($guid, 'Teachers');
                    echo '</h3>';
                    echo '<ul>';
                    while ($row = $result->fetch()) {
                        echo '<li>'.formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
                        if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                            $teaching = true;
                        }
                    }
                    echo '</ul>';
                }

                //Print mark
                echo '<h3>';
                echo __($guid, 'Markbook Columns');
                echo '</h3>';

                //Set pagination variable
                $page = 1;
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                }
                if ((!is_numeric($page)) or $page < 1) {
                    $page = 1;
                }

                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($teaching) {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</div>';
                }

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name/Unit');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Type');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Date<br/>Complete');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Viewable <br/>to Students');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Viewable <br/>to Parents');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Actions');
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
                        $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonHookID'], $row['gibbonCourseClassID']);
                        if (isset($unit[0])) {
                            echo $unit[0];
                        }
                        if (isset($unit[1])) {
                            echo '<br/><i>'.$unit[1].' '.__($guid, 'Unit').'</i>';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo $row['type'];
                        echo '</td>';
                        echo '<td>';
                        if ($row['complete'] == 'Y') {
                            echo dateConvertBack($guid, $row['completeDate']);
                        }
                        echo '</td>';
                        echo '<td>';
                        echo $row['viewableStudents'];
                        echo '</td>';
                        echo '<td>';
                        echo $row['viewableParents'];
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID='.$row['gibbonMarkbookColumnID']."&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_edit.php'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                        echo '</td>';
                        echo '</tr>';

                        ++$count;
                    }
                    echo '</table>';
                }
            }
        }
    }
}
