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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_transport_student.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Student Transport').'</div>';
    echo '</div>';

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_transport_studentExport.php?address='.$_GET['q']."'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
    echo '</div>';

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, transport, surname, preferredName, address1, address1District, address1Country, nameShort FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY transport, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo '<th>';
    echo __($guid, 'Transport');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Student');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Address');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Parents');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Roll Group');
    echo '</th>';
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
        echo $row['transport'];
        echo '</td>';
        echo '<td>';
        echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
        echo '</td>';
        echo '<td>';
        try {
            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
            $sqlFamily = 'SELECT gibbonFamily.gibbonFamilyID, nameAddress, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
            $resultFamily = $connection2->prepare($sqlFamily);
            $resultFamily->execute($dataFamily);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($rowFamily = $resultFamily->fetch()) {
            if ($rowFamily['nameAddress'] != '') {
                echo $rowFamily['nameAddress'];
                if ($rowFamily['homeAddress'] != '') {
                    echo ',<br/>';
                }
            }
            if (substr(rtrim($rowFamily['homeAddress']), -1) == ',') {
                $address = substr(rtrim($rowFamily['homeAddress']), 0, -1);
            } else {
                $address = rtrim($rowFamily['homeAddress']);
            }
            $address = addressFormat($address, rtrim($rowFamily['homeAddressDistrict']), rtrim($rowFamily['homeAddressCountry']));
            if ($address != false) {
                $address = explode(',', $address);
                for ($i = 0; $i < count($address); ++$i) {
                    echo $address[$i];
                    if ($i < (count($address) - 1)) {
                        echo ',';
                    }
                    echo '<br/>';
                }
            }
        }
        echo '</td>';
        echo '<td>';
        try {
            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
            $resultFamily = $connection2->prepare($sqlFamily);
            $resultFamily->execute($dataFamily);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($rowFamily = $resultFamily->fetch()) {
            try {
                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                $resultFamily2 = $connection2->prepare($sqlFamily2);
                $resultFamily2->execute($dataFamily2);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowFamily2 = $resultFamily2->fetch()) {
                echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                $numbers = 0;
                for ($i = 1; $i < 5; ++$i) {
                    if ($rowFamily2['phone'.$i] != '') {
                        if ($rowFamily2['phone'.$i.'Type'] != '') {
                            echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                        }
                        if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                            echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                        }
                        echo $rowFamily2['phone'.$i].'<br/>';
                        ++$numbers;
                    }
                }
                if ($numbers == 0) {
                    echo "<span style='font-size: 85%; font-style: italic'>No number available.</span><br/>";
                }
            }
        }
        echo '</td>';
        echo '<td>';
        echo $row['nameShort'];
        echo '</td>';
        echo '</tr>';
    }
    if ($count == 0) {
        echo "<tr class=$rowNum>";
        echo '<td colspan=2>';
        echo __($guid, 'There are no records to display.');
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
