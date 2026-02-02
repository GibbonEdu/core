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
use Gibbon\Services\Format;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\Staff\StaffApplicationFormGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$action = $_POST['action'] ?? [];
$identifiers = $_POST['identifiers'] ?? []; 
$templateName = $_POST['templateName'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/applicationForm_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffApplicationFormGateway = $container->get(StaffApplicationFormGateway::class);
    $success = true;

    // Validate the required data
    $identifiers = is_array($identifiers) ? $identifiers : [$identifiers];
    if (empty($identifiers))  {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($action == 'Reject') {
        foreach ($identifiers as $gibbonStaffApplicationFormID) {
            // Validate the database relationships exist
            if (!$staffApplicationFormGateway->exists($gibbonStaffApplicationFormID)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            }

            // Update status to Rejected
            $success = $staffApplicationFormGateway->update($gibbonStaffApplicationFormID, ['status' => 'Rejected']);
        }
    }

    // Send rejection emails if requested
    if ($action == 'Reject' && !empty($templateName)) {
        $sendReport = ['emailSent' => 0, 'emailFailed' => 0];    
        $template = $container->get(EmailTemplate::class)->setTemplate($templateName);
        $mail = $container->get(Mailer::class);
        $mail->SMTPKeepAlive = true;
        
        foreach ($identifiers as $gibbonStaffApplicationFormID) {
            // Get the application details
            $application = $staffApplicationFormGateway->getByID($gibbonStaffApplicationFormID);

            if (empty($application) || empty($application['email'])) {
                $sendReport['emailFailed']++;
                continue;
            }

            // Prepare template data
            $data = [
                'preferredName'     => $application['preferredName'] ?? '',
                'surname'           => $application['surname'] ?? '',
                'date'              => Format::date(date('Y-m-d')),
            ];

            // Render the email
            $subject = $template->renderSubject($data);
            $body = $template->renderBody($data);

            $mail->AddAddress($application['email'], Format::name('', $data['preferredName'], $data['surname'], 'Staff', false, true));
            
            $mail->setDefaultSender($subject);
            $mail->renderBody('mail/email.twig.html', [
                'title'  => $subject,
                'body'   => $body,
            ]);

            if ($mail->Send()) {
                $sendReport['emailSent']++;
            } else {
                $sendReport['emailFailed']++;
            }

            // Clear addresses for next email
            $mail->ClearAllRecipients();
        }

        // Close SMTP connection
        $mail->smtpClose();

        // Update success based on email results
        if ($sendReport['emailFailed'] > 0 && $sendReport['emailSent'] == 0) {
            $success = false;
        }
    }

    $URL .= !$success ? "&return=error2" : ($action == 'Reject with email' ? "&return=success5" : "&return=success0");

    header("Location: {$URL}");
}
