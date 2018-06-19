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

$gibbonPersonFieldID = $_GET['gibbonPersonFieldID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/userFields_edit.php&gibbonPersonFieldID=$gibbonPersonFieldID";

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonPersonFieldID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Validate Inputs
        $name = $_POST['name'];
        $active = $_POST['active'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        $options = (isset($_POST['options']))? $_POST['options'] : '';
        if ($type == 'varchar') $options = min(max(1, intval($options)), 255);
        if ($type == 'text') $options = max(1, intval($options));
        $required = $_POST['required'];

        $roleCategories = (isset($_POST['roleCategories']))? $_POST['roleCategories'] : array();
        $activePersonStudent = in_array('activePersonStudent', $roleCategories);
        $activePersonStaff = in_array('activePersonStaff', $roleCategories);
        $activePersonParent = in_array('activePersonParent', $roleCategories);
        $activePersonOther = in_array('activePersonOther', $roleCategories);
        
        $activeDataUpdater = $_POST['activeDataUpdater'];
        $activeApplicationForm = $_POST['activeApplicationForm'];

        if ($name == '' or $active == '' or $description == '' or $type == '' or $required == '' or $activeDataUpdater == '' or $activeApplicationForm == '') {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('name' => $name, 'active' => $active, 'description' => $description, 'type' => $type, 'options' => $options, 'required' => $required, 'activePersonStudent' => $activePersonStudent, 'activePersonStaff' => $activePersonStaff, 'activePersonParent' => $activePersonParent, 'activePersonOther' => $activePersonOther, 'activeDataUpdater' => $activeDataUpdater, 'activeApplicationForm' => $activeApplicationForm, 'gibbonPersonFieldID' => $gibbonPersonFieldID);
                $sql = 'UPDATE gibbonPersonField SET name=:name, active=:active, description=:description, type=:type, options=:options, required=:required, activePersonStudent=:activePersonStudent, activePersonStaff=:activePersonStaff, activePersonParent=:activePersonParent, activePersonOther=:activePersonOther, activeDataUpdater=:activeDataUpdater, activeApplicationForm=:activeApplicationForm WHERE gibbonPersonFieldID=:gibbonPersonFieldID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
