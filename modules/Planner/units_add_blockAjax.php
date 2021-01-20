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

include '../../gibbon.php';

include './moduleFunctions.php';

$page = $container->get('page');

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

if ($gibbonUnitBlockID != '') {
    
        $data = array('gibbonUnitBlockID' => $gibbonUnitBlockID);
        $sql = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitBlockID=:gibbonUnitBlockID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $title = $row['title'];
        $type = $row['type'];
        $length = $row['length'];
        $contents = $row['contents'];
        $teachersNotes = $row['teachersNotes'];
    }
} else {
    $title = '';
    $type = '';
    $length = '';
    $contents = getSettingByScope($connection2, 'Planner', 'smartBlockTemplate');
    $teachersNotes = '';
}

makeBlock($guid,  $connection2, $id, $mode, $title, $type, $length, $contents, 'N', $gibbonUnitBlockID, '', $teachersNotes, false);
