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

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$id = $_GET['id'];
$mode = null;
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
}
if ($mode == '') {
    $mode = 'masterAdd';
}
$gibbonUnitBlockID = null;
if (isset($_GET['gibbonUnitBlockID'])) {
    $gibbonUnitBlockID = $_GET['gibbonUnitBlockID'];
}

//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
$gibbonUnitID = null;
if (isset($_GET['gibbonUnitID'])) {
    $gibbonUnitID = $_GET['gibbonUnitID'];
}
if (strpos($gibbonUnitID, '-') == false) {
    $hooked = false;
} else {
    $hooked = true;
    $gibbonHookIDToken = substr($gibbonUnitID, 11);
    $gibbonUnitIDToken = substr($gibbonUnitID, 0, 10);
}

if ($gibbonUnitBlockID != '') {
    try {
        if ($hooked == false) {
            $data = array('gibbonUnitBlockID' => $gibbonUnitBlockID);
            $sql = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitBlockID=:gibbonUnitBlockID';
        } else {
            try {
                $dataHooks = array('gibbonHookID' => $gibbonHookIDToken);
                $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name";
                $resultHooks = $connection2->prepare($sqlHooks);
                $resultHooks->execute($dataHooks);
            } catch (PDOException $e) {
            }
            if ($resultHooks->rowCount() == 1) {
                $rowHooks = $resultHooks->fetch();
                $hookOptions = unserialize($rowHooks['options']);
                if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                    $data = array('unitSmartBlockIDField' => $gibbonUnitBlockID);
                    $sql = 'SELECT * FROM '.$hookOptions['unitSmartBlockTable'].' WHERE '.$hookOptions['unitSmartBlockIDField'].'=:unitSmartBlockIDField';
                }
            }
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        if ($hooked == false) {
            $title = $row['title'];
            $type = $row['type'];
            $length = $row['length'];
            $contents = $row['contents'];
            $teachersNotes = $row['teachersNotes'];
        } else {
            $title = $row[$hookOptions['unitSmartBlockTitleField']];
            $type = $row[$hookOptions['unitSmartBlockTypeField']];
            $length = $row[$hookOptions['unitSmartBlockLengthField']];
            $contents = $row[$hookOptions['unitSmartBlockContentsField']];
            $teachersNotes = $row[$hookOptions['unitSmartBlockTeachersNotesField']];
        }
    }
} else {
    $title = '';
    $type = '';
    $length = '';
    $contents = getSettingByScope($connection2, 'Planner', 'smartBlockTemplate');
    $teachersNotes = '';
}

makeBlock($guid,  $connection2, $id, $mode, $title, $type, $length, $contents, 'N', $gibbonUnitBlockID, '', $teachersNotes, false);
