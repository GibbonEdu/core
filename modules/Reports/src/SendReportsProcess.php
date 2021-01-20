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

namespace Gibbon\Module\Reports;

use Gibbon\Services\Format;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Services\BackgroundProcess;
use League\Container\ContainerAwareTrait;
use Gibbon\Domain\Students\StudentGateway;
use League\Container\ContainerAwareInterface;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

/**
 * SendNotificationsProcess
 *
 * @version v21
 * @since   v21
 */
class SendReportsProcess extends BackgroundProcess implements ContainerAwareInterface
{
    use ContainerAwareTrait;


    public function __construct()
    {
        
    }

    public function runSendReportsToParents($gibbonReportID, $identifiers)
    {
        $familyGateway = $this->container->get(FamilyGateway::class);
        $reportGateway = $this->container->get(ReportGateway::class);
        $userGateway = $this->container->get(UserGateway::class);
        $reportArchiveEntryGateway = $this->container->get(ReportArchiveEntryGateway::class);
    
        $report = $reportGateway->getByID($gibbonReportID);
        $template = $this->container->get(EmailTemplate::class)->setTemplate('Send Reports to Parents');
        $mail = $this->container->get(Mailer::class);
        $mail->SMTPKeepAlive = true;

        $sendReport = ['emailSent' => 0, 'emailFailed' => 0];

        foreach ($identifiers as $gibbonReportArchiveEntryID) {
            $archive = $reportArchiveEntryGateway->getByID($gibbonReportArchiveEntryID);
            $student = $userGateway->getByID($archive['gibbonPersonID'] ?? '');

            if (empty($archive) || empty($student)) {
                $sendReport['emailFailed']++;
                continue;
            }

            // Generate and save an access token so this report can be accessed securely
            $accessToken = bin2hex(random_bytes(20));
            $data = [
                'timestampSent' => date('Y-m-d H:i:s'),
                'accessToken' => $accessToken,
                'timestampAccessExpiry' => date('Y-m-d H:i:s', strtotime('+1 week')),
            ];
            $reportArchiveEntryGateway->update($gibbonReportArchiveEntryID, $data);
    
            // Get the adults in this family and filter by email settings
            $familyAdults = $familyGateway->selectFamilyAdultsByStudent($archive['gibbonPersonID'], true)->fetchAll();
            $familyAdults = array_filter($familyAdults, function ($adult) {
                return $adult['contactEmail'] == 'Y' && !empty($adult['email']);
            });

            foreach ($familyAdults as $parent) {
                // Setup the data for this template
                $data = [
                    'reportName'           => $report['name'],
                    'studentPreferredName' => $student['preferredName'],
                    'studentSurname'       => $student['surname'],
                    'parentPreferredName'  => $parent['preferredName'],
                    'parentSurname'        => $parent['surname'],
                    'date'                 => Format::date($archive['timestampModified']),
                ];
        
                // Render the templates for this email
                $subject = $template->renderSubject($data);
                $body = $template->renderBody($data);

                $mail->AddAddress($parent['email'], Format::name('', $parent['preferredName'], $parent['surname'], 'Parent', false, true));
                
                $mail->setDefaultSender($subject);
                $mail->renderBody('mail/email.twig.html', [
                    'title'  => $subject,
                    'body'   => $body,
                    'button' => [
                        'url'  => '/modules/Reports/archive_byStudent_download.php?action=view&gibbonReportArchiveEntryID='.$gibbonReportArchiveEntryID.'&token='.$accessToken.'&gibbonPersonIDAccessed='.$parent['gibbonPersonID'],
                        'text' => __('Download'),
                    ],

                    'button2' => $parent['status'] == 'Full' && $parent['canLogin'] == 'Y' ? [
                        'url'  => '/index.php?q=/modules/Reports/archive_byStudent_view.php&gibbonSchoolYearID='.$report['gibbonSchoolYearID'].'&gibbonPersonID='.$student['gibbonPersonID'],
                        'text' => __('View Online'),
                    ] : [],
                ]);

                if ($mail->Send()) {
                    $sendReport['emailSent']++;
                } else {
                    $sendReport['emailFailed']++;
                }

                // Clear addresses
                $mail->ClearAllRecipients();
            }
        }

        // Close SMTP connection
        $mail->smtpClose();

        return $sendReport;
    }

    public function runSendReportsToStudents($gibbonReportID, $identifiers)
    {
        $reportGateway = $this->container->get(ReportGateway::class);
        $userGateway = $this->container->get(UserGateway::class);
        $reportArchiveEntryGateway = $this->container->get(ReportArchiveEntryGateway::class);
    
        $report = $reportGateway->getByID($gibbonReportID);
        $template = $this->container->get(EmailTemplate::class)->setTemplate('Send Reports to Students');
        $mail = $this->container->get(Mailer::class);
        $mail->SMTPKeepAlive = true;

        $sendReport = ['emailSent' => 0, 'emailFailed' => 0];

        foreach ($identifiers as $gibbonReportArchiveEntryID) {
            $archive = $reportArchiveEntryGateway->getByID($gibbonReportArchiveEntryID);
            $student = $userGateway->getByID($archive['gibbonPersonID'] ?? '');

            if (empty($archive) || empty($student)) {
                $sendReport['emailFailed']++;
                continue;
            }

            // Generate and save an access token so this report can be accessed securely
            $accessToken = bin2hex(random_bytes(20));
            $data = [
                'timestampSent' => date('Y-m-d H:i:s'),
                'accessToken' => $accessToken,
                'timestampAccessExpiry' => date('Y-m-d H:i:s', strtotime('+1 week')),
            ];
            $reportArchiveEntryGateway->update($gibbonReportArchiveEntryID, $data);
    
            // Setup the data for this template
            $data = [
                'reportName'           => $report['name'],
                'studentPreferredName' => $student['preferredName'],
                'studentSurname'       => $student['surname'],
                'date'                 => Format::date($archive['timestampModified']),
            ];
    
            // Render the templates for this email
            $subject = $template->renderSubject($data);
            $body = $template->renderBody($data);

            $mail->AddAddress($student['email'], Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true));
            
            $mail->setDefaultSender($subject);
            $mail->renderBody('mail/email.twig.html', [
                'title'  => $subject,
                'body'   => $body,
                'button' => [
                    'url'  => '/modules/Reports/archive_byStudent_download.php?action=view&gibbonReportArchiveEntryID='.$gibbonReportArchiveEntryID.'&token='.$accessToken,
                    'text' => __('Download'),
                ],

                'button2' => $student['status'] == 'Full' && $student['canLogin'] == 'Y' ? [
                    'url'  => '/index.php?q=/modules/Reports/archive_byStudent_view.php&gibbonSchoolYearID='.$report['gibbonSchoolYearID'].'&gibbonPersonID='.$student['gibbonPersonID'],
                    'text' => __('View Online'),
                ] : [],
            ]);

            if ($mail->Send()) {
                $sendReport['emailSent']++;
            } else {
                $sendReport['emailFailed']++;
            }

            // Clear addresses
            $mail->ClearAllRecipients();
            
        }

        // Close SMTP connection
        $mail->smtpClose();

        return $sendReport;
    }
}
