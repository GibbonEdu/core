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

function getBehaviourRecord($guid, $gibbonPersonID, $connection2)
{
    $enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
    $enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

    try {
        $dataYears = array('gibbonPersonID' => $gibbonPersonID);
        $sqlYears = 'SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
        $resultYears = $connection2->prepare($sqlYears);
        $resultYears->execute($dataYears);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultYears->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<div class='linkTop'>";
        $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
        if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') == true) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=&gibbonYearGroupID=&type='>".__($guid, 'Add')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            if ($policyLink != '') {
                echo ' | ';
            }
        }
        if ($policyLink != '') {
            echo "<a href='$policyLink'>".__($guid, 'View Behaviour Policy').'</a>';
        }
        echo '</div>';

        $yearCount = 0;
        while ($rowYears = $resultYears->fetch()) {
            $class = '';
            if ($yearCount == 0) {
                $class = "class='top'";
            }
            echo "<h3 $class>";
            echo $rowYears['name'];
            echo '</h3>';

            ++$yearCount;

            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                $sql = 'SELECT gibbonBehaviour.*, title, surname, preferredName FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY timestamp DESC';
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
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo "<th style='width: 10%'>";
                echo __($guid, 'Date');
                echo '</th>';
                echo "<th style='width: 7%'>";
                echo __($guid, 'Type');
                echo '</th>';
                if ($enableDescriptors == 'Y') {
                    echo "<th style='width: 18%'>";
                    echo __($guid, 'Descriptor');
                    echo '</th>';
                }
                if ($enableLevels == 'Y') {
                    echo "<th style='width: 18%'>";
                    echo __($guid, 'Level');
                    echo '</th>';
                }
                echo "<th style='width: 17%'>";
                echo __($guid, 'Teacher');
                echo '</th>';
                echo "<th style='width: 10%'>";
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $rowNum = 'odd';
                $count = 0;
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                    echo '<td>';
                    if (substr($row['timestamp'], 0, 10) > $row['date']) {
                        echo __($guid, 'Updated:').' '.dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'<br/>';
                        echo __($guid, 'Incident:').' '.dateConvertBack($guid, $row['date']).'<br/>';
                    } else {
                        echo dateConvertBack($guid, $row['date']).'<br/>';
                    }
                    echo '</td>';
                    echo "<td style='text-align: center'>";
                    if ($row['type'] == 'Negative') {
                        echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                    } elseif ($row['type'] == 'Positive') {
                        echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                    }
                    echo '</td>';
                    if ($enableDescriptors == 'Y') {
                        echo '<td>';
                        echo trim($row['descriptor']);
                        echo '</td>';
                    }
                    if ($enableLevels == 'Y') {
                        echo '<td>';
                        echo trim($row['level']);
                        echo '</td>';
                    }
                    echo '<td>';
                    echo formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff', false, true).'</b><br/>';
                    echo '</td>';
                    echo '<td>';
                    if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php', 'Manage Behaviour Records_all') and $row['gibbonSchoolYearID'] == $_SESSION[$guid]['gibbonSchoolYearID']) {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID='.$row['gibbonBehaviourID'].'&gibbonPersonID='.$row['gibbonPersonID']."&gibbonRollGroupID=&gibbonYearGroupID=&type='><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    } elseif (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php', 'Manage Behaviour Records_my') and $row['gibbonSchoolYearID'] == $_SESSION[$guid]['gibbonSchoolYearID']  and $row['gibbonPersonIDCreator'] == $_SESSION[$guid]['gibbonPersonID']) {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID='.$row['gibbonBehaviourID'].'&gibbonPersonID='.$row['gibbonPersonID']."&gibbonRollGroupID=&gibbonYearGroupID=&type='><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    }
                    echo "<script type='text/javascript'>";
                    echo '$(document).ready(function(){';
                    echo "\$(\".comment-$count-$yearCount\").hide();";
                    echo "\$(\".show_hide-$count-$yearCount\").fadeIn(1000);";
                    echo "\$(\".show_hide-$count-$yearCount\").click(function(){";
                    echo "\$(\".comment-$count-$yearCount\").fadeToggle(1000);";
                    echo '});';
                    echo '});';
                    echo '</script>';
                    if ($row['comment'] != '' or $row['followup'] != '') {
                        echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count-$yearCount' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                    }
                    echo '</td>';
                    echo '</tr>';
                    if ($row['comment'] != '' or $row['followup'] != '') {
                        if ($row['type'] == 'Positive') {
                            $bg = 'background-color: #D4F6DC;';
                        } else {
                            $bg = 'background-color: #F6CECB;';
                        }
                        echo "<tr class='comment-$count-$yearCount' id='comment-$count-$yearCount'>";
                        echo "<td style='$bg' colspan=6>";
                        if ($row['comment'] != '') {
                            echo '<b>'.__($guid, 'Incident').'</b><br/>';
                            echo nl2brr($row['comment']).'<br/><br/>';
                        }
                        if ($row['followup'] != '') {
                            echo '<b>'.__($guid, 'Follow Up').'</b><br/>';
                            echo nl2brr($row['followup']).'<br/><br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                echo '</table>';
            }
        }
    }
}
