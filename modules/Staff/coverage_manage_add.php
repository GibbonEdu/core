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
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Staff Coverage'), 'coverage_manage.php')
        ->add(__('Add Coverage'));

    $editLink = isset($_GET['editID'])
        ? $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_manage_edit.php&gibbonStaffCoverageID='.$_GET['editID']
        : '';
    $page->return->setEditLink($editLink);

    $substituteGateway = $container->get(SubstituteGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    $criteria = $substituteGateway->newQueryCriteria(true)
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('active', 'Y')
        ->filterBy('status', 'Full');

    $availableSubs = $substituteGateway->queryAllSubstitutes($criteria)->toArray();

    $availableSubs = array_reduce($availableSubs, function ($group, $person) {
        $group[$person['gibbonPersonID']] = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
        return $group;
    }, []);

    $form = Form::create('staffAbsenceEdit', $session->get('absoluteURL').'/modules/Staff/coverage_manage_addProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Add Coverage', __('Add Coverage'));

    $row = $form->addRow();
        $row->addAlert(__("This option lets you add general coverage for a substitute that is not associated with a staff absence. This can be useful if they are covering an activity or event rather than a particular absence."), 'message');

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
            ->wrap('<div class="standardWidth floatRight">', '</div>');

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('time', __('Time'));
        $col = $row->addColumn('time')->addClass('right inline');
        $col->addTime('timeStart')
            ->setClass('shortWidth')
            ->isRequired();
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('shortWidth')
            ->isRequired();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDCoverage', __('Substitute'));
        if ($internalCoverage == 'Y') {
            $row->addSelectStaff('gibbonPersonIDCoverage')
            ->placeholder()
            ->isRequired();
        } else {
            $row->addSelectPerson('gibbonPersonIDCoverage')
                ->fromArray($availableSubs)
                ->placeholder()
                ->isRequired();
        }
        

    // Loaded via AJAX
    $row = $form->addRow();
        $row->addContent('<div class="datesTable"></div>');

    $form->toggleVisibilityByClass('subSelected')->onSelect('gibbonPersonIDCoverage')->whenNot('Please select...');

    $row = $form->addRow()->addClass('subSelected');
        $row->addLabel('gibbonPersonID', __('Created For'));
        $row->addSelectStaff('gibbonPersonID')
            ->placeholder()
            ->selected($session->get('gibbonPersonID'))
            ->isRequired();

    $statusOptions = [
        'Accepted'  => __('Assign'),
        'Requested' => __('Request'),
    ];
    $row = $form->addRow()->addClass('subSelected');
        $row->addLabel('status', __('Type'));
        $row->addSelect('status')->fromArray($statusOptions)->isRequired();

    $row = $form->addRow()->addClass('subSelected');
        $row->addLabel('reason', __('Reason'));
        $row->addTextField('reason')->maxLength(30);

    $row = $form->addRow()->addClass('subSelected');
        $row->addLabel('notesStatus', __('Comment'))->description(__('This message is shared with substitutes, and is also visible to users who manage staff coverage.'));
        $row->addTextArea('notesStatus')->setRows(3);

    $row = $form->addRow()->addClass('coverageSubmit');
        $row->addSubmit()->prepend('<div class="coverageNoSubmit inline text-right text-xs text-gray-600 italic pr-1">'.__('Select a substitute and at least one date before continuing.').'</div>');

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function() {
    $('#gibbonPersonIDCoverage, #dateStart, #dateEnd, #allDay, #timeStart, #timeEnd').on('change', function() {
        $('.datesTable').load('./modules/Staff/coverage_manage_addAjax.php', {
            'allDay': $('input[name=allDay]:checked').val(),
            'dateStart': $('#dateStart').val(),
            'dateEnd': $('#dateEnd').val(),
            'timeStart': $('#timeStart').val(),
            'timeEnd': $('#timeEnd').val(),
            'gibbonPersonIDCoverage': $('#gibbonPersonIDCoverage').val(),
        }, function() {
            // Pre-highlight selected rows
            $('.bulkActionForm').find('.bulkCheckbox :checkbox').each(function () {
                $(this).closest('tr').toggleClass('selected', $(this).prop('checked'));
            });

            $('#gibbonPersonID').trigger('change');
        });
    });

    // Individual requests: Prevent clicking submit until at least one date has been selected
    $(document).on('change', '#gibbonPersonID, input[name="requestDates[]"]', function() {
        var checked = $('input[name="requestDates[]"]:checked');

        if (checked.length <= 0) {
            $('.coverageNoSubmit').show();
            $('.coverageSubmit :input').prop('disabled', true);
        } else {
            $('.coverageNoSubmit').hide();
            $('.coverageSubmit :input').prop('disabled', false);
        }
    });
    
    $('#gibbonPersonID').trigger('change');
}) ;
</script>
