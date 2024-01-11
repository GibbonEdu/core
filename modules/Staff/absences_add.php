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

    $editLink = isset($_GET['editID'])
        ? $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_manage_edit.php&gibbonStaffAbsenceID='.$_GET['editID']
        : '';
    $page->return->setEditLink($editLink);
    $page->return->addReturns([
        'error8' => __('Your request failed.') .' '. __('The specified date is not in the future, or is not a school day.'),
    ]);

    $absoluteURL = $session->get('absoluteURL');
    $settingGateway = $container->get(SettingGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    // Get absence types & format them for the chained select lists
    $types = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();
    $typesRequiringApproval = $staffAbsenceTypeGateway->selectTypesRequiringApproval()->fetchAll(\PDO::FETCH_COLUMN, 0);

    $approverOptions = explode(',', $settingGateway->getSettingByScope('Staff', 'absenceApprovers') ?? '');
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
    $form = Form::create('staffAbsence', $session->get('absoluteURL').'/modules/Staff/absences_addProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    if ($highestAction == 'New Absence_any') {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID');
        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelectStaff('gibbonPersonID')->addClass('coverageField')->placeholder()->required()->selected($gibbonPersonID);
    } elseif ($highestAction == 'New Absence_mine') {
        $gibbonPersonID = $session->get('gibbonPersonID');
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
    }

    $row = $form->addRow();
        $row->addLabel('gibbonStaffAbsenceTypeID', __('Type'));
        $row->addSelect('gibbonStaffAbsenceTypeID')
            ->fromArray($types)
            ->placeholder()
            ->required();

    $form->toggleVisibilityByClass('reasonOptions')->onSelect('gibbonStaffAbsenceTypeID')->when($typesWithReasons);

    $row = $form->addRow()->addClass('reasonOptions');
        $row->addLabel('reason', __('Reason'));
        $row->addSelect('reason')
            ->fromArray($reasonsOptions)
            ->chainedTo('gibbonStaffAbsenceTypeID', $reasonsChained)
            ->placeholder()
            ->required();

    // DATES
    $date = $_GET['date'] ?? '';
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->addClass('coverageField')->chainedTo('dateEnd')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->addClass('coverageField')->chainedFrom('dateStart')->required()->setValue($date);

    $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->inline()
            ->setClass('coverageField')
            ->setValue('Y')
            ->checked('Y')
            ->wrap('<div class="standardWidth floatRight">', '</div>');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline');
        $col->addTime('timeStart')
            ->setClass('coverageField shortWidth')
            ->required();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('coverageField shortWidth')
            ->required();

    $col = $form->addRow()->addClass('schoolClosedOverride hidden')->addColumn();
        $col->addAlert(__('One or more selected dates are not a school day. Check here to confirm if you would like to include these dates.'), 'warning');
        $col->addCheckbox('schoolClosedOverride')
            ->description(__('Confirm'))
            ->setClass('text-right pr-1')
            ->setValue('Y');

    // APPROVAL
    if (!empty($typesRequiringApproval)) {
        // Pre-fill the last approver from the one most recently used
        $gibbonPersonIDApproval = $staffAbsenceGateway->getMostRecentApproverByPerson($gibbonPersonID);

        $form->toggleVisibilityByClass('approvalRequired')->onSelect('gibbonStaffAbsenceTypeID')->when($typesRequiringApproval);
        $form->addRow()->addClass('approvalRequired')->addHeading('Requires Approval', __('Requires Approval'))->addClass('approvalRequired');

        $row = $form->addRow()->addClass('approvalRequired');
        $row->addLabel('gibbonPersonIDApproval', __('Approver'));
        $row->addSelectUsersFromList('gibbonPersonIDApproval', $approverOptions)
            ->placeholder()
            ->required()
            ->selected($gibbonPersonIDApproval ?? '');

        $row = $form->addRow()->addClass('approvalRequired');
            $row->addLabel('commentConfidential', __('Confidential Comment'))->description(__('This message is only shared with the selected approver.'));
            $row->addTextArea('commentConfidential')->setRows(3);
    }

    // NOTIFICATIONS
    $form->addRow()->addHeading('Notifications', __('Notifications'));

    $row = $form->addRow()->addClass('approvalRequired hidden');
        $row->addAlert(__('The following people will only be notified if this absence is approved.'), 'message');

    // Get the most recent absence and pre-fill the notification group & list of people
    $recentAbsence = $staffAbsenceGateway->getMostRecentAbsenceByPerson($gibbonPersonID);

    $notificationSetting = $settingGateway->getSettingByScope('Staff', 'absenceNotificationGroups');
    $notificationGroups = $container->get(GroupGateway::class)->selectGroupsByIDList($notificationSetting)->fetchKeyPair();

    if (!empty($notificationGroups)) {
        $row = $form->addRow();
            $row->addLabel('gibbonGroupID', __('Automatically Notify'));
            $row->addSelect('gibbonGroupID')->fromArray($notificationGroups)->required()->selected($recentAbsence['gibbonGroupID'] ?? '');
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
            ->fromAjax($session->get('absoluteURL').'/modules/Staff/staff_searchAjax.php')
            ->selected($notified)
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 rounded-full bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.image + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.jobTitle + "</span></div></li>"; }');

    $commentTemplate = $settingGateway->getSettingByScope('Staff', 'absenceCommentTemplate');
    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'))->description(__('This message is shared with the people notified of this absence and users who manage staff absences.'));
        $row->addTextArea('comment')->setRows(5)->setValue($commentTemplate);

    // COVERAGE
    if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php')) {
        $form->toggleVisibilityByClass('coverageRequest')->onInput('dateStart')->whenNot('');

        $form->addRow()->addClass('coverageRequest')->addHeading(__('Coverage'))->addClass('coverageRequest');

        $row = $form->addRow()->addClass('coverageRequest');
            $row->addLabel('coverageRequired', __('Substitute Required'));
            $row->addYesNo('coverageRequired')->required()->placeholder();

        $form->addRow()->setClass('hidden coverageRequestForm p-0');

        // $form->toggleVisibilityByClass('coverageOptions')->onSelect('coverageRequired')->whenNot('N');

        // if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php')) {
        //     $col = $form->addRow()->addClass('coverageOptions')->addColumn();
        //         $col->addAlert(__("You'll have the option to send a coverage request after submitting this form."), 'success');
        // }
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
    // ABSENCE
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

    // COVERAGE
    $('#coverageRequired, .coverageField').on('change', function() {
        if ($('#coverageRequired').val() == 'Y') {
            $('.coverageRequestForm').removeClass('hidden').html('<div class="w-full flex items-center justify-center h-32"><img class="align-middle w-56 -mt-px" src="./themes/Default/img/loading.gif"></div>');
            $('.coverageRequestForm').load('./modules/Staff/coverage_requestAjax.php', {
                'gibbonStaffAbsenceTypeID': $('#gibbonStaffAbsenceTypeID').val(),
                'gibbonPersonID': $('#gibbonPersonID').val() ?? "<?php echo $gibbonPersonID; ?>",
                'dateStart': $('#dateStart').val(),
                'dateEnd': $('#dateEnd').val(),
                'allDay': $('input[name=allDay]:checked').val(),
                'timeStart': $('#timeStart').val(),
                'timeEnd': $('#timeEnd').val(),
            }, function(result) {
                console.log('loaded');
                $('input[name="timetableClasses[]"]').trigger('change');
                $('input[name="requestDates[]"]').trigger('change');
            });
        } else {
            $('.coverageRequestForm').addClass('hidden').html('');
        }
    });

    $(document).on('change', 'input[name="timetableClasses[]"],input[name="requestDates[]"]', function() {
        var checkbox = this;
        $(this).parents('tr').find('.individualOptions.personSelect').each(function() {
            $(this).toggle($(checkbox).prop("checked"));
        });

        $(this).parents('tr').find('.coverageNotes').each(function() {
            $(this).toggle($(checkbox).prop("checked"));
        });

    });

    $('input[name="timetableClasses[]"],input[name="requestDates[]"]').trigger('change');

    $(document).on('change', '#requestType', function() {
        $('input[name="timetableClasses[]"],input[name="requestDates[]"]').trigger('change');
    });
}) ;
</script>
