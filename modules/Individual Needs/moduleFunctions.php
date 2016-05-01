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

//$mode can be blank or "disabled". $archive is a serialized array of values previously archived
function printINStatusTable($connection2, $gibbonPersonID, $mode = '', $archive = '')
{
    $output = false;

    try {
        $dataDescriptors = array();
        $sqlDescriptors = 'SELECT * FROM gibbonINDescriptor ORDER BY sequenceNumber, nameShort';
        $resultDescriptors = $connection2->prepare($sqlDescriptors);
        $resultDescriptors->execute($dataDescriptors);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $dataSeverity = array();
        $sqlSeverity = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber, nameShort';
        $resultSeverity = $connection2->prepare($sqlSeverity);
        $resultSeverity->execute($dataSeverity);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultDescriptors->rowCount() < 1 or $resultSeverity->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'Individual needs descriptors or severity levels have not been set.');
        $output .= '</div>';
    } else {
        $descriptors = array();
        $count = 0;
        while ($rowDescriptors = $resultDescriptors->fetch()) {
            $descriptors[$count][0] = $rowDescriptors['gibbonINDescriptorID'];
            $descriptors[$count][1] = $rowDescriptors['name'];
            $descriptors[$count][2] = $rowDescriptors['nameShort'];
            $descriptors[$count][3] = $rowDescriptors['description'];
            ++$count;
        }

        $severity = array();
        $count = 0;
        while ($rowSeverity = $resultSeverity->fetch()) {
            $severity[$count][0] = $rowSeverity['gibbonAlertLevelID'];
            $severity[$count][1] = __($guid, $rowSeverity['name']);
            $severity[$count][2] = $rowSeverity['nameShort'];
            $severity[$count][3] = __($guid, $rowSeverity['description']);
            $severity[$count][4] = $rowSeverity['color'];
            ++$count;
        }

        $personDescriptors = array();
        $count = 0;
        if ($archive == '') { //Not an archive, get live data
            try {
                $dataPersonDescriptors = array('gibbonPersonID' => $gibbonPersonID);
                $sqlPersonDescriptors = 'SELECT * FROM gibbonINPersonDescriptor WHERE gibbonPersonID=:gibbonPersonID';
                $resultPersonDescriptors = $connection2->prepare($sqlPersonDescriptors);
                $resultPersonDescriptors->execute($dataPersonDescriptors);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowPersonDescriptors = $resultPersonDescriptors->fetch()) {
                $personDescriptors[$count][0] = $rowPersonDescriptors['gibbonINDescriptorID'];
                $personDescriptors[$count][1] = $rowPersonDescriptors['gibbonAlertLevelID'];
                ++$count;
            }
        } else { //It is an archive, so populate array
            $archive = unserialize($archive);
            if (count($archive) > 0) {
                foreach ($archive as $archiveEntry) {
                    $personDescriptors[$count][0] = $archiveEntry['gibbonINDescriptorID'];
                    $personDescriptors[$count][1] = $archiveEntry['gibbonAlertLevelID'];
                    ++$count;
                }
            }
        }

        //Print IN Status table
        $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
        $output .= "<tr class='head'>";
        $output .= '<th>';
        $output .= __($guid, 'Descriptor');
        $output .= '<th>';
        for ($i = 0; $i < count($severity); ++$i) {
            $output .= '<th>';
            $output .= "<span title='".$severity[$i][3]."'>".$severity[$i][1].'</span>';
            $output .= '<th>';
        }
        $output .= '</tr>';
        for ($n = 0; $n < count($descriptors); ++$n) {
            if ($n % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            $output .= "<tr class=$rowNum>";
            $output .= '<td>';
            $output .= "<span title='".__($guid, $descriptors[$n][3])."'>".__($guid, $descriptors[$n][1]).'</span>';
            $output .= '<td>';
            for ($i = 0; $i < count($severity); ++$i) {
                $output .= "<td style='width: 10%'>";
                $checked = '';
                for ($j = 0; $j < count($personDescriptors); ++$j) {
                    if ($personDescriptors[$j][0] == $descriptors[$n][0] and $personDescriptors[$j][1] == $severity[$i][0]) {
                        $checked = 'checked';
                    }
                }
                $output .= "<input $mode $checked type='checkbox' name='status[]' value='".$descriptors[$n][0].'-'.$severity[$i][0]."'>";
                $output .= '<td>';
            }
            $output .= '</tr>';
        }
        $output .= '</table>';
    }

    return $output;
}
