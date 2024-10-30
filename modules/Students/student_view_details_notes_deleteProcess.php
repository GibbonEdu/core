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

use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\System\SettingGateway;

include '../../gibbon.php';

$gibbonStudentNoteID = $_POST['gibbonStudentNoteID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$search = $_POST['search'] ?? '';
$subpage = $_POST['subpage'] ?? 'Notes';
$category = $_POST['category'] ?? '';
$allStudents = $_POST['allStudents'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/student_view_details.php&gibbonPersonID=$gibbonPersonID&search=$search&subpage=$subpage&category=$category&allStudents=$allStudents";

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $enableStudentNotes = $container->get(SettingGateway::class)->getSettingByScope('Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $highestAction = getHighestGroupedAction($guid, $_POST['q'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    if (empty($gibbonStudentNoteID) || empty($gibbonPersonID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $noteGateway = $container->get(StudentNoteGateway::class);
    if ($highestAction == "View Student Profile_fullEditAllNotes") {
        $note = $noteGateway->getByID($gibbonStudentNoteID);
    }

    if (empty($note)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    $noteGateway = $container->get(StudentNoteGateway::class);
    $deleted = $noteGateway->delete($gibbonStudentNoteID);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
