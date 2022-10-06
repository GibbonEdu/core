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
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$name = $_POST['name'] ?? '';
$nameShort = $_POST['nameShort'] ?? '';
$gibbonTTColumnID = $_POST['gibbonTTColumnID'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'ttColumn_edit')->withQueryParam('gibbonTTColumnID', $gibbonTTColumnID);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    //Proceed!
    //Check if special day specified
    if ($gibbonTTColumnID == '') {
        header('Location: ' . $URL->withReturn('error1'));
    } else {
        try {
            $data = array('gibbonTTColumnID' => $gibbonTTColumnID);
            $sql = 'SELECT * FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            header('Location: ' . $URL->withReturn('error2'));
            exit();
        }

        if ($result->rowCount() != 1) {
            header('Location: ' . $URL->withReturn('error2'));
        } else {
            //Validate Inputs
            if ($name == '' or $nameShort == '') {
                header('Location: ' . $URL->withReturn('error3'));
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'gibbonTTColumnID' => $gibbonTTColumnID);
                    $sql = 'SELECT * FROM gibbonTTColumn WHERE name=:name AND NOT gibbonTTColumnID=:gibbonTTColumnID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    header('Location: ' . $URL->withReturn('error2'));
                    exit();
                }

                if ($result->rowCount() > 0) {
                    header('Location: ' . $URL->withReturn('error3'));
                } else {
                    //Write to database
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonTTColumnID' => $gibbonTTColumnID);
                        $sql = 'UPDATE gibbonTTColumn SET name=:name, nameShort=:nameShort WHERE gibbonTTColumnID=:gibbonTTColumnID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        header('Location: ' . $URL->withReturn('error2'));
                        exit();
                    }

                    header('Location: ' . $URL->withReturn('success0'));
                }
            }
        }
    }
}
