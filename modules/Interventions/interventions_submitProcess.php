<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Interventions\INInterventionGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/interventions_submit.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_submit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $strategies = $_POST['strategies'] ?? '';
    $parentsInformed = $_POST['parentsInformed'] ?? '';
    $parentContactDetails = $_POST['parentContactDetails'] ?? '';
    $parentNotInformedReason = $_POST['parentNotInformedReason'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    
    // Validate required fields
    if (empty($gibbonPersonIDStudent) || empty($name) || empty($description) || empty($parentsInformed)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Get form tutor information
    $studentGateway = new StudentGateway($pdo);
    $student = $studentGateway->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonIDStudent)->fetch();
    
    if (empty($student)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Get the form tutor
    $formTutorID = null;
    $data = array('gibbonFormGroupID' => $student['gibbonFormGroupID']);
    $sql = "SELECT gibbonPersonIDTutor FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID";
    $result = $pdo->select($sql, $data);
    
    if ($result->rowCount() > 0) {
        $formTutor = $result->fetch();
        $formTutorID = $formTutor['gibbonPersonIDTutor'];
    }
    
    // Create the intervention record
    $interventionGateway = new INInterventionGateway($pdo);
    
    $data = [
        'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
        'gibbonPersonIDFormTutor' => $formTutorID,
        'name' => $name,
        'description' => $description,
        'status' => 'Referral',
        'formTutorDecision' => 'Pending',
        'formTutorNotes' => null,
        'outcomeNotes' => null,
        'outcomeDecision' => 'Pending'
    ];
    
    // Add additional data to the description field
    $additionalInfo = "\n\n";
    
    // Add strategies if provided
    if (!empty($strategies)) {
        $additionalInfo .= "STRATEGIES ALREADY TRIED:\n" . $strategies . "\n\n";
    }
    
    // Add parent information
    $additionalInfo .= "PARENTS INFORMED: " . ($parentsInformed == 'Y' ? 'Yes' : 'No') . "\n";
    if ($parentsInformed == 'Y' && !empty($parentContactDetails)) {
        $additionalInfo .= "PARENT CONTACT DETAILS:\n" . $parentContactDetails . "\n";
    } else if ($parentsInformed == 'N' && !empty($parentNotInformedReason)) {
        $additionalInfo .= "REASON PARENTS NOT INFORMED:\n" . $parentNotInformedReason . "\n";
    }
    
    // Append the additional information to the description
    $data['description'] .= $additionalInfo;
    
    // Insert the record
    $gibbonINInterventionID = $interventionGateway->insert($data);
    
    if (!$gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Success!
    header("Location: {$session->get('absoluteURL')}/index.php?q=/modules/Interventions/interventions_submit.php&return=success0");
}
