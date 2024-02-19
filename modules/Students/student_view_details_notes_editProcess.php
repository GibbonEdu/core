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

use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['note' => 'HTML']);

$logGateway = $container->get(LogGateway::class);
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$subpage = $_GET['subpage'] ?? '';
$gibbonStudentNoteID = $_GET['gibbonStudentNoteID'] ?? '';
$allStudents = $_GET['allStudents'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Students/student_view_details_notes_edit.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&subpage=Notes&gibbonStudentNoteID=$gibbonStudentNoteID&category=".$_GET['category']."&allStudents=$allStudents";

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0";
        header("Location: {$URL}");
    } else {
        $enableStudentNotes = $container->get(SettingGateway::class)->getSettingByScope('Students', 'enableStudentNotes');
        if ($enableStudentNotes != 'Y') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if note specified
            if ($gibbonStudentNoteID == '' or $gibbonPersonID == '' or $subpage == '') {
                echo 'Fatal error loading this page!';
            } else {
                try {
                    if ($highestAction == "View Student Profile_fullEditAllNotes") {
                        $data = array('gibbonStudentNoteID' => $gibbonStudentNoteID);
                        $sql = 'SELECT * FROM gibbonStudentNote WHERE gibbonStudentNoteID=:gibbonStudentNoteID';
                    }
                    else {
                        $data = array('gibbonStudentNoteID' => $gibbonStudentNoteID, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
                        $sql = 'SELECT * FROM gibbonStudentNote WHERE gibbonStudentNoteID=:gibbonStudentNoteID AND gibbonPersonIDCreator=:gibbonPersonIDCreator';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();
                    //Validate Inputs
                    $title = $_POST['title'] ?? '';
                    $gibbonStudentNoteCategoryID = $_POST['gibbonStudentNoteCategoryID'] ?? '';
                    if ($gibbonStudentNoteCategoryID == '') {
                        $gibbonStudentNoteCategoryID = null;
                    }
                    $note = $_POST['note'] ?? '';

                    if ($note == '') {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //Write to database
                        try {
                            $data = array('gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID, 'title' => $title, 'note' => $note, 'gibbonStudentNoteID' => $gibbonStudentNoteID);
                            $sql = 'UPDATE gibbonStudentNote SET gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID, title=:title, note=:note WHERE gibbonStudentNoteID=:gibbonStudentNoteID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Attempt to write logo
                        $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), getModuleIDFromName($connection2, 'Students'), $session->get('gibbonPersonID'), 'Student Profile - Note Edit', array('gibbonStudentNoteID' => $gibbonStudentNoteID, 'noteOriginal' => $row['note'], 'noteNew' => $note), $_SERVER['REMOTE_ADDR']);

                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
