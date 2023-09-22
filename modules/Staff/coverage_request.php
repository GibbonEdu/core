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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Timetable\TimetableGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Module\Staff\Forms\CoverageRequestForm;
use Gibbon\Module\Staff\Tables\AbsenceDates;
use Gibbon\Module\Staff\View\StaffCard;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('New Coverage Request'));

    $page->return->addReturns([
            'success1' => __('Your request was completed successfully.').' '.__('You may now continue by submitting a coverage request for this absence.'),
            'error8' => __('Your request failed because no dates have been selected. Please check your input and submit your request again.'),
        ]);

    $gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';
    $gibbonStaffAbsenceDateID = $_GET['gibbonStaffAbsenceDateID'] ?? '';

    $settingGateway = $container->get(SettingGateway::class);
    $substituteGateway = $container->get(SubstituteGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    if (empty($gibbonStaffAbsenceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get absence details
    $values = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);
    $absenceDates = $staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID)->fetchAll();

    if (!empty($gibbonStaffAbsenceDateID)) {
        $absenceDates = array_filter($absenceDates, function ($item) {
            return $item['date'] >= date('Y-m-d');
        });
    }

    if (empty($values) || empty($absenceDates)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get coverage mode
    $coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    if ($values['status'] != 'Approved' && $coverageMode == 'Requested') {
        $page->addMessage(__('Coverage may only be requested for an absence after it has been approved.'));
        return;
    }

    // Get date ranges
    $dateStart = !empty($absenceDates) ? current($absenceDates) : [];
    $dateEnd = !empty($absenceDates) ? end($absenceDates) : [];

    // Staff Card
    if ($values['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $staffCard = $container->get(StaffCard::class);
        $staffCard->setPerson($values['gibbonPersonID'])->compose($page);
    }

    // Coverage Request
    $form = $container->get(CoverageRequestForm::class)->createForm($values['gibbonPersonID'], $dateStart['date'], $dateEnd['date'], $dateStart['allDay'], $dateStart['timeStart'], $dateStart['timeEnd']);

    $form->setTitle(__('Coverage Request'));
    $form->setAction($session->get('absoluteURL').'/modules/Staff/coverage_requestProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID ?? '');

    $row = $form->addRow()->addClass('coverageSubmit');
        $row->addSubmit()->prepend('<div class="coverageNoSubmit inline text-right text-xs text-gray-600 italic pr-1">'.__('Select at least one date and/or time before continuing.').'</div>');

    $page->write($form->getOutput());
}
?>

<script>

checkSelections = function ()
{
    // Prevent clicking submit until at least one date (and sub) has been selected
    var datesChecked = $('input[name="timetableClasses[]"]:checked');
    var subsChecked = $('.personSelect').filter(function () {
        return $(this).val() != '';
    }).length;

    if (datesChecked === undefined || datesChecked.length <= 0 || ($('#requestType').val() == 'Individual' && subsChecked <= 0 ) ) {
        $('.coverageNoSubmit').show();
        $('.coverageSubmit :input').prop('disabled', true);
    } else {
        $('.coverageNoSubmit').hide();
        $('.coverageSubmit :input').prop('disabled', false);
    }
}

$(document).ready(function() {

    $(document).on('change', '.personSelect', function() {
        checkSelections();
    });

    $(document).on('change', 'input[name="timetableClasses[]"]', function() {
        var checkbox = this;
        $(this).parents('tr').find('.individualOptions.personSelect').each(function() {
            $(this).toggle($(checkbox).prop("checked"));
        });

        $(this).parents('tr').find('.coverageNotes').each(function() {
            $(this).toggle($(checkbox).prop("checked"));
        });

        checkSelections();
    });

    $('input[name="timetableClasses[]"]').trigger('change');
    checkSelections();

    $(document).on('change', '#requestType', function() {
        $('input[name="timetableClasses[]"]').trigger('change');
    });
}) ;
</script>
