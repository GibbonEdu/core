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

use Gibbon\Domain\Timetable\ClassFieldGateway;

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/classFields_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/classFields_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $name = $_POST['name'] ?? '';
    $active = $_POST['active'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $options = $_POST['options'] ?? '';
    if ($type == 'varchar') $options = min(max(0, intval($options)), 255);
    if ($type == 'text') $options = max(0, intval($options));
    $required = $_POST['required'] ?? '';
    
    //Validate Inputs
    if ($name == '' or $active == '' or $description == '' or $type == '' or $required == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $classFieldGateway = $container->get(ClassFieldGateway::class);
        //Write to database
        $data = array('name' => $name, 'active' => $active, 'description' => $description, 'type' => $type, 'options' => $options, 'required' => $required);
        $AI = $classFieldGateway->insert($data);

        //Success 0
        $URL .= '&return=success0&editID='.str_pad($AI, 4, '0', STR_PAD_LEFT);
        header("Location: {$URL}");
    }
}
