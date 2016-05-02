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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_my_full.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        //Get class variable
        $gibbonActivityID = $_GET['gibbonActivityID'];
        if ($gibbonActivityID == '') {
            echo "<div class='warning'>";
            echo __($guid, 'Your request failed because your inputs were invalid.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            $today = date('Y-m-d');

            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
                $sql = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonActivityID=:gibbonActivityID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='warning'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                //Should we show date as term or date?
                $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

                echo '<h1>';
                echo $row['name'].'<br/>';
                $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
                if ($options != '') {
                    echo "<div style='padding-top: 5px; font-size: 65%; font-style: italic'>";
                    echo trim($row['type']);
                    echo '</div>';
                }
                echo '</h1>';

                echo "<table class='blank' cellspacing='0' style='width: 550px; float: left;'>";
                echo '<tr>';
                if ($dateType != 'Date') {
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Terms').'</span><br/>';
                    $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                    $termList = '';
                    for ($i = 0; $i < count($terms); $i = $i + 2) {
                        if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                            $termList .= $terms[($i + 1)].', ';
                        }
                    }
                    if ($termList == '') {
                        echo '<i>'.__($guid, 'NA').'</i>';
                    } else {
                        echo substr($termList, 0, -2);
                    }
                    echo '</td>';
                } else {
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Start Date').'</span><br/>';
                    echo dateConvertBack($guid, $row['programStart']);
                    echo '</td>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'End Date').'</span><br/>';
                    echo dateConvertBack($guid, $row['programEnd']);
                    echo '</td>';
                }
                echo "<td style='width: 33%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Groups').'</span><br/>';
                echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Payment').'</span><br/>';
                if ($row['payment'] == 0) {
                    echo '<i>'.__($guid, 'None').'</i>';
                } else {
                    if (substr($_SESSION[$guid]['currency'], 4) != '') {
                        echo substr($_SESSION[$guid]['currency'], 4);
                    }
                    echo $row['payment'];
                }
                echo '</td>';
                echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Maximum Participants').'</span><br/>';
                echo $row['maxParticipants'];
                echo '</td>';
                echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Staff').'</span><br/>';
                try {
                    $dataStaff = array('gibbonActivityID' => $row['gibbonActivityID']);
                    $sqlStaff = "SELECT title, preferredName, surname, role FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                    $resultStaff = $connection2->prepare($sqlStaff);
                    $resultStaff->execute($dataStaff);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultStaff->rowCount() < 1) {
                    echo '<i>'.__($guid, 'None').'</i>';
                } else {
                    echo "<ul style='margin-left: 15px'>";
                    while ($rowStaff = $resultStaff->fetch()) {
                        echo '<li>'.formatName($rowStaff['title'], $rowStaff['preferredName'], $rowStaff['surname'], 'Staff').'</li>';
                    }
                    echo '</ul>';
                }
                echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo "<td style='padding-top: 15px; width: 33%; vertical-align: top' colspan=3>";
                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Provider').'</span><br/>';
                echo '<i>';
                if ($row['provider'] == 'School') {
                    echo $_SESSION[$guid]['organisationNameShort'];
                } else {
                    echo __($guid, 'External');
                };
                echo '</i>';
                echo '</td>';
                echo '</tr>';
                if ($row['description'] != '') {
                    echo '<tr>';
                    echo "<td style='text-align: justify; padding-top: 15px; width: 33%; vertical-align: top' colspan=3>";
                    echo '<h2>'.__($guid, 'Description').'</h2>';
                    echo $row['description'];
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                //Participants & Attendance
                echo "<div style='width:400px; float: right; font-size: 115%; padding-top: 6px'>";
                echo "<h3 style='padding-top: 0px; margin-top: 5px'>".__($guid, 'Time Slots').'</h3>';

                try {
                    $dataSlots = array('gibbonActivityID' => $row['gibbonActivityID']);
                    $sqlSlots = 'SELECT * FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';
                    $resultSlots = $connection2->prepare($sqlSlots);
                    $resultSlots->execute($dataSlots);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $count = 0;
                while ($rowSlots = $resultSlots->fetch()) {
                    echo '<h4>'.__($guid, $rowSlots['name']).'</h4>';
                    echo '<p>';
                    echo '<i>'.__($guid, 'Time').'</i>: '.substr($rowSlots['timeStart'], 0, 5).' - '.substr($rowSlots['timeEnd'], 0, 5).'<br/>';
                    if ($rowSlots['gibbonSpaceID'] != '') {
                        try {
                            $dataSpace = array('gibbonSpaceID' => $rowSlots['gibbonSpaceID']);
                            $sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
                            $resultSpace = $connection2->prepare($sqlSpace);
                            $resultSpace->execute($dataSpace);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultSpace->rowCount() > 0) {
                            $rowSpace = $resultSpace->fetch();
                            echo '<i>'.__($guid, 'Location').'</i>: '.$rowSpace['name'];
                        }
                    } else {
                        echo '<i>'.__($guid, 'Location').'</i>: '.$rowSlots['locationExternal'];
                    }
                    echo '</p>';

                    ++$count;
                }
                if ($count == 0) {
                    echo '<i>'.__($guid, 'None').'</i>';
                }

                $role = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                if ($role == 'Staff') {
                    echo '<h3>'.__($guid, 'Participants').'</h3>';

                    try {
                        $dataStudents = array('gibbonActivityID' => $row['gibbonActivityID']);
                        $sqlStudents = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Accepted' ORDER BY surname, preferredName";
                        $resultStudents = $connection2->prepare($sqlStudents);
                        $resultStudents->execute($dataStudents);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultStudents->rowCount() < 1) {
                        echo '<i>'.__($guid, 'None').'</i>';
                    } else {
                        echo "<ul style='margin-left: 15px'>";
                        while ($rowStudent = $resultStudents->fetch()) {
                            echo '<li>'.formatName('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student').'</li>';
                        }
                        echo '</ul>';
                    }

                    try {
                        $dataStudents = array('gibbonActivityID' => $row['gibbonActivityID']);
                        $sqlStudents = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Waiting List' ORDER BY timestamp";
                        $resultStudents = $connection2->prepare($sqlStudents);
                        $resultStudents->execute($dataStudents);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultStudents->rowCount() > 0) {
                        echo '<h3>'.__($guid, 'Waiting List').'</h3>';
                        echo "<ol style='margin-left: 15px'>";
                        while ($rowStudent = $resultStudents->fetch()) {
                            echo '<li>'.formatName('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student').'</li>';
                        }
                        echo '</ol>';
                    }
                }
                echo '</div>';
            }
        }
    }
}
