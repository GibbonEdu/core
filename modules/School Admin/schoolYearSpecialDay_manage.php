<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Special Days'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no terms in the specified year.');
            echo '</div>';
        } else {
            while ($row = $result->fetch()) {
                echo '<h3>';
                echo $row['name'];
                echo '</h3>';
                $firstDayStamp = Format::timestamp($row['firstDay']);
                $lastDayStamp = Format::timestamp($row['lastDay']);

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
                
                    $dataSpecial = array('firstDay' => $row['firstDay'], 'lastDay' => $row['lastDay']);
                    $sqlSpecial = 'SELECT * FROM gibbonSchoolYearSpecialDay WHERE date BETWEEN :firstDay AND :lastDay ORDER BY date';
                    $resultSpecial = $connection2->prepare($sqlSpecial);
                    $resultSpecial->execute($dataSpecial);
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
                
                    $dataDays = array();
                    $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='N'";
                    $resultDays = $connection2->prepare($sqlDays);
                    $resultDays->execute($dataDays);
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
                echo "<table cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
                echo "<tr class='head'>";
                echo "<th style='width: 14px'>";
                echo __('Monday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __('Tuesday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __('Wednesday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __('Thursday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __('Friday');
                echo '</th>';
                echo "<th style='width: 14px'>";
                echo __('Saturday');
                echo '</th>';
                echo "<th style='width: 15px'>";
                echo __('Sunday');
                echo '</th>';
                echo '</tr>';

                $specialDayStamp = null;
                for ($i = $startDayStamp; $i <= $endDayStamp;$i = strtotime('+1 day', $i)) {
                    if (date('D', $i) == 'Mon') {
                        echo "<tr style='height: 60px'>";
                    }

                    if (isset($rowSpecial)) {
                        if ($rowSpecial == true) {
                            $specialDayStamp = Format::timestamp($rowSpecial['date']);
                        }
                    }

                    if ($i < $firstDayStamp or $i > $lastDayStamp or $days[date('D', $i)] == 'N') {
                        echo "<td style='background-color: #bbbbbb'>";
                        echo '</td>';

                        if ($i == $specialDayStamp) {
                            $rowSpecial = $resultSpecial->fetch();
                        }
                    } else {
                        if ($i == $specialDayStamp) {
                            $class = $rowSpecial['type'] == 'Off Timetable' ? 'bg-blue-200 border-blue-700 text-blue-700' : 'bg-red-200 border-red-600 text-red-600';

                            echo "<td class='{$class}' style='text-align: center; font-size: 10px'>";
                            echo Format::date(date('Y-m-d', $i)).'<br/>'.$rowSpecial['name'].'<br/>';
                            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/schoolYearSpecialDay_manage_edit.php&gibbonSchoolYearSpecialDayID='.$rowSpecial['gibbonSchoolYearSpecialDayID']."&gibbonSchoolYearTermID=".$row['gibbonSchoolYearTermID']."&gibbonSchoolYearID=$gibbonSchoolYearID'><img style='margin-top: 3px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                            echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module').'/schoolYearSpecialDay_manage_delete.php&gibbonSchoolYearSpecialDayID='.$rowSpecial['gibbonSchoolYearSpecialDayID']."&gibbonSchoolYearID=$gibbonSchoolYearID&width=650&height=135'><img style='margin-top: 3px' title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                            $rowSpecial = $resultSpecial->fetch();
                            echo '</td>';
                        } else {
                            $class = date('Y-m-d', $i) == date('Y-m-d') ? 'bg-yellow-200' : 'bg-gray-200';
                            echo "<td class='{$class}' style='text-align: center;  font-size: 10px'>";

                            echo "<span style='color: #000000'>".Format::date(date('Y-m-d', $i)).'<br/>'.__('School Day').'</span>';
                            echo '<br/>';
                            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/schoolYearSpecialDay_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=".$i.'&gibbonSchoolYearTermID='.$row['gibbonSchoolYearTermID']."&firstDay=$firstDayStamp&lastDay=$lastDayStamp'><img style='margin-top: 3px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a> ";
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
    }
}
