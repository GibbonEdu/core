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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/userFields_add.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $name = $_POST['name'];
    $active = $_POST['active'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $options = '';
    if (isset($_POST['options'])) {
        $options = $_POST['options'];
    }
    $required = $_POST['required'];
    $activePersonStudent = '';
    if (isset($_POST['activePersonStudent'])) {
        $activePersonStudent = $_POST['activePersonStudent'];
    }
    $activePersonStaff = '';
    if (isset($_POST['activePersonStaff'])) {
        $activePersonStaff = $_POST['activePersonStaff'];
    }
    $activePersonParent = '';
    if (isset($_POST['activePersonParent'])) {
        $activePersonParent = $_POST['activePersonParent'];
    }
    $activePersonOther = '';
    if (isset($_POST['activePersonOther'])) {
        $activePersonOther = $_POST['activePersonOther'];
    }
    $activeDataUpdater = $_POST['activeDataUpdater'];
    $activeApplicationForm = $_POST['activeApplicationForm'];

    //Validate Inputs
    if ($name == '' or $active == '' or $description == '' or $type == '' or $required == '' or $activeDataUpdater == '' or $activeApplicationForm == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('name' => $name, 'active' => $active, 'description' => $description, 'type' => $type, 'options' => $options, 'required' => $required, 'activePersonStudent' => $activePersonStudent, 'activePersonStaff' => $activePersonStaff, 'activePersonParent' => $activePersonParent, 'activePersonOther' => $activePersonOther, 'activeDataUpdater' => $activeDataUpdater, 'activeApplicationForm' => $activeApplicationForm);
            $sql = 'INSERT INTO gibbonPersonField SET name=:name, active=:active, description=:description, type=:type, options=:options, required=:required, activePersonStudent=:activePersonStudent, activePersonStaff=:activePersonStaff, activePersonParent=:activePersonParent, activePersonOther=:activePersonOther, activeDataUpdater=:activeDataUpdater, activeApplicationForm=:activeApplicationForm';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 3, '0', STR_PAD_LEFT);

        //Success 0
        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
