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

namespace Gibbon\Module\Staff\Forms;

use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;


/**
 * CoverageRequestForm
 *
 * @version v26
 * @since   v26
 */
class CoverageRequestForm
{
    protected $session;
    protected $db;
    protected $substituteGateway;
    protected $specialDayGateway;
    protected $staffCoverageDateGateway;
    protected $coverageMode;
    protected $internalCoverage;

    public function __construct(Session $session, Connection $db, SettingGateway $settingGateway, StaffCoverageDateGateway $staffCoverageDateGateway, SchoolYearSpecialDayGateway $specialDayGateway, SubstituteGateway $substituteGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->staffCoverageDateGateway = $staffCoverageDateGateway;
        $this->specialDayGateway = $specialDayGateway;
        $this->substituteGateway = $substituteGateway;

        $this->coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
        $this->internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');
    }

    public function createForm($gibbonPersonID, $dateStart, $dateEnd, $allDay, $timeStart = null, $timeEnd = null)
    {
        $canSelectSubstitutes = $this->coverageMode == 'Requested'; // TODO: && $values['status'] == 'Approved'
        $classesNeedingCover = 0;

        // Get timetabled classes and non-class records that need coverage (activities and duty)
        $classes = $this->staffCoverageDateGateway->selectPotentialCoverageByPersonAndDate($this->session->get('gibbonSchoolYearID'), $gibbonPersonID, $dateStart, $dateEnd)->fetchAll();
        $classes = array_map(function ($item) {
            $item['contextCheckboxID'] = $item['date'].':'.$item['foreignTable'].':'.$item['foreignTableID'];
            return $item;
        }, $classes);

        // Check for special days for these classes
        $specialDays = $this->specialDayGateway->selectSpecialDaysByDateRange($dateStart, $dateEnd)->fetchGroupedUnique();

        // Check if classes are running on a special day
        $classes = array_reduce($classes, function ($group, $item) use (&$specialDays, &$classesNeedingCover) {
            $item['offTimetable'] = false;

            if ($item['context'] == 'Class' && isset($specialDays[$item['date']])) {
                $item['offTimetable'] = $this->specialDayGateway->getIsClassOffTimetableByDate($this->session->get('gibbonSchoolYearID'), $item['contextID'], $item['date']);
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
        $absenceDates = [];

        if (!$coverageByTimetable) {
            $start = new \DateTime(Format::dateConvert($dateStart).' 00:00:00');
            $end = new \DateTime(Format::dateConvert($dateEnd).' 23:00:00');

            $dateRange = new \DatePeriod($start, new \DateInterval('P1D'), $end);
            foreach ($dateRange as $date) {
                $absenceDates[] = [
                    'date'                 => $date->format('Y-m-d'),
                    'allDay'               => $allDay,
                    'timeStart'            => $timeStart,
                    'timeEnd'              => $timeStart,
                ];
            }
        }

        // Look for available subs
        $criteria = $this->substituteGateway->newQueryCriteria()
            ->filterBy('allStaff', $this->internalCoverage == 'Y')
            ->sortBy('gibbonSubstitute.priority', 'DESC')
            ->sortBy(['surname', 'preferredName']);

        $availabilityCount = 0;
        $availableSubs = [];

        foreach ($coverageByTimetable ? $classes : $absenceDates as $index => $date) {
            if (!empty($date['gibbonStaffCoverageID'])) continue; // Already covered

            $availableByDate = $this->substituteGateway->queryAvailableSubsByDate($criteria, $date['date'], $date['timeStart'], $date['timeEnd'])->toArray();
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

        // Build a list of available subs by type
        $countTypes = [];
        $availableSubsByType = array_reduce($availableSubs, function ($group, $item) use (&$countTypes) {
            $countTypes[$item['type']] = isset($countTypes[$item['type']])? $countTypes[$item['type']] + 1 : 1;
            $group[$item['type']] = $item['type']." ({$countTypes[$item['type']]})";
            return $group;
        }, []);

        // FORM
        $form = Form::create('staffAbsenceEdit', '');

        $form->setFactory(DatabaseFormFactory::create($this->db));

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
                return Format::small(Format::dayOfWeekName($coverage['date']));
            });

        if ($coverageByTimetable) {
            $table->addColumn('period', __('Period'));
            $table->addColumn('contextName', __('Cover'));
            $table->addColumn('timeStart', __('Time'))
                ->format(function ($class) {
                    return Format::small(Format::timeRange($class['timeStart'], $class['timeEnd']));
                });
        } else {
            $table->addColumn('timeStart', __('Time'))
                ->format(function ($absence) {
                    return $absence['allDay'] == 'N'
                        ? Format::small(Format::timeRange($absence['timeStart'], $absence['timeEnd']))
                        : Format::small(__('All Day'));
            });

        }

        if ($coverageByTimetable) {
            $table->addCheckboxColumn('timetableClasses', 'contextCheckboxID')
                ->width('15%')
                ->checked(function ($class) use ($allDay, $timeStart, $timeEnd) {
                    if ($class['offTimetable']) return false;

                    $insideTimeRange = $class['timeStart'] < $timeEnd.':00' && $class['timeEnd'] > $timeStart.':00';

                    return $allDay == 'Y' || $insideTimeRange ? $class['contextCheckboxID'] : false;
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
                ->format(function ($class) use (&$form, &$coverageByTimetable, &$availableSubs) {
                    if (!empty($class['gibbonStaffCoverageID'])) {
                        return !empty($class['surnameCoverage'])
                            ? Format::name('', $class['preferredNameCoverage'], $class['surnameCoverage'], 'Staff', false, true)
                            : __('Any available substitute');
                    }

                    $availableSubsList = $coverageByTimetable ? ($class['availability'] ?? []) : $availableSubs;
                    $availableSubsOptions = array_reduce($availableSubsList, function ($group, $item) {
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
            $table->addColumn('notes', __('Notes').' **')
                ->width('25%')
                ->format(function ($class) use ($coverageByTimetable, &$form, &$specialDays) {
                    if ($class['offTimetable']) {
                        return Format::small($specialDays[$class['date']]['name'] ?? '');
                    }
                    if (!empty($class['gibbonPersonIDCoverage'])) {
                        return Format::name('', $class['preferredNameCoverage'], $class['surnameCoverage'], 'Staff', false, true);
                    }
                    $id = $coverageByTimetable ? $class['contextCheckboxID'] : $class['date'];
                    $input = $form->getFactory()->createTextField("notes[{$id}]")->setID("notes{$id}")->addClass($coverageByTimetable ? 'coverageNotes hidden' : 'coverageNotes');
                    return $input->getOutput();
            });
        }

        $row = $form->addRow();
            $row->addLabel('notesStatus', __('Notes'))->description('** '.__('This message is shared with substitutes, and is also visible to users who manage staff coverage.'));
            $row->addTextArea('notesStatus')->setRows(3);

        return $form;
    }
}
