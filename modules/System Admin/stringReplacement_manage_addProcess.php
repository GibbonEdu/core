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

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/stringReplacement_manage_add.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $original = $_POST['original'];
    $replacement = $_POST['replacement'];
    $mode = $_POST['mode'];
    $caseSensitive = $_POST['caseSensitive'];
    $priority = $_POST['priority'];

    //Validate Inputs
    if ($original == '' or $replacement == '' or $mode == '' or $caseSensitive == '' or $priority == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('original' => $original, 'replacement' => $replacement, 'mode' => $mode, 'caseSensitive' => $caseSensitive, 'priority' => $priority);
            $sql = 'INSERT INTO gibbonString SET original=:original, replacement=:replacement, mode=:mode, caseSensitive=:caseSensitive, priority=:priority';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

        //Update string list in session & clear cache to force reload
        $gibbon->locale->setStringReplacementList($pdo, true);
        $_SESSION[$guid]['pageLoads'] = null;

        //Success 0
        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
