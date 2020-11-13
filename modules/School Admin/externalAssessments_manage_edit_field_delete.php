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

use Gibbon\Forms\Prefab\DeleteForm;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonExternalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'] ?? '';
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'] ?? '';
    if ($gibbonExternalAssessmentFieldID == '' or $gibbonExternalAssessmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID);
            $sql = 'SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/externalAssessments_manage_edit_field_deleteProcess.php?gibbonExternalAssessmentFieldID=$gibbonExternalAssessmentFieldID&gibbonExternalAssessmentID=$gibbonExternalAssessmentID");
            echo $form->getOutput();
        }
    }
}
