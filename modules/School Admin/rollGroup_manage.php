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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Roll Groups').'</div>';
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
            $previousYear = getPreviousSchoolYearID($gibbonSchoolYearID, $connection2);
        $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
        if ($previousYear != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
        } else {
            echo __($guid, 'Previous Year').' ';
        }
        echo ' | ';
        if ($nextYear != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
        echo '</div>';

        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonRollGroupID, gibbonSchoolYear.name as yearName, gibbonRollGroup.name, gibbonRollGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonSpace.name AS space, website FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) LEFT JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber, gibbonRollGroup.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        if ($nextYear != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/rollGroup_manage_copyProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonSchoolYearIDNext=$nextYear'> ".__($guid, 'Copy All To Next Year')."<img style='margin-left: 3px' title='".__($guid, 'Copy All To Next Year')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a> | ";
        }
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rollGroup_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Short Name').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Form Tutors');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Location');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Website');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Actions');
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

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo '<b>'.$row['name'].'</b><br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".$row['nameShort'].'</span>';
                echo '</td>';
                echo '<td>';
                try {
                    $dataTutor = array('gibbonPersonID1' => $row['gibbonPersonIDTutor'], 'gibbonPersonID2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonID3' => $row['gibbonPersonIDTutor3']);
                    $sqlTutor = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3';
                    $resultTutor = $connection2->prepare($sqlTutor);
                    $resultTutor->execute($dataTutor);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowTutor = $resultTutor->fetch()) {
                    echo formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', false, true).'<br/>';
                }
                echo '</td>';
                echo '<td>';
                echo $row['space'];
                echo '</td>';
                echo '<td>';
                if ($row['website'] != '') {
                    echo "<a target='_blank' href='".$row['website']."'>".$row['website'].'</a>';
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage_edit.php&gibbonRollGroupID='.$row['gibbonRollGroupID']."&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollGroup_manage_delete.php&gibbonRollGroupID='.$row['gibbonRollGroupID']."&gibbonSchoolYearID=$gibbonSchoolYearID&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo '</td>';
                echo '</tr>';

                ++$count;
            }
            echo '</table>';
        }
    }
}
