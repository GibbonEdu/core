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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function getBadges($connection2, $guid, $gibbonPersonID, $gibbon = null)
{
    $output = '';

    $gibbonThemeName = $_SESSION['gibbonThemeName'] ?? 'Default';
    $absoluteURL = $_SESSION['absoluteURL'] ?? '';
    if($gibbon != null)
    {
        $gibbonThemeName = $gibbon->session->get('gibbonThemeName');
        $absoluteURL = $gibbon->session->get('absoluteURL');
    }
    
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT badgesBadgeStudent.*, badgesBadge.name AS award, badgesBadge.logo AS logo, badgesBadge.category AS category, gibbonSchoolYear.name AS year FROM badgesBadgeStudent JOIN badgesBadge ON (badgesBadgeStudent.badgesBadgeID=badgesBadge.badgesBadgeID) JOIN gibbonSchoolYear ON (badgesBadgeStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber DESC, date DESC';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($result->rowCount() < 1) {
        $output .= "<div class='warning'>";
        $output .= __('There are no records to display.');
        $output .= '</div>';
    } else {
        //Prep array of awards
        $awardYears = array();
        $innerCount = 0;
        while ($row = $result->fetch()) {
            $awardYears[$row['year']][0] = $row['year'];
            if (isset($awardYears[$row['year']][1]) == false) { //No data, so start adding data
                $innerCount = 0;
                $awardYears[$row['year']][1] = array("$innerCount" => $row['award']);
                $awardYears[$row['year']][2] = array("$innerCount" => $row['logo']);
                $awardYears[$row['year']][3] = array("$innerCount" => $row['category']);
                ++$innerCount;
            } else { //Already data, so start appending
                $awardYears[$row['year']][1][$innerCount] = $row['award'];
                $awardYears[$row['year']][2][$innerCount] = $row['logo'];
                $awardYears[$row['year']][3][$innerCount] = $row['category'];
                ++$innerCount;
            }
        }

        //Spit out awards from array
        $columns = 3;
        foreach ($awardYears as $awardYear) { //Spit out years
            $output .= '<h3>';
            $output .= $awardYear[0];
            $output .= '</h3>';

            $count = 0;
            foreach ($awardYear[1] as $awards) {
                if ($count % $columns == 0) {
                    if ($count == 0) {
                        $output .= "<table class='margin-bottom: 10px; smallIntBorder' cellspacing='0' style='width:100%'>";
                    }
                    $output .= '<tr>';
                }

                $output .= "<td style='padding-top: 15px!important; padding-bottom: 15px!important; width:33%; text-align: center; vertical-align: top'>";
                if ($awardYear[2][$count] != '') {
                    $output .= "<img style='margin-bottom: 20px; max-width: 150px' src='". $absoluteURL .'/'.$awardYear[2][$count]."'/><br/>";
                } else {
                    $output .= "<img style='margin-bottom: 20px; max-width: 150px' src='". $absoluteURL .'/themes/'. $gibbonThemeName ."/img/anonymous_240_square.jpg'/><br/>";
                }
                $output .= '<b>'.$awards.'</b><br/>';
                $output .= '<span class=\'emphasis small\'>'.$awardYear[3][$count].'</span><br/>';
                $output .= '</td>';

                if ($count % $columns == ($columns - 1)) {
                    $output .= '</tr>';
                }
                ++$count;
            }

            if ($count % $columns != 0) {
                for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                    $output .= '<td></td>';
                }
                $output .= '</tr>';
            }

            $output .= '</table>';
        }
    }

    return $output;
}
