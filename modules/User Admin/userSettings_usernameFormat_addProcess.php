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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/userSettings_usernameFormat_add.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $format = $_POST['format'] ?? '';
    $gibbonRoleIDList = $_POST['gibbonRoleIDList'] ?? [];
    $isDefault = $_POST['isDefault'] ?? '';
    $isNumeric = $_POST['isNumeric'] ?? '';
    $numericValue = $_POST['numericValue'] ?? '0';
    $numericSize = $_POST['numericSize'] ?? '4';
    $numericIncrement = $_POST['numericIncrement'] ?? '1';

    if (empty($format) || empty($gibbonRoleIDList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gibbonRoleIDList = implode(',', $gibbonRoleIDList);

        try {
            $data = array('format' => $format, 'gibbonRoleIDList' => $gibbonRoleIDList, 'isDefault' => $isDefault, 'isNumeric' => $isNumeric, 'numericValue' => $numericValue, 'numericSize' => $numericSize, 'numericIncrement' => $numericIncrement);
            $sql = "INSERT INTO gibbonUsernameFormat SET format=:format, gibbonRoleIDList=:gibbonRoleIDList, isDefault=:isDefault, isNumeric=:isNumeric, numericValue=:numericValue, numericSize=:numericSize, numericIncrement=:numericIncrement";
            $result = $pdo->executeQuery($data, $sql);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 3, '0', STR_PAD_LEFT);

        // Update default
        if ($isDefault == 'Y') {
            $data = array('gibbonUsernameFormatID' => $AI);
            $sql = "UPDATE gibbonUsernameFormat SET isDefault='N' WHERE gibbonUsernameFormatID <> :gibbonUsernameFormatID";
            $result = $pdo->executeQuery($data, $sql);
        }

        //Success 0
        $URL .= '&return=success0&editID='.$AI;
        header("Location: {$URL}");
        exit;
    }
}
