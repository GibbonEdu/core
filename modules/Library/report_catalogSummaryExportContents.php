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

include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Library/report_catalogSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $ownershipType = trim($_GET['ownershipType']);
    $gibbonLibraryTypeID = trim($_GET['gibbonLibraryTypeID']);
    $gibbonSpaceID = trim($_GET['gibbonSpaceID']);
    $status = trim($_GET['status']);

    echo '<h1>';
    echo __($guid, 'Catalog Summary');
    echo '</h1>';

    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($ownershipType != '') {
            $data['ownershipType'] = $ownershipType;
            $sqlWhere .= 'ownershipType=:ownershipType AND ';
        }
        if ($gibbonLibraryTypeID != '') {
            $data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
            $sqlWhere .= 'gibbonLibraryItem.gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
        }
        if ($gibbonSpaceID != '') {
            $data['gibbonSpaceID'] = $gibbonSpaceID;
            $sqlWhere .= 'gibbonSpaceID=:gibbonSpaceID AND ';
        }
        if ($status != '') {
            $data['status'] = $status;
            $sqlWhere .= 'status=:status AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        $sql = "SELECT gibbonLibraryItem.*, gibbonLibraryType.fields AS typeFields FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) $sqlWhere ORDER BY id";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo '<th>';
    echo __($guid, 'School ID');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Name').'<br/>';
    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Producer').'</span>';
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Type');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Location');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Ownership').'<br/>';
    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'User/Owner').'</span>';
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Status').'<br/>';
    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Borrowable').'</span>';
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Purchase Date').'<br/>';
    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Vendor').'</span>';
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Details').'<br/>';
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
        echo '<b>'.$row['id'].'</b>';
        echo '</td>';
        echo '<td>';
        echo '<b>'.$row['name'].'</b>';
        if ($row['producer'] != '') {
            echo " ; <span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
        }
        echo '</td>';
        echo '<td>';
        try {
            $dataType = array('gibbonLibraryTypeID' => $row['gibbonLibraryTypeID']);
            $sqlType = 'SELECT name FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID';
            $resultType = $connection2->prepare($sqlType);
            $resultType->execute($dataType);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultType->rowCount() == 1) {
            $rowType = $resultType->fetch();
            echo __($guid, $rowType['name']);
        }
        echo '</td>';
        echo '<td>';
        if ($row['gibbonSpaceID'] != '') {
            try {
                $dataSpace = array('gibbonSpaceID' => $row['gibbonSpaceID']);
                $sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
                $resultSpace = $connection2->prepare($sqlSpace);
                $resultSpace->execute($dataSpace);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultSpace->rowCount() == 1) {
                $rowSpace = $resultSpace->fetch();
                echo $rowSpace['name'];
            }
        }
        if ($row['locationDetail'] != '') {
            echo " ; <span style='font-size: 85%; font-style: italic'>".$row['locationDetail'].'</span>';
        }
        echo '</td>';
        echo '<td>';
        if ($row['ownershipType'] == 'School') {
            echo $_SESSION[$guid]['organisationNameShort'];
        } elseif ($row['ownershipType'] == 'Individual') {
            echo 'Individual';
        }
        if ($row['gibbonPersonIDOwnership'] != '') {
            try {
                $dataPerson = array('gibbonPersonID' => $row['gibbonPersonIDOwnership']);
                $sqlPerson = 'SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                $resultPerson = $connection2->prepare($sqlPerson);
                $resultPerson->execute($dataPerson);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultPerson->rowCount() == 1) {
                $rowPerson = $resultPerson->fetch();
                echo "; <span style='font-size: 85%; font-style: italic'>".formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true).'</span>';
            }
        }
        echo '</td>';
        echo '<td>';
        echo $row['status'];
        echo " ; <span style='font-size: 85%; font-style: italic'>".$row['borrowable'].'</span>';
        echo '</td>';
        echo '<td>';
        if ($row['purchaseDate'] == '') {
            echo '<i>'.__($guid, 'Unknown').'</i>';
        } else {
            echo dateConvertBack($guid, $row['purchaseDate']).' ; ';
        }
        if ($row['vendor'] != '') {
            echo "; <span style='font-size: 85%; font-style: italic'>".$row['vendor'].'</span>';
        }
        echo '</td>';
        echo '<td>';
        $typeFields = unserialize($row['typeFields']);
        $fields = unserialize($row['fields']);
        foreach ($typeFields as $typeField) {
            if (isset($fields[$typeField['name']])) {
                if ($fields[$typeField['name']] != '') {
                    echo '<b>'.__($guid, $typeField['name']).': </b>';
                    if (isset($fields[$typeField['name']])) {
                        echo $fields[$typeField['name']].' ; ';
                    }
                }
            }
        }
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
