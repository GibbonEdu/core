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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Module\Interventions\Domain\INInterventionGateway;

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
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    
    // Validate required fields
    if (empty($gibbonPersonIDStudent) || empty($name) || empty($description) || empty($parentsInformed)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Get form tutor information
    $studentGateway = $container->get(StudentGateway::class);
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
    $interventionGateway = $container->get(INInterventionGateway::class);
    
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
    
    // Insert the record
    $gibbonINInterventionID = $interventionGateway->insert($data);
    
    if (!$gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if notifications are enabled
    $settingGateway = $container->get(SettingGateway::class);
    $notifyFormTutor = $settingGateway->getSettingByScope('Interventions', 'notifyFormTutor');
    
    if ($notifyFormTutor == 'Y' && !empty($formTutorID)) {
        // Send notification to form tutor
        $notificationEvent = new NotificationEvent('Interventions', 'New Referral');
        $notificationEvent->setNotificationText(sprintf(__('A new intervention referral has been submitted for %1$s by %2$s.'), Format::name('', $student['preferredName'], $student['surname'], 'Student'), Format::name($session->get('title'), $session->get('preferredName'), $session->get('surname'), 'Staff')));
        $notificationEvent->setActionLink('/index.php?q=/modules/Interventions/interventions_manage_edit.php&gibbonINInterventionID='.$gibbonINInterventionID);
        
        // Add form tutor as target for notification
        $notificationEvent->addRecipient($formTutorID);
        
        // Send the notification
        $event = $container->get('event');
        $event->dispatch($notificationEvent, 'Notify');
    }
    
    // Success!
    $URL .= "&return=success0";
    header("Location: {$URL}");
}
