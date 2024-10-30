<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    return;
}

$mode = $_GET['mode'] ?? '';
if ($mode == 'Add') {
    
        $data = array('gibbonRubricID' => $_GET['gibbonRubricID'], 'gibbonPersonID' => $_GET['gibbonPersonID'], 'gibbonRubricCellID' => $_GET['gibbonRubricCellID'], 'contextDBTable' => $_GET['contextDBTable'], 'contextDBTableID' => $_GET['contextDBTableID']);
        $sql = 'INSERT INTO gibbonRubricEntry SET gibbonRubricID=:gibbonRubricID, gibbonPersonID=:gibbonPersonID, gibbonRubricCellID=:gibbonRubricCellID, contextDBTable=:contextDBTable, contextDBTableID=:contextDBTableID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
}
if ($mode == 'Remove') {
    
        $data = array('gibbonRubricID' => $_GET['gibbonRubricID'], 'gibbonPersonID' => $_GET['gibbonPersonID'], 'gibbonRubricCellID' => $_GET['gibbonRubricCellID'], 'contextDBTable' => $_GET['contextDBTable'], 'contextDBTableID' => $_GET['contextDBTableID']);
        $sql = 'DELETE FROM gibbonRubricEntry WHERE gibbonRubricID=:gibbonRubricID AND gibbonPersonID=:gibbonPersonID AND gibbonRubricCellID=:gibbonRubricCellID AND contextDBTable=:contextDBTable AND contextDBTableID=:contextDBTableID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
}
