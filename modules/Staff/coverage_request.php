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
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('New Coverage Request'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, [
            'success1' => __('Your request was completed successfully.').' '.__('You may now continue by submitting a coverage request for this absence.'),
            'error8' => __('Your request failed because no dates have been selected. Please check your input and submit your request again.'),
        ]);
    }

    $gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';
    $gibbonPersonIDCoverage = $_GET['gibbonPersonIDCoverage'] ?? '';

    $substituteGateway = $container->get(SubstituteGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);

    if (empty($gibbonStaffAbsenceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);
    $absenceDates = $staffAbsenceDateGateway->selectDatesByAbsence($gibbonStaffAbsenceID)->fetchAll();

    if (empty($values) || empty($absenceDates)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($values['status'] != 'Approved') {
        $page->addError(__('Coverage may only be requested for an absence after it has been approved.'));
        return;
    }
    
    // Look for available subs
    $criteria = $substituteGateway->newQueryCriteria()
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName']);

    $availableSubs = array_reduce($absenceDates, function ($group, $date) use ($substituteGateway, &$criteria) {
        $availableByDate = $substituteGateway->queryAvailableSubsByDate($criteria, $date['date'], $date['timeStart'], $date['timeEnd'])->toArray();
        return array_merge($group, $availableByDate);
    }, []);

    // Group subs by person
    $availableSubs = array_reduce($availableSubs, function ($group, $item) {
        $group[$item['gibbonPersonID']] = $item;
        return $group;
    }, []);

    // Re-sort the grouped results by priority and surname
    uasort($availableSubs, function ($a, $b) {
        return $b['priority'] != $a['priority']
            ? $b['priority'] <=> $a['priority']
            : $a['surname'] <=> $b['surname'];
    });

    // Map sub names for Select list
    $availableSubsOptions = array_reduce($availableSubs, function ($group, $item) {
        $group[$item['type']][$item['gibbonPersonID']] = Format::name($item['title'], $item['preferredName'], $item['surname'], 'Staff', true, true);
        return $group;
    }, []);

    // Build a list of available subs by type
    $countTypes = [];
    $availableSubsByType = array_reduce($availableSubs, function ($group, $item) use (&$countTypes) {
        $countTypes[$item['type']] = isset($countTypes[$item['type']])? $countTypes[$item['type']] + 1 : 1;
        $group[$item['type']] = $item['type']." ({$countTypes[$item['type']]})";
        return $group;
    }, []);

    $form = Form::create('staffAbsenceEdit', $_SESSION[$guid]['absoluteURL'].'/modules/Staff/coverage_requestProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $form->addRow()->addHeading(__('Coverage Request'));

    $requestTypes = ['Broadcast'  => __('Any available substitute')];

    if (!empty($availableSubs)) {
        $requestTypes['Individual'] = __('Specific substitute');
    }

    $dateStart = $absenceDates[0] ?? '';
    $dateEnd = $absenceDates[count($absenceDates) -1] ?? '';
    $dateRange = Format::dateRangeReadable($dateStart['date'], $dateEnd['date']);
    $timeRange = $dateStart['allDay'] == 'N'
        ? Format::timeRange($dateStart['timeStart'], $dateStart['timeEnd'])
        : '';

    $row = $form->addRow();
        $row->addLabel('dateLabel', __('Absence'));
        $row->addTextField('date')->readonly()->setValue($dateRange.' '.$timeRange);

    $row = $form->addRow();
        $row->addLabel('requestType', __('Substitute Required'));
        $row->addSelect('requestType')->isRequired()->fromArray($requestTypes)->selected('Broadcast');

    $form->toggleVisibilityByClass('individualOptions')->onSelect('requestType')->when('Individual');
    $form->toggleVisibilityByClass('broadcastOptions')->onSelect('requestType')->when('Broadcast');

        
    // Broadcast
    $row = $form->addRow()->addClass('broadcastOptions');
    if (!empty($availableSubs)) {
        $row->addAlert(__("This option sends a request out to all available substitutes. There are currently {count} substitutes with availability for this time period. You'll receive a notification once your request is accepted.", ['count' => '<b>'.count($availableSubs).'</b>']), 'message');

        // If there's more than one sub type, allow users to direct their broadcast request to a specific type.
        // All substitute types are selected by default.
        $allSubsTypes = $container->get(SettingGateway::class)->getSettingByScope('Staff', 'substituteTypes');
        $allSubsTypes = array_filter(array_map('trim', explode(',', $allSubsTypes)));
        if (count($allSubsTypes) > 1) {
            $row = $form->addRow()->addClass('broadcastOptions');
            $row->addLabel('substituteTypes', __('Substitute Types'));
            $row->addCheckbox('substituteTypes')->fromArray($availableSubsByType)->checkAll()->wrap('<div class="standardWidth floatRight">', '</div>');
        }
    } else {
        $row->addAlert(__("There are no substitutes currently available for this time period. You should still send a request, as sub availability may change, but you cannot select a specific sub at this time. A notification will be sent to admin."), 'warning');
    }

    // Individual
    $row = $form->addRow()->addClass('individualOptions');
        $row->addAlert(__("This option sends your request to the selected substitute. You'll receive a notification when they accept or decline. If your request is declined you'll have to option to send a new request."), 'message');

    $row = $form->addRow()->addClass('individualOptions');
        $row->addLabel('gibbonPersonIDCoverage', __('Substitute'))->description(__('Only available substitutes are listed here.'));
        $row->addSelectPerson('gibbonPersonIDCoverage')
            ->fromArray($availableSubsOptions)
            ->placeholder()
            ->selected($gibbonPersonIDCoverage)
            ->isRequired();

    $sql = "SELECT DATE_FORMAT(schoolStart, '%H:%i') as schoolStart, DATE_FORMAT(schoolEnd, '%H:%i') as schoolEnd FROM gibbonDaysOfWeek WHERE name=DATE_FORMAT(CURRENT_DATE, '%W') AND schoolDay='Y'";
    $weekday = $pdo->selectOne($sql);

    // Time Options
    if ($dateStart['allDay'] == 'Y') {
        $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addCheckbox('allDay')
            ->description(__('All Day'))
            ->setValue('Y')
            ->checked($dateStart['allDay']);
    } else {
        $form->addHiddenValue('allDay', 'N');
    }

    $form->toggleVisibilityByClass('timeOptions')->onCheckbox('allDay')->whenNot('Y');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline');
        $col->addTime('timeStart')
            ->setClass('shortWidth')
            ->isRequired()
            ->setValue($dateStart['timeStart'] ?? $weekday['schoolStart']);
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('shortWidth')
            ->isRequired()
            ->setValue($dateStart['timeEnd'] ?? $weekday['schoolEnd']);

    // Loaded via AJAX
    $row = $form->addRow()->addClass('individualOptions');
        $row->addContent('<div class="datesTable"></div>');

    $row = $form->addRow();
        $row->addLabel('notesStatus', __('Comment'))->description(__('This message is shared with substitutes, and is also visible to users who manage staff coverage.'));
        $row->addTextArea('notesStatus')->setRows(3);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function() {
    $('#gibbonPersonIDCoverage, #allDay, #timeStart, #timeEnd').on('change', function() {
        if ($('#gibbonPersonIDCoverage').val() == '') return;

        $('.datesTable').load('./modules/Staff/coverage_requestAjax.php', {
            'gibbonStaffAbsenceID': '<?php echo $gibbonStaffAbsenceID ?? ''; ?>',
            'gibbonPersonIDCoverage': $('#gibbonPersonIDCoverage').val(),
            'allDay': $('input[name=allDay]:checked').val(),
            'timeStart': $('#timeStart').val(),
            'timeEnd': $('#timeEnd').val(),
        }, function() {
            // Pre-highlight selected rows
            $('.bulkActionForm').find('.bulkCheckbox :checkbox').each(function () {
                $(this).closest('tr').toggleClass('selected', $(this).prop('checked'));
            });
        });
    });

}) ;
</script>
