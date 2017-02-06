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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Tie Days to Dates').'</div>';
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
        if ($result->rowcount() != 1) {
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
        echo '<p>';
        echo __($guid, 'To multi-add a single timetable day to multiple dates, use the checkboxes in the relevant dates, and then press the Submit button at the bottom of the page.');
        echo '</p>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
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
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/ttDates_addMultiProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID'>";

            while ($row = $result->fetch()) {
                echo '<h3>';
                echo $row['name'];
                echo '</h3>';
                list($firstDayYear, $firstDayMonth, $firstDayDay) = explode('-', $row['firstDay']);
                $firstDayStamp = mktime(0, 0, 0, $firstDayMonth, $firstDayDay, $firstDayYear);
                list($lastDayYear, $lastDayMonth, $lastDayDay) = explode('-', $row['lastDay']);
                $lastDayStamp = mktime(0, 0, 0, $lastDayMonth, $lastDayDay, $lastDayYear);

                //Count back to first Monday before first day
                $startDayStamp = $firstDayStamp;
                while (date('D', $startDayStamp) != 'Mon') {
                    $startDayStamp = $startDayStamp - 86400;
                }

                //Count forward to first Sunday after last day
                $endDayStamp = $lastDayStamp;
                while (date('D', $endDayStamp) != 'Sun') {
                    $endDayStamp = $endDayStamp + 86400;
                }

                //Get the special days
                try {
                    $dataSpecial = array('gibbonSchoolYearTermID' => $row['gibbonSchoolYearTermID']);
                    $sqlSpecial = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID ORDER BY date';
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
                for ($i = 1; $i < 8; ++$i) {
                    echo "<th style='width: 14px; text-align: center'>";
                    $dowLong = date('l', strtotime("Sunday +$i days"));
                    $dowShort = date('D', strtotime("Sunday +$i days"));
                    echo __($guid, $dowLong).'<br/>';
                    echo '<script type="text/javascript">';
                    echo '$(function () {';
                    echo "$('.checkall".$dowShort.$row['nameShort']."').click(function () {";
                    echo "$('.".$dowShort.$row['nameShort']."').find(':checkbox').attr('checked', this.checked);";
                    echo '});';
                    echo '});';
                    echo '</script>';
                    echo "<input type='checkbox' class='checkall".$dowShort.$row['nameShort']."'><br/>";
                    echo '</th>';
                }
                echo '</tr>';

                $specialDayStamp = null;
                for ($i = $startDayStamp;$i <= $endDayStamp;$i = $i + 86400) {
                    if (date('D', $i) == 'Mon') {
                        echo "<tr style='height: 60px'>";
                    }

                    if (isset($rowSpecial)) {
                        if ($rowSpecial == true) {
                            list($specialDayYear, $specialDayMonth, $specialDayDay) = explode('-', $rowSpecial['date']);
                            $specialDayStamp = mktime(0, 0, 0, $specialDayMonth, $specialDayDay, $specialDayYear);
                        }
                    }

                    if ($i < $firstDayStamp or $i > $lastDayStamp or $days[date('D', $i)] == 'N') {
                        echo "<td style='background-color: #bbbbbb'>";
                        echo '</td>';

                        if ($i == $specialDayStamp) {
                            $rowSpecial = $resultSpecial->fetch();
                        }
                    } else {
                        if ($i == $specialDayStamp and $rowSpecial['type'] == 'School Closure') {
                            echo "<td style='vertical-align: top; text-align: center; background-color: #bbbbbb; font-size: 10px'>";
                            echo "<span style='color: #fff'>".date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>'.$rowSpecial['name'].'</span>';
                            echo '<br/>';
                            $rowSpecial = $resultSpecial->fetch();
                            echo '</td>';
                        } else {
                            echo "<td style='vertical-align: top; text-align: center; background-color: #eeeeee; font-size: 10px'>";
                            if ($i == $specialDayStamp and $rowSpecial['type'] == 'Timing Change') {
                                echo "<span style='color: #000000'>".date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i)."<br/></span><span style='color: #f00'>".__($guid, 'Timing Change').'</span>';
                            } else {
                                echo "<span style='color: #000000'>".date($_SESSION[$guid]['i18n']['dateFormatPHP'], $i).'<br/>'.__($guid, 'School Day').'</span>';
                            }
                            echo '<br/>';
                            echo "<fieldset class='".date('D', $i).$row['nameShort']."' style='border: none'>";
                            echo "<input name='dates[]' value='$i' type='checkbox'/>";
                            echo '</fieldset>';
                            echo '<br/>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i."'><img style='margin-top: 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a><br/>";

                            try {
                                $dataDay = array('date' => date('Y-m-d', $i));
                                $sqlDay = 'SELECT gibbonTTDay.nameShort AS dayName, gibbonTT.nameShort AS ttName FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE date=:date';
                                $resultDay = $connection2->prepare($sqlDay);
                                $resultDay->execute($dataDay);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowDay = $resultDay->fetch()) {
                                echo '<b>'.$rowDay['ttName'].' '.$rowDay['dayName'].'</b><br/>';
                            }
                            echo '</td>';
                        }
                    }

                    if (date('D', $i) == 'Sun') {
                        echo '</tr>';
                    }
                    ++$count;
                }

                echo '</table>';
            }
        }

        echo '<h3>';
        echo __($guid, 'Multi Add');
        echo '</h3>';

        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
        echo '<tr>';
        echo '<td> ';
        echo '<b>'.__($guid, 'Day').'</b><br/>';
        echo '</td>';
        echo '<td class="right">';
        echo '<select style="width: 202px" name="gibbonTTDayID">';

		//Check which timetables are not already linked to this date
		try {
			$dataCheck = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
			$sqlCheck = 'SELECT * FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
			$resultCheck = $connection2->prepare($sqlCheck);
			$resultCheck->execute($dataCheck);
		} catch (PDOException $e) {
		}

        $tt = array();
        $count = 0;
        while ($rowCheck = $resultCheck->fetch()) {
            try {
                $dataSelect = array('gibbonTTID' => $rowCheck['gibbonTTID']);
                $sqlSelect = 'SELECT gibbonTTDay.*, gibbonTT.name AS ttName FROM gibbonTTDay JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE gibbonTT.gibbonTTID=:gibbonTTID ORDER BY gibbonTTDay.name';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                echo "<option value='".$rowSelect['gibbonTTDayID']."'>".$rowSelect['ttName'].': '.$rowSelect['nameShort'].'</option>';
            }
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td colspan=2 class="right">';
        echo '<input type="hidden" name="q" value="/modules/'.$_SESSION[$guid]['module'].'/ttDates.php">';
        echo '<input type="submit" value="'.__($guid, 'Submit').'">';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
    }
}
