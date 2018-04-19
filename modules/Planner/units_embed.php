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

include '../../functions.php';
include '../../config.php';

//Module includes
include './moduleFunctions.php';

//Get system settings
getSystemSettings($guid, $connection2);

//Check if courseschool year specified
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonCourseID = $_GET['gibbonCourseID'];
$gibbonUnitID = $_GET['gibbonUnitID'];
$themeName = $_GET['themeName'];

//Grab theme, CSS and JS
echo '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>';
$_SESSION[$guid]['gibbonThemeName'] = $themeName;
if ($themeName == '') { echo "<link rel='stylesheet' type='text/css' href='".$_SESSION[$guid]['absoluteURL']."/themes/Default/css/main.css' />";
} else {
    echo "<link rel='stylesheet' type='text/css' href='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$themeName."/css/main.css' />";
}
echo '<style type="text/css">';
    echo 'body {';
        echo 'background: none; width: 600px';
    echo '}';
    echo 'td p {';
        echo 'margin-bottom: 0px';
    echo '}';
echo '</style>';
echo '<script type="text/javascript" src='.$_SESSION[$guid]['absoluteURL'].'/lib/jquery/jquery.js"></script>';
echo '<script type="text/javascript" src="'.$_SESSION[$guid]['absoluteURL'].'/lib/jquery/jquery.js"></script>';
echo '<script type="text/javascript" src='.$_SESSION[$guid]['absoluteURL'].'/lib/jquery-ui/js/jquery-ui.min.js"></script>';

if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') { echo "<div class='error'>";
    echo __($guid, 'You have not specified one or more required parameters.');
    echo '</div>';
} else {
    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        echo "<div class='error'>";
        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
        echo '</div>';
    } else {
        $row = $result->fetch();
        $yearName = $row['name'];
        $gibbonDepartmentID = $row['gibbonDepartmentID'];

        //Check if unit specified
        if ($gibbonUnitID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            if ($gibbonUnitID == '') {
                echo "<div class='error'>";
                echo __($guid, 'You have not specified one or more required parameters.');
                echo '</div>';
            } else {
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = "SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID AND embeddable='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The specified record cannot be found.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $row = $result->fetch();

                    if ($_GET['title'] == 'true') {
                        echo '<h1>'.$row['name'].'</h1>';
                    }

                    if ($row['details'] != '') {
                        echo '<h3>Unit Overview</h3>';
                        echo '<p>';
                        echo $row['details'];
                        echo '</p>';
                    }

                    echo '<h3>Unit Smart Blocks</h3>';
                    try {
                        $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                        $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                        $resultBlocks = $connection2->prepare($sqlBlocks);
                        $resultBlocks->execute($dataBlocks);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    $i = 1;
                    if ($resultBlocks->rowCount() < 1) {
                        echo "<div class='error'>There are no smart blocks in this unit.</div>";
                    } else {
                        echo "<p>Smart blocks are <a target='_parent' href='https://gibbonedu.org'>Gibbon's</a> unique method for organising the content within a unit. Each block represents an element of a lesson, perhaps an activity, a discussion or even an outcome. Here you can simply view the blocks, but if your school runs Gibbon you can use the blocks to create lessons plans, and use drag and drop to quickly move content between lessons.</p>";
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            makeBlock($guid, $connection2, $i, 'embed', $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], 'N', $rowBlocks['gibbonUnitBlockID'], '', $rowBlocks['teachersNotes']);
                            ++$i;
                        }
                    }

                    //Spit out outcomes
                    try {
                        $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                        $sqlBlocks = "SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
                        $resultBlocks = $connection2->prepare($sqlBlocks);
                        $resultBlocks->execute($dataBlocks);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultBlocks->rowCount() > 0) {
                        echo '<h3>Outcomes</h3>';
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Year Groups');
                        echo '</th>';
                        echo '<th>';
                        echo 'Description';
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo '<b>'.$rowBlocks['nameShort'].'</b><br/>';
                            echo "<span style='font-size: 75%; font-style: italic'>".$rowBlocks['name'].'</span>';
                            echo '</td>';
                            echo '<td>';
                            echo getYearGroupsFromIDList($guid, $connection2, $rowBlocks['gibbonYearGroupIDList']);
                            echo '</td>';
                            echo '<td colspan=5>';
                            echo $rowBlocks['content'];
                            echo '</td>';
                            echo '</tr>';
                            ++$count;
                        }
                        echo '</table>';
                    }

                    echo '<h3>Source</h3>';
                    if ($_SESSION[$guid]['webLink'] == '') {
                        echo "<p>This unit was built with, and is powered by, <a target='_parent' href='https://gibbonedu.org'>Gibbon</a> (the open, flexible and free school platform) at ".$_SESSION[$guid]['organisationName'].'</p>';
                    } else {
                        echo "<p>This unit was built with, and is powered by, <a target='_parent' href='https://gibbonedu.org'>Gibbon</a> (the open, flexible and free school platform) at <a target='_parent' href='".$_SESSION[$guid]['webLink']."'>".$_SESSION[$guid]['organisationName'].'</a></p>';
                    }
                }
            }
        }
    }
}
