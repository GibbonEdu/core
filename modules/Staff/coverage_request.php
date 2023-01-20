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
    $gibbonPersonIDCoverage = $_GET['gibbonPersonIDCoverage'] ?? '';

    $settingGateway = $container->get(SettingGateway::class);
    $substituteGateway = $container->get(SubstituteGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    if (empty($gibbonStaffAbsenceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);
    $absenceDates = $staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID)->fetchAll();

    if (empty($values) || empty($absenceDates)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($values['status'] != 'Approved' && $coverageMode == 'Requested') {
        $page->addMessage(__('Coverage may only be requested for an absence after it has been approved.'));
        return;
    }

    // Get coverage mode
    $coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    $canSelectSubstitutes = $coverageMode == 'Requested' && $values['status'] == 'Approved';
    $classesNeedingCover = 0;
    
    // Get date ranges
    $dateStart = $absenceDates[0] ?? '';
    $dateEnd = $absenceDates[count($absenceDates) -1] ?? '';
    $dateRange = Format::dateRangeReadable($dateStart['date'], $dateEnd['date']);
    $timeRange = $dateStart['allDay'] == 'N'
        ? Format::timeRange($dateStart['timeStart'], $dateStart['timeEnd'])
        : '';

    // Get timetabled classes and non-class records that need coverage (activities and duty)
    $classes = $staffCoverageDateGateway->selectPotentialCoverageByPersonAndDate($session->get('gibbonSchoolYearID'), $values['gibbonPersonID'], $dateStart['date'], $dateEnd['date'])->fetchAll();
    $classes = array_map(function ($item) {
        $item['contextCheckboxID'] = $item['date'].':'.$item['foreignTable'].':'.$item['foreignTableID'];
        return $item;
    }, $classes);

    // Check for special days for these classes
    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
    $specialDays = $specialDayGateway->selectSpecialDaysByDateRange($dateStart['date'], $dateEnd['date'])->fetchGroupedUnique();

    // Check if classes are running on a special day
    $classes = array_reduce($classes, function ($group, $item) use (&$specialDayGateway, &$session, &$specialDays, &$classesNeedingCover) {
        $item['offTimetable'] = false;

        if ($item['context'] == 'Class' && isset($specialDays[$item['date']])) {
            $item['offTimetable'] = $specialDayGateway->getIsClassOffTimetableByDate($session->get('gibbonSchoolYearID'), $item['contextID'], $item['date']);
        }

        if ($item['context'] == 'Activity' && isset($specialDays[$item['date']])) {
            $item['offTimetable'] = $specialDays[$item['date']]['cancelActivities'] == 'Y';
        }

        if (!$item['offTimetable']) {
            $classesNeedingCover += $item['coverage'] != 'Requested' && $item['coverage'] != 'Pending' && $item['coverage'] != 'Accepted' ? 1 : 0;
        }

        $group[] = $item;
        return $group;
    }, []);

    $coverageByTimetable = !empty($classes);

    // Look for available subs
    $criteria = $substituteGateway->newQueryCriteria()
        ->filterBy('allStaff', $internalCoverage == 'Y')
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName']);

    $availabilityCount = 0;
    $availableSubs = [];

    foreach ($coverageByTimetable ? $classes : $absenceDates as $index => $date) {
        if (!empty($date['gibbonStaffCoverageID'])) continue; // Already covered

        $availableByDate = $substituteGateway->queryAvailableSubsByDate($criteria, $date['date'], $date['timeStart'], $date['timeEnd'])->toArray();
        $availabilityCount += !empty($availableByDate)? 1 : 0;

        $availableSubs = array_merge($availableSubs, $availableByDate);

        if ($coverageByTimetable) {
            $classes[$index]['availability'] = $availableByDate;
        }
    }

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

    // FORM
    $form = Form::create('staffAbsenceEdit', $session->get('absoluteURL').'/modules/Staff/coverage_requestProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffAbsenceID', $gibbonStaffAbsenceID);

    $form->addRow()->addHeading('Coverage Request', __('Coverage Request'));
        
    $row = $form->addRow();
        $row->addLabel('dateLabel', __('Absence'));
        $row->addTextField('date')->readonly()->setValue($dateRange.' '.$timeRange);

    if ($canSelectSubstitutes) {

        $requestTypes = ['Broadcast'  => __('Any available substitute')];

        if (!empty($availableSubs) || ($coverageByTimetable && $availabilityCount > 0)) {
            $requestTypes['Individual'] = __('Specific substitute');
        }

        $row = $form->addRow();
            $row->addLabel('requestType', __('Substitute Required'));
            $row->addSelect('requestType')->isRequired()->fromArray($requestTypes)->selected('Broadcast');

        $form->toggleVisibilityByClass('individualOptions')->onSelect('requestType')->when('Individual');
        $form->toggleVisibilityByClass('broadcastOptions')->onSelect('requestType')->when('Broadcast');
            
        // Broadcast
        if (!empty($availableSubs)) {
            $col = $form->addRow()->addClass('broadcastOptions')->addColumn();
            $col->addAlert(__("This option sends a request out to all available substitutes. There are currently {count} substitutes with availability for this time period. You'll receive a notification once your request is accepted.", ['count' => Format::bold(count($availableSubs))]), 'message');
            
            if ($coverageByTimetable && $classesNeedingCover > $availabilityCount) {
                $col->addAlert(__("There are currently no available substitutes for {count} of the following classes.", ['count' => Format::bold($classesNeedingCover - $availabilityCount)]).' '.__('A notification will be sent to administration.'), 'warning');
            }

            // If there's more than one sub type, allow users to direct their broadcast request to a specific type.
            // All substitute types are selected by default.
            if (count($availableSubsByType) > 1) {
                $row = $form->addRow()->addClass('broadcastOptions');
                $row->addLabel('substituteTypes', __('Substitute Types'));
                $row->addCheckbox('substituteTypes')->fromArray($availableSubsByType)->checkAll()->wrap('<div class="standardWidth floatRight">', '</div>');
            }
        } else {
            $row = $form->addRow()->addClass('broadcastOptions');
            $row->addAlert(__("There are no substitutes currently available for this time period. You should still send a request, as substitute availability may change, but you cannot select a specific substitute at this time. A notification will be sent to administration."), 'warning');
        }

        // Individual
        $col = $form->addRow()->addClass('individualOptions')->addColumn();
            $col->addAlert(__("This option sends your request to the selected substitute. You'll receive a notification when they accept or decline. If your request is declined you'll have to option to send a new request."), 'message');

        if ($coverageByTimetable && $classesNeedingCover > $availabilityCount) {
            $col->addAlert(__("There are currently no available substitutes for {count} of the following classes.", ['count' => Format::bold($classesNeedingCover - $availabilityCount)]).' '.__('You can still send a request, as substitute availability may change, but you cannot select a specific substitute for these classes.').' '.__('A notification will be sent to administration.'), 'warning');
        }
    } else {
        $form->addHiddenValue('requestType', $coverageByTimetable ? 'Individual' : 'Broadcast');
    }

    $sql = "SELECT DATE_FORMAT(schoolStart, '%H:%i') as schoolStart, DATE_FORMAT(schoolEnd, '%H:%i') as schoolEnd FROM gibbonDaysOfWeek WHERE name=DATE_FORMAT(CURRENT_DATE, '%W') AND schoolDay='Y'";
    $weekday = $pdo->selectOne($sql);

    $col = $form->addRow()->addColumn();
    $col->addLabel('when', __('When'));

    $table = $col->addDataTable('staffAbsenceDates')->withData(new DataSet($coverageByTimetable ? $classes : $absenceDates));
    $table->getRenderer()->addData('class', 'bulkActionForm mt-2');

    $table->modifyRows(function ($class, $row) {
        if (!empty($class['gibbonStaffCoverageID'])) $row->addClass('dull');
        return $row->addClass('h-16');
    });

    $table->addColumn('dateLabel', __('Date'))
        ->format(Format::using('dateReadable', 'date'))
        ->formatDetails(function ($coverage) {
            return Format::small(Format::dateReadable($coverage['date'], '%A'));
        });

    if ($coverageByTimetable) {
        $table->addColumn('period', __('Period'));
        $table->addColumn('contextName', __('Cover'));
        $table->addColumn('timeStart', __('Time'))
            ->format(function ($class) {
                return Format::small(Format::timeRange($class['timeStart'], $class['timeEnd']));
            });
    } else {
        $table->addColumn('allDay', __('All Day'))
            ->format(Format::using('yesNo', 'allDay'));

        $table->addColumn('timeStart', __('Time'))
            ->width('50%')
            ->format(function ($absence) {
                return $absence['allDay'] == 'N'
                    ? Format::small(Format::timeRange($absence['timeStart'], $absence['timeEnd']))
                    : Format::small(__('All Day'));
        });

    }

    if ($coverageByTimetable) {
        $form->addHiddenValue('allDay', 'N');

        $table->addCheckboxColumn('timetableClasses', 'contextCheckboxID')
            ->width('15%')
            ->checked(function ($class) use ($dateStart, $dateEnd) {
                if ($class['offTimetable']) return false;
                
                $insideTimeRange = $class['timeStart'] <= $dateStart['timeEnd'] && $class['timeEnd'] >= $dateEnd['timeStart'];

                return $dateStart['allDay'] == 'Y' || $insideTimeRange ? $class['contextCheckboxID'] : false;
            })
            ->format(function ($class) {
                if ($class['offTimetable']) {
                    return __('Off Timetable');
                }
                if (!empty($class['gibbonStaffCoverageID'])) {
                    return  $class['coverage'] == 'Requested' || $class['coverage'] == 'Pending'
                        ? Format::tag(__('Pending'), 'message')
                        : Format::small(__($class['coverage']));
                }
            });
    } else {
        $table->addCheckboxColumn('requestDates', 'date')
            ->width('15%')
            ->checked(true)
            ->format(function ($date) use (&$unavailable, &$request) {
                // Is this date unavailable: absent, already booked, or has an availability exception
                if (isset($unavailable[$date['date']])) {
                    $times = $unavailable[$date['date']];

                    foreach ($times as $time) {
                    
                        // Handle full day and partial day unavailability
                        if ($time['allDay'] == 'Y' 
                        || ($time['allDay'] == 'N' && $request['allDay'] == 'Y')
                        || ($time['allDay'] == 'N' && $request['allDay'] == 'N'
                            && $time['timeStart'] < $request['timeEnd']
                            && $time['timeEnd'] > $request['timeStart'])) {
                            return Format::small(__($time['status'] ?? 'Not Available'));
                        }
                    }
                }
            });
    }

    if ($canSelectSubstitutes) {
        $table->addColumn('substitute', __('Substitute'))
            ->description(__('Only available substitutes are listed here.'))
            ->addClass('individualOptions')
            ->width('35%')
            ->format(function ($class) use (&$substituteGateway, &$criteria, &$form, &$coverageByTimetable) {
                if (!empty($class['gibbonStaffCoverageID'])) {
                    return !empty($class['surnameCoverage'])
                        ? Format::name('', $class['preferredNameCoverage'], $class['surnameCoverage'], 'Staff', false, true)
                        : __('Any available substitute');
                }

                $availableSubsOptions = array_reduce($class['availability'] ?? [], function ($group, $item) {
                    $group[$item['type']][$item['gibbonPersonID']] = Format::name($item['title'], $item['preferredName'], $item['surname'], 'Staff', true, true);
                    return $group;
                }, []);

                if (empty($availableSubsOptions)) {
                    return Format::small(__('No substitutes available.'));
                }

                $id = $coverageByTimetable ? $class['contextCheckboxID'] : $class['date'];
                return $form->getFactory()->createSelectPerson('gibbonPersonIDCoverage')
                    ->fromArray($availableSubsOptions)
                    ->setName('gibbonPersonIDCoverage['.$id.']')
                    ->setID('gibbonPersonIDCoverage'.$id)
                    ->photo(true, 'small')
                    ->placeholder()
                    ->setClass('individualOptions flex-1')
                    ->getOutput();
            });
    } else {
        $table->addColumn('notes', __('Notes').' *')
            ->width('25%')
            ->format(function ($class) use ($coverageByTimetable, &$form, &$specialDays) {
                if ($class['offTimetable']) {
                    return Format::small($specialDays[$class['date']]['name'] ?? '');
                }
                if (!empty($class['gibbonPersonIDCoverage'])) {
                    return Format::name('', $class['preferredNameCoverage'], $class['surnameCoverage'], 'Staff', false, true);
                }
                $id = $coverageByTimetable ? $class['contextCheckboxID'] : $class['date'];
                $input = $form->getFactory()->createTextField("notes[{$id}]")->setID("notes{$id}")->addClass('coverageNotes hidden');
                return $input->getOutput();
        });
    }

    $row = $form->addRow();
        $row->addLabel('notesStatus', __('Notes'))->description('* '.__('This message is shared with substitutes, and is also visible to users who manage staff coverage.'));
        $row->addTextArea('notesStatus')->setRows(3);

    $row = $form->addRow()->addClass('coverageSubmit');
        $row->addSubmit()->prepend('<div class="coverageNoSubmit inline text-right text-xs text-gray-600 italic pr-1">'.__('Select at least one date and/or time before continuing.').'</div>');

    echo $form->getOutput();
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
