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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Students\StudentNoteGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonStudentNoteID = $_GET['gibbonStudentNoteID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $subpage = $_GET['subpage'] ?? '';

    $enableStudentNotes = $container->get(SettingGateway::class)->getSettingByScope('Students', 'enableStudentNotes');
    if ($enableStudentNotes != 'Y') {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (empty($gibbonStudentNoteID) || empty($gibbonPersonID) || empty($subpage)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $noteGateway = $container->get(StudentNoteGateway::class);
    if ($highestAction == "View Student Profile_fullEditAllNotes") {
        $note = $noteGateway->getByID($gibbonStudentNoteID);
    }
    
    if (empty($note)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Students/student_view_details_notes_deleteProcess.php', true);
    echo $form->getOutput();
}
