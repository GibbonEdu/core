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

//Gibbon system-wide includes
include './gibbon.php';

$gibbonAlarmID = $_POST['gibbonAlarmID'];
$gibbonPersonIDSounder = $_POST['gibbonPersonIDSounder'];

//Proceed!
if ($gibbonAlarmID == '') { echo "<div class='error'>";
    echo __('An error has occurred.');
    echo '</div>';
} else {
    //Check confirmation of alarm
    $output = '';

    try {
        $dataConfirm = array('gibbonAlarmID' => $gibbonAlarmID);
        $sqlConfirm = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, gibbonAlarmConfirmID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
        $resultConfirm = $connection2->prepare($sqlConfirm);
        $resultConfirm->execute($dataConfirm);
    } catch (PDOException $e) {
        //$output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultConfirm->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __('There are no records to display.');
        $output .= '</div>';
    } else {
        $output .= "<table cellspacing='0' style='width: 400px; margin: 0 auto'>";
        $output .= "<tr class='head'>";
        $output .= "<th style='color: #fff; text-align: left'>";
        $output .= __('Name').'<br/>';
        $output .= '</th>';
        $output .= "<th style='color: #fff; text-align: left'>";
        $output .= __('Confirmed');
        $output .= '</th>';
        $output .= "<th style='color: #fff; text-align: left'>";
        $output .= __('Actions');
        $output .= '</th>';
        $output .= '</tr>';

        $rowCount = 0;
        while ($rowConfirm = $resultConfirm->fetch()) {
            //COLOR ROW BY STATUS!
            $output .= "<tr id='row".$rowCount."'>";
            $output .= "<td style='color: #fff'>";
            $output .= Format::name('', $rowConfirm['preferredName'], $rowConfirm['surname'], 'Staff', true, true).'<br/>';
            $output .= '</td>';
            $output .= "<td style='color: #fff'>";
            if ($gibbonPersonIDSounder == $rowConfirm['gibbonPersonID']) {
                $output .= __('NA');
            } else {
                if ($rowConfirm['gibbonAlarmConfirmID'] != '') {
                    $output .= "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                }
            }
            $output .= '</td>';
            $output .= "<td style='color: #fff'>";
            if ($gibbonPersonIDSounder != $rowConfirm['gibbonPersonID']) {
                if ($rowConfirm['gibbonAlarmConfirmID'] == '') {
                    $output .= "<a target='_parent' href='".$_SESSION[$guid]['absoluteURL'].'/index_notification_ajax_alarmConfirmProcess.php?gibbonPersonID='.$rowConfirm['gibbonPersonID'].'&gibbonAlarmID='.$gibbonAlarmID."'><img title='".__('Confirm')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick_light.png'/></a> ";
                }
            }
            $output .= '</td>';
            $output .= '</tr>';
            ++$rowCount;
        }
        $output .= '</table>';
    }

    echo $output;
}
