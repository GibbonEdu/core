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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_privacy_student.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Privacy Choices by Student').'</div>';
    echo '</div>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, privacy, surname, preferredName, nameShort, image_240 FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND NOT privacy='' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY nameShort, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $privacy = getSettingByScope($connection2, 'User Admin', 'privacy');
    $privacyOptions = explode(',', getSettingByScope($connection2, 'User Admin', 'privacyOptions'));

    if (count($privacyOptions) < 1 or $privacy == 'N') {
        echo "<div class='error'>";
        echo __($guid, 'There are no privacy options in place.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th rowspan=2>';
        echo __($guid, 'Count');
        echo '</th>';
        echo '<th rowspan=2>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th rowspan=2 style=\'text-align: center\'>';
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th colspan='.count($privacyOptions).'>';
        echo __($guid, 'Privacy');
        echo '</th>';
        echo '</tr>';

        echo "<tr class='head'>";
        foreach ($privacyOptions as $option) {
            echo '<th>';
            echo $option;
            echo '</th>';
        }
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
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
            echo $count;
            echo '</td>';
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td style=\'text-align: center\'>';
            echo getUserPhoto($guid, $row['image_240'], 75).'<br/>';
            echo '<a href=\''.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID'].'\'>'.formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
            echo '</td>';
            $studentPrivacyOptions = explode(',', $row['privacy']);
            foreach ($privacyOptions as $option) {
                echo '<td>';
                foreach ($studentPrivacyOptions as $studentOption) {
                    if (trim($studentOption) == trim($option)) {
                        echo "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> " . __($guid, 'Required');
                    }
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=3>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
