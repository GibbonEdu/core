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

@session_start();

$gibbonPersonIDAssistant = $_GET['gibbonPersonIDAssistant'];
$gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/in_edit.php&gibbonPersonID=$gibbonPersonIDStudent";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Individual Needs/in_edit.php', $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        if ($highestAction != 'Individual Needs Records_viewEdit') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            if ($gibbonPersonIDAssistant == '' or $gibbonPersonIDStudent == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    $data = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonPersonIDAssistant' => $gibbonPersonIDAssistant);
                    $sql = 'SELECT * FROM gibbonINAssistant WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDAssistant=:gibbonPersonIDAssistant';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() < 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonPersonIDAssistant' => $gibbonPersonIDAssistant);
                        $sql = 'DELETE FROM gibbonINAssistant WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDAssistant=:gibbonPersonIDAssistant';
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
    }
}
