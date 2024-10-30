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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Module\Staff\Tables\CoverageDates;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Staff Coverage'), 'coverage_manage.php')
        ->add(__('Edit Coverage'));

    $page->return->addReturns([
            'success1' => __('Your request was completed successfully.'),
            'warning3' => __('This coverage request has already been accepted.'),
        ]);

    $gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';
    $gibbonStaffCoverageID = str_pad($gibbonStaffCoverageID, 14, 0, STR_PAD_LEFT);

    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    if (empty($gibbonStaffCoverageID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageGateway->getCoverageDetailsByID($gibbonStaffCoverageID);

    if (empty($coverage)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('staffCoverage', $session->get('absoluteURL').'/modules/Staff/coverage_manage_editProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $gibbonStaffCoverageID);

    $form->addHeaderAction('delete', __('Cancel').' '.__('Request'))
        ->setURL('/modules/Staff/coverage_manage_delete.php')
        ->addParam('gibbonStaffCoverageID', $gibbonStaffCoverageID)
        ->setIcon('iconCross')
        ->displayLabel();

    $form->addRow()->addHeading('Coverage Request', __('Coverage Request'));

    if (!empty($coverage['gibbonPersonID'])) {
        $staffCard = $container->get(StaffCard::class);
        $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);
    }

    if (!empty($coverage['gibbonStaffAbsenceID'])) {
        $row = $form->addRow();
            $row->addLabel('typeLabel', __('Type'));
            $row->addTextField('type')->readonly()->setValue($coverage['reason'] ? "{$coverage['type']} ({$coverage['reason']})" : $coverage['type']);
    }
    
    $row = $form->addRow();
        $row->addLabel('timestamp', __('Requested'));
        $row->addTextField('timestampValue')
            ->readonly()
            ->setValue(Format::relativeTime($coverage['timestampStatus'], false))
            ->setTitle($coverage['timestampStatus']);

    
    $row = $form->addRow();
        $row->addLabel('notesStatusLabel', __('Notes'));
        $row->addTextArea('notesStatus')->setRows(3)->setValue($coverage['notesStatus']);
    
    
    $form->addRow()->addHeading('Substitute', __('Substitute'));

    if ($coverage['requestType'] == 'Individual') {
        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDLabel', __('Person'));
            $row->addSelectUsers('gibbonPersonIDCoverage')
                ->placeholder()
                ->isRequired()
                ->selected($coverage['gibbonPersonIDCoverage'] ?? '')
                ->setReadonly(true);
    } else if ($coverage['requestType'] == 'Broadcast') {

        $row = $form->addRow();
            $row->addLabel('requestTypeLabel', __('Type'));
            $row->addTextField('requestType')->readonly()->setValue($coverage['requestType']);

        $notificationList = $coverage['notificationSent'] == 'Y' ? json_decode($coverage['notificationListAbsence'] ?? '') : [];

        if ($notificationList) {
            $notified = $container->get(UserGateway::class)->selectNotificationDetailsByPerson($notificationList)->fetchGroupedUnique();

            $row = $form->addRow();
                $row->addLabel('sentToLabel', __('Notified'));
                $row->addTextArea('sentTo')->readonly()->setValue(Format::nameList($notified, 'Staff', false, true, ', '));
        }
    }

    // Output the coverage status change timestamp, if it has been actioned
    if ($coverage['status'] != 'Requested' && !empty($coverage['timestampCoverage'])) {
        $row = $form->addRow();
        $row->addLabel('timestampCoverage', __($coverage['status']));
        $row->addTextField('timestampCoverageValue')
            ->readonly()
            ->setValue(Format::relativeTime($coverage['timestampCoverage'], false))
            ->setTitle($coverage['timestampCoverage']);
    }

    if (!empty($coverage['notesCoverage'])) {
        $row = $form->addRow();
            $row->addLabel('notesCoverageLabel', __('Comment'));
            $row->addTextArea('notesCoverage')->setRows(3)->readonly();
    }

    // DATA TABLE
    $table = $container->get(CoverageDates::class)->create($gibbonStaffCoverageID);

    $row = $form->addRow()->addContent($table->getOutput());

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    $form->loadAllValuesFrom($coverage);

    echo $form->getOutput();
}
