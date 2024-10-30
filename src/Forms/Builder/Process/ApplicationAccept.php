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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Contracts\Services\Session;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Process\ViewableProcess;
use Gibbon\Forms\Builder\View\ApplicationAcceptView;
use Gibbon\Services\Format;

class ApplicationAccept extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = [];
    protected $initialStatus;

    protected $session;
    protected $notificationSender;
    protected $notificationGateway;

    public function __construct(Session $session, NotificationSender $notificationSender, NotificationGateway $notificationGateway)
    {
        $this->session = $session;
        $this->notificationSender = $notificationSender;
        $this->notificationGateway = $notificationGateway;
    }

    public function getViewClass() : string
    {
        return ApplicationAcceptView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return true;
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $this->initialStatus = $formData->getStatus();

        $formData->setStatus('Accepted');
        $formData->setResult('statusDate', date('Y-m-d H:i:s'));

        $this->sendNotifications($builder, $formData);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $formData->setStatus($this->initialStatus);
        $formData->setResult('statusDate', null);
    }

    protected function sendNotifications(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $studentName = Format::name('', $formData->get('preferredName'), $formData->get('surname'), 'Student');
        $studentGroup = $formData->has('formGroupName')? $formData->get('formGroupName') : $formData->get('yearGroupName');

        // Raise a new notification event for Admissions
        $event = new NotificationEvent('Admissions', 'Application Form Accepted');
        $notificationText = sprintf(__('An application form for %1$s (%2$s) has been accepted for the %3$s school year.'), $studentName, $studentGroup, $formData->get('schoolYearName'));
        $notificationText .= $formData->hasAll(['gibbonStudentEnrolmentID', 'gibbonFormGroupIDEntry'])
            ? ' '.__('The student has successfully been enrolled in the specified school year, year group and form group.')
            : ' '.__('Student could not be enrolled, so this will have to be done manually at a later date.');

        $event->addScope('gibbonYearGroupID', $formData->get('gibbonYearGroupIDEntry'));
        $event->addRecipient($this->session->get('organisationAdmissions'));
        $event->setNotificationText($notificationText);
        $event->setActionLink("/index.php?q=/modules/Admissions/applications_manage_edit.php&gibbonAdmissionsApplicationID=".$builder->getConfig('foreignTableID')."&gibbonSchoolYearID=".$formData->get('gibbonSchoolYearIDEntry')."&search=");

        $event->pushNotifications($this->notificationGateway, $this->notificationSender);

        // Raise a new notification event for SEN
        if ($formData->has('senDetails') || $formData->has('medicalInformation')) {
            $event = new NotificationEvent('Admissions', 'New Application with SEN/Medical');
            $event->addScope('gibbonYearGroupID', $formData->get('gibbonYearGroupIDEntry'));
            $event->setNotificationText(__('An application form has been accepted for {name} ({group}) with SEN or Medical needs. Please visit the student profile to review these details.', [
                'name' => $studentName,
                'group' => $studentGroup,
            ]));
            $event->setActionLink('/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$formData->get('gibbonPersonIDStudent').'&search=&allStudents=on');

            $event->pushNotifications($this->notificationGateway, $this->notificationSender);
        }

        $this->notificationSender->sendNotifications();
    }
}
