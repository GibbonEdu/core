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

use Gibbon\Forms\Prefab\DeleteForm;

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonExternalAssessmentStudentID = $_GET['gibbonExternalAssessmentStudentID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';

    //Check if gibbonExternalAssessmentStudentID specified
    if ($gibbonExternalAssessmentStudentID == '' or $gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
            $sql = 'SELECT * FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();

            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/externalAssessment_manage_details_deleteProcess.php?gibbonExternalAssessmentStudentID=$gibbonExternalAssessmentStudentID&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents");
            echo $form->getOutput();
        }
    }
}
