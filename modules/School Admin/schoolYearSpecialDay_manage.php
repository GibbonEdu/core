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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Special Days').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

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
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
        echo '</div>';

        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no terms in the specied year.');
            echo '</div>';
        } else {
            while ($row = $result->fetch()) {
                echo '<h3>';
                echo $row['name'];
                echo '</h3>';
                $firstDayStamp = dateConvertToTimestamp($row['firstDay']);
                $lastDayStamp = dateConvertToTimestamp($row['lastDay']);

                //Count back to first Monday before first day
                $startDayStamp = $firstDayStamp;
                while (date('D', $startDayStamp) != 'Mon') {
                    $startDayStamp = strtotime('-1 day', $startDayStamp);
                }

                //Count forward to first Sunday after last day
                $endDayStamp = $lastDayStamp;
                while (date('D', $endDayStamp) != 'Sun') {
                    $endDayStamp = strtotime('+1 day', $endDayStamp);
                }

                //Get the special days
                try {
                    $dataSpecial = array('firstDay' => $row['firstDay'], 'lastDay' => $row['lastDay']);
                    $sqlSpecial = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date BETWEEN :firstDay AND :lastDay ORDER BY date';
                    $resultSpecial = $connection2->prepare($sqlSpecial);
                    $resultSpecial->execute($dataSpecial);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultSpecial->rowCount() > 0) {
                    $rowSpecial = $resultSpecial->fetch();
                }

                //Check which days are school days
                $days = array();
                $days['Mon'] = 'Y';
                $days['Tue'] = 'Y';
                $days['Wed'] = 'Y';
                $days['Thu'] = 'Y';
                $days['Fri'] = 'Y';
                $days['Sat'] = 'Y';
                $days['Sun'] = 'Y';
                try {
                    $dataDays = array();
                    $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'";
                    $resultDays = $connection2->prepare($sqlDays);
                    $resultDays->execute($dataDays);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowDays = $resultDays->fetch()) {
                    if ($rowDays['nameShort'] == 'Mon') {
                        $days['Mon'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Tue') {
                        $days['Tue'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Wed') {
                        $days['Wed'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Thu') {
                        $days['Thu'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Fri') {
                        $days['Fri'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Sat') {
                        $days['Sat'] = 'N';
                    } elseif ($rowDays['nameShort'] == 'Sun') {
                        $days['Sun'] = 'N';
                    }
                }

                $count = 1;
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo "<th style='width: 14px'>";
                echo __($guid, 'Monday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __($guid, 'Tuesday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __($guid, 'Wednesday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __($guid, 'Thursday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __($guid, 'Friday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __($guid, 'Saturday');
                echo '</th>';
                echo "<th style='width: 15px'>";
                echo __($guid, 'Sunday');
                echo '</th>';
                echo '</tr>';

                $specialDayStamp = null;
                for ($i = $startDayStamp; $i <= $endDayStamp;$i = strtotime('+1 day', $i)) {
                    if (date('D', $i) == 'Mon') {
                        echo "<tr style='height: 60px'>";
                    }

                    if (isset($rowSpecial)) {
                        if ($rowSpecial == true) {
                            $specialDayStamp = dateConvertToTimestamp($rowSpecial['date']);
                        }
                    }

                    if ($i < $firstDayStamp or $i > $lastDayStamp or $days[date('D', $i)] == 'N') {
                        echo "<td style='background-color: #bbbbbb'>";
                        echo '</td>';

                        if ($i == $specialDayStamp) {
                            $rowSpecial = $resultSpecial->fetch();
                        }
                    } else {
                        echo "<td style='text-align: center; background-color: #eeeeee; font-size: 10px'>";
                        if ($i == $specialDayStamp) {
                            echo "<span style='color: #ff0000'>".dateConvertBack($guid, date('Y-m-d', $i)).'<br/>'.$rowSpecial['name'].'</span>';
                            echo '<br/>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage_edit.php&gibbonSchoolYearSpecialDayID='.$rowSpecial['gibbonSchoolYearSpecialDayID']."&gibbonSchoolYearTermID=".$row['gibbonSchoolYearTermID']."&gibbonSchoolYearID=$gibbonSchoolYearID'><img style='margin-top: 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage_delete.php&gibbonSchoolYearSpecialDayID='.$rowSpecial['gibbonSchoolYearSpecialDayID']."&gibbonSchoolYearID=$gibbonSchoolYearID&width=650&height=135'><img style='margin-top: 3px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                            $rowSpecial = $resultSpecial->fetch();
                        } else {
                            echo "<span style='color: #000000'>".dateConvertBack($guid, date('Y-m-d', $i)).'<br/>School Day</span>';
                            echo '<br/>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/schoolYearSpecialDay_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i.'&gibbonSchoolYearTermID='.$row['gibbonSchoolYearTermID']."&firstDay=$firstDayStamp&lastDay=$lastDayStamp'><img style='margin-top: 3px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> ";
                        }
                        echo '</td>';
                    }

                    if (date('D', $i) == 'Sun') {
                        echo '</tr>';
                    }
                    ++$count;
                }

                echo '</table>';
            }
        }
    }
}
