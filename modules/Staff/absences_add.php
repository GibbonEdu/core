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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('New Absence'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (isset($_GET['return'])) {
        $editLink = isset($_GET['editID'])
            ? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/absences_manage_edit.php&gibbonStaffAbsenceID='.$_GET['editID']
            : '';
        returnProcess($guid, $_GET['return'], $editLink, [
            'error8' => __('Your request failed.') .' '. __('The specified date is not in the future, or is not a school day.'),
        ]);
    }

    $absoluteURL = $gibbon->session->get('absoluteURL');
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    // Get absence types & format them for the chained select lists
    $types = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();
    $typesRequiringApproval = $staffAbsenceTypeGateway->selectTypesRequiringApproval()->fetchAll(\PDO::FETCH_COLUMN, 0);

    $approverOptions = explode(',', getSettingByScope($connection2, 'Staff', 'absenceApprovers'));
    $typesWithReasons = $reasonsOptions = $reasonsChained = [];

    $types = array_reduce($types, function ($group, $item) use (&$reasonsOptions, &$reasonsChained, &$typesWithReasons) {
        $id = $item['gibbonStaffAbsenceTypeID'];
        $group[$id] = $item['name'];
        $reasons = array_filter(array_map('trim', explode(',', $item['reasons'])));
        if (!empty($reasons)) {
            $typesWithReasons[] = $id;
            foreach ($reasons as $reason) {
                $reasonsOptions[$reason] = $reason;
                $reasonsChained[$reason] = $id;
            }
        }
        return $group;
    }, []);

    // FORM
    $form = Form::create('staffAbsence', $_SESSION[$guid]['absoluteURL'].'/modules/Staff/absences_addProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Basic Information'));

    if ($highestAction == 'New Absence_any') {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? $_SESSION[$guid]['gibbonPersonID'];
        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectStaff('gibbonPersonID')->placeholder()->isRequired()->selected($gibbonPersonID);
    } elseif ($highestAction == 'New Absence_mine') {
        $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
    }

    $row = $form->addRow();
        $row->addLabel('gibbonStaffAbsenceTypeID', __('Type'));
        $row->addSelect('gibbonStaffAbsenceTypeID')
            ->fromArray($types)
            ->placeholder()
            ->isRequired();

    $form->toggleVisibilityByClass('reasonOptions')->onSelect('gibbonStaffAbsenceTypeID')->when($typesWithReasons);

    $row = $form->addRow()->addClass('reasonOptions');
        $row->addLabel('reason', __('Reason'));
        $row->addSelect('reason')
            ->fromArray($reasonsOptions)
            ->chainedTo('gibbonStaffAbsenceTypeID', $reasonsChained)
            ->placeholder()
            ->isRequired();

    // DATES
    $date = $_GET['date'] ?? '';
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->isRequired()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->isRequired()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->inline()
            ->setClass()
            ->setValue('Y')
            ->checked('Y')
            ->wrap('<div class="standardWidth floatRight">', '</div>');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline');
        $col->addTime('timeStart')
            ->setClass('shortWidth')
            ->isRequired();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('shortWidth')
            ->isRequired();

    $col = $form->addRow()->addClass('schoolClosedOverride hidden')->addColumn();
        $col->addAlert(__('One or more selected dates are not a school day. Check here to confirm if you would like to continue submitting this form.'), 'warning');
        $col->addCheckbox('schoolClosedOverride')
            ->description(__('Confirm'))
            ->setClass('text-right pr-1')
            ->setValue('Y');

    // APPROVAL
    if (!empty($typesRequiringApproval)) {
        $form->toggleVisibilityByClass('approvalRequired')->onSelect('gibbonStaffAbsenceTypeID')->when($typesRequiringApproval);
        $form->toggleVisibilityByClass('approvalNotRequired')->onSelect('gibbonStaffAbsenceTypeID')->whenNot(array_merge($typesRequiringApproval, ['Please select...']));

        // Pre-fill the last approver from the one most recently used
        $gibbonPersonIDApproval = $staffAbsenceGateway->getMostRecentApproverByPerson($gibbonPersonID);

        $form->addRow()->addHeading(__('Requires Approval'))->addClass('approvalRequired');

        $row = $form->addRow()->addClass('approvalRequired');
        $row->addLabel('gibbonPersonIDApproval', __('Approver'));
        $row->addSelectUsersFromList('gibbonPersonIDApproval', $approverOptions)
            ->placeholder()
            ->isRequired()
            ->selected($gibbonPersonIDApproval ?? '');

        $row = $form->addRow()->addClass('approvalRequired');
            $row->addLabel('commentConfidential', __('Confidential Comment'))->description(__('This message is only shared with the selected approver.'));
            $row->addTextArea('commentConfidential')->setRows(3);
    }

    // NOTIFICATIONS
    $form->addRow()->addHeading(__('Notifications'));

    $row = $form->addRow()->addClass('approvalRequired hidden');
        $row->addAlert(__('The following people will only be notified if this absence is approved.'), 'message');

    // Get the most recent absence and pre-fill the notification group & list of people
    $recentAbsence = $staffAbsenceGateway->getMostRecentAbsenceByPerson($gibbonPersonID);
    
    $notificationSetting = $container->get(SettingGateway::class)->getSettingByScope('Staff', 'absenceNotificationGroups');
    $notificationGroups = $container->get(GroupGateway::class)->selectGroupsByIDList($notificationSetting)->fetchKeyPair();

    if (!empty($notificationGroups)) {
        $row = $form->addRow();
            $row->addLabel('gibbonGroupID', __('Automatically Notify'));
            $row->addSelect('gibbonGroupID')->fromArray($notificationGroups)->isRequired()->selected($recentAbsence['gibbonGroupID']);
    }

    // Format user details into token-friendly list
    $notificationList = !empty($recentAbsence['notificationList'])? json_decode($recentAbsence['notificationList']) : [];
    $notified = $container->get(UserGateway::class)->selectNotificationDetailsByPerson($notificationList)->fetchGroupedUnique();
    $notified = array_map(function ($token) use ($absoluteURL) {
        return [
            'id'       => $token['gibbonPersonID'],
            'name'     => Format::name('', $token['preferredName'], $token['surname'], 'Staff', false, true),
            'jobTitle' => !empty($token['jobTitle']) ? $token['jobTitle'] : $token['type'],
            'image'    => $absoluteURL.'/'.$token['image_240'],
        ];
    }, $notified);

    $row = $form->addRow();
        $row->addLabel('notificationList', __('Notify Additional People'))->description(__('Your notification choices are saved and pre-filled for future absences.'));
        $row->addFinder('notificationList')
            ->fromAjax($gibbon->session->get('absoluteURL').'/modules/Staff/staff_searchAjax.php')
            ->selected($notified)
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 rounded-full bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.image + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.jobTitle + "</span></div></li>"; }');

    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'))->description(__('This message is shared with the people notified of this absence and users who manage staff absences.'));
        $row->addTextArea('comment')->setRows(3);

    // COVERAGE
    if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php')) {
        $form->addRow()->addHeading(__('Coverage'))->addClass('approvalNotRequired');

        $row = $form->addRow()->addClass('approvalNotRequired');
            $row->addLabel('coverageRequired', __('Substitute Required'));
            $row->addYesNo('coverageRequired')->isRequired()->selected('N');
    
        $form->toggleVisibilityByClass('coverageOptions')->onSelect('coverageRequired')->whenNot('N');
            
        $row = $form->addRow()->addClass('coverageOptions approvalNotRequired');
            $row->addAlert(__("You'll have the option to send a coverage request after submitting this form."), 'success');
    } else {
        $form->addHiddenValue('coverageRequired', 'N');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function() {
    $('#dateStart, #dateEnd').on('change', function() {
        $.ajax({
            url: "./modules/Staff/absences_addAjax.php",
            data: {
                'dateStart': $('#dateStart').val(),
                'dateEnd': $('#dateEnd').val(),
            },
            type: 'POST',
            success: function(data) {
                if (data === '0') {
                    $('.schoolClosedOverride').removeClass('hidden');
                    $('#schoolClosedOverride').prop('disabled', false);
                } else {
                    $('.schoolClosedOverride').addClass('hidden');
                    $('#schoolClosedOverride').prop('disabled', true);
                }
            }
        });
    });
}) ;
</script>
