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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Course Enrolment by Class').'</div>';
    echo '</div>';

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
        echo '</div>';

        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            while ($row = $result->fetch()) {
                echo '<h3>';
                echo $row['nameShort'].' ('.$row['name'].')';
                echo '</h3>';

                try {
                    $dataClass = array('gibbonCourseID' => $row['gibbonCourseID']);
                    $sqlClass = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name';
                    $resultClass = $connection2->prepare($sqlClass);
                    $resultClass->execute($dataClass);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultClass->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Short Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Participants').'<br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Active').'</span>';
                    echo '</th>';
                    echo '<th>';
                    echo 'Participants<br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Expected').'</span>';
                    echo '</th>';
                    echo '<th>';
                    echo 'Participants<br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Total').'</span>';
                    echo '</th>';
                    echo "<th style='width: 55px'>";
                    echo __($guid, 'Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowClass = $resultClass->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $rowClass['name'];
                        echo '</td>';
                        echo '<td>';
                        echo $rowClass['nameShort'];
                        echo '</td>';
                        echo '<td>';
                        $total = 0;
                        $active = 0;
                        $expected = 0;
                        try {
                            $dataClasses = array('gibbonCourseClassID' => $rowClass['gibbonCourseClassID']);
                            $sqlClasses = "SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')";
                            $resultClasses = $connection2->prepare($sqlClasses);
                            $resultClasses->execute($dataClasses);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultClasses->rowCount() >= 0) {
                            $active = $resultClasses->rowCount();
                        }

                        try {
                            $dataClasses = array('gibbonCourseClassID' => $rowClass['gibbonCourseClassID']);
                            $sqlClasses = "SELECT gibbonCourseClassPerson.* FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Expected' AND gibbonCourseClassID=:gibbonCourseClassID AND (NOT role='Student - Left') AND (NOT role='Teacher - Left')";
                            $resultClasses = $connection2->prepare($sqlClasses);
                            $resultClasses->execute($dataClasses);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultClasses->rowCount() >= 0) {
                            $expected = $resultClasses->rowCount();
                        }
                        echo $active;
                        echo '</td>';
                        echo '<td>';
                        echo $expected;
                        echo '</td>';
                        echo '<td>';
                        echo '<b>'.($active + $expected).'<b/> ';
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_manage_class_edit.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID'].'&gibbonCourseID='.$row['gibbonCourseID']."&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
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
