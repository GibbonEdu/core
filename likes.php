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

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Likes').'</div>';
echo '</div>';
echo '<p>';
echo __($guid, 'This page shows you a break down of all your likes in the current year, and they have been earned.');
echo '</p>';

//Count planner likes
$resultLike = countLikesByRecipient($connection2, $_SESSION[$guid]['gibbonPersonID'], 'result', $_SESSION[$guid]['gibbonSchoolYearID']);
if ($resultLike == false) {
    echo "<div class='error'>".__($guid, 'An error has occurred.').'</div>';
} else {
    if ($resultLike->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 90px'>";
        echo __($guid, 'Photo');
        echo '</th>';
        echo "<th style='width: 180px'>";
        echo __($guid, 'Giver').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Role').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Title').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Comment').'</span>';
        echo '</th>';
        echo "<th style='width: 70px'>";
        echo __($guid, 'Date');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $resultLike->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

			//COLOR ROW BY STATUS!
			echo "<tr class=$rowNum>";
            echo '<td>';
            echo getUserPhoto($guid, $row['image_240'], 75);
            echo '</td>';
            echo '<td>';
            $roleCategory = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
            if ($roleCategory == 'Student' and isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php')) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], $roleCategory, false).'</a><br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, $roleCategory).'</i>';
            } else {
                echo formatName('', $row['preferredName'], $row['surname'], $roleCategory, false).'<br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, $roleCategory).'</i>';
            }
            echo '</td>';
            echo '<td>';
            echo __($guid, $row['title']).'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['comment'].'</span>';
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, substr($row['timestamp'], 0, 10));
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>



