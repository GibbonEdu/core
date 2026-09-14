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

use Gibbon\Data\Validator;
use Gibbon\Domain\Markbook\MarkbookColumnGateway;

include '../../gibbon.php';

include './moduleFunctions.php';

$validator = $container->get(Validator::class);
$_GET = $validator->sanitize($_GET);

if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    die();
}

$mode = $_GET['mode'] ?? '';

$data = [
    'gibbonRubricID'     => $validator->sanitizeNumeric($_GET['gibbonRubricID'] ?? ''),
    'gibbonPersonID'     => $validator->sanitizeNumeric($_GET['gibbonPersonID'] ?? ''),
    'gibbonRubricCellID' => $validator->sanitizeNumeric($_GET['gibbonRubricCellID'] ?? ''),
    'contextDBTable'     => $validator->sanitizeAlphaNumeric($_GET['contextDBTable'] ?? ''),
    'contextDBTableID'   => $validator->sanitizeAlphaNumeric($_GET['contextDBTableID'] ?? ''),
];

if (empty($data['gibbonRubricID']) || empty($data['gibbonPersonID']) || empty($data['gibbonRubricCellID']) || empty($data['contextDBTable']) || empty($data['contextDBTableID'])) {
    die();
}

// Check for permissions to edit the contextDBTable
if ($data['contextDBTable'] == 'gibbonMarkbookColumn') {
    $markbookColumnGateway = $container->get(MarkbookColumnGateway::class);
    $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit_data.php', $connection2);

    if ($highestAction == 'Edit Markbook_everything' || $highestAction == 'Edit Markbook_multipleClassesAcrossSchool') {
        $markbookColumn = $markbookColumnGateway->getByID($data['contextDBTableID']);
    } elseif ($highestAction == 'Edit Markbook_multipleClassesInDepartment') {
        $markbookColumn = $markbookColumnGateway->getMarkbookColumnByDepartmentPerson($data['contextDBTableID'], $session->get('gibbonPersonID'));
    } else {
        $markbookColumn = $markbookColumnGateway->getMarkbookColumnByTeacher($data['contextDBTableID'], $session->get('gibbonPersonID'));
    }

    if (empty($markbookColumn) || $markbookColumn['gibbonMarkbookColumnID'] != $data['contextDBTableID']) {
        die();
    }
} elseif ($data['contextDBTable'] == 'atlColumn' && isActionAccessible($guid, $connection2, '/modules/ATL/atl_write_data.php') == false) {
    die();
} else {
    die();
}

if ($mode == 'Add') {
    $sql = 'INSERT INTO gibbonRubricEntry SET gibbonRubricID=:gibbonRubricID, gibbonPersonID=:gibbonPersonID, gibbonRubricCellID=:gibbonRubricCellID, contextDBTable=:contextDBTable, contextDBTableID=:contextDBTableID';
    $pdo->insert($sql, $data);
}
if ($mode == 'Remove') {
    $sql = 'DELETE FROM gibbonRubricEntry WHERE gibbonRubricID=:gibbonRubricID AND gibbonPersonID=:gibbonPersonID AND gibbonRubricCellID=:gibbonRubricCellID AND contextDBTable=:contextDBTable AND contextDBTableID=:contextDBTableID';
    $pdo->delete($sql, $data);
}
