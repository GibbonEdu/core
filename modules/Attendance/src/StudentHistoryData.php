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

namespace Gibbon\Module\Attendance;

use DatePeriod;
use DateInterval;
use DateTimeImmutable;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;

/**
 * Student History Data
 *
 * @version v18
 * @since   v18
 */
class StudentHistoryData
{
    protected $pdo;
    protected $termGateway;
    protected $attendanceLogGateway;
	protected $timetableGateway;
    protected $settingGateway;

    public function __construct(
        Connection $pdo,
        SchoolYearTermGateway $termGateway,
        AttendanceLogPersonGateway $attendanceLogGateway,
		TimetableDayDateGateway $timetableGateway,
        SettingGateway $settingGateway,
    ) {
        $this->pdo = $pdo;
        $this->termGateway = $termGateway;
        $this->attendanceLogGateway = $attendanceLogGateway;
		$this->timetableGateway = $timetableGateway;
        $this->settingGateway = $settingGateway;
    }

    /**
     * Build a data set of attendance logs grouped by term.
     *
     * @param string $gibbonSchoolYearID
     * @param string $gibbonPersonID
     * @param string $dateStart     Y-m-d
     * @param string $dateEnd       Y-m-d
     * @return DataSet
     */
    public function getAttendanceData($gibbonSchoolYearID, $gibbonPersonID, $dateStart, $dateEnd)
    {
        $countClassAsSchool = $this->settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');
        $firstDayOfTheWeek = $this->settingGateway->getSettingByScope('System', 'firstDayOfTheWeek');
		
		// Get showIncompleteAttendance setting from gibbonSetting
		$showIncomplete = $this->settingGateway->getSettingByScope('Attendance', 'showIncompleteAttendance');
		
		// Determine current user's role category (already stored in session)
		global $session;
		$roleCategory = $session->get('gibbonRoleIDCurrentCategory') ?? '';

		// Hide incomplete attendance for non-Staff (e.g. students and parents) regardless of setting
		if ($roleCategory !== 'Staff') {
			$showIncomplete = 'N';
		}

        // Get Logs
        $logs = $this->attendanceLogGateway
            ->selectAllAttendanceLogsByPerson($gibbonSchoolYearID, $gibbonPersonID)
            ->fetchGrouped();

        // Get Weekdays
        $sql = "SELECT nameShort, name FROM gibbonDaysOfWeek where schoolDay='Y' ORDER BY sequenceNumber";
        $daysOfWeek = $this->pdo->select($sql)->fetchKeyPair();

        // Get Terms
        $criteria = $this->termGateway->newQueryCriteria(true)
            ->filterBy('schoolYear', $gibbonSchoolYearID)
            ->filterBy('firstDay', date('Y-m-d'))
            ->sortBy('firstDay');

        $terms = $this->termGateway->querySchoolYearTerms($criteria)->toArray();
        $today = new DateTimeImmutable();
        $classLogs = [];

        foreach ($terms as $index => $term) {
            $specialDays = $this->termGateway->selectSchoolClosuresByTerm($term['gibbonSchoolYearTermID'])->fetchKeyPair();
            $offTimetableDays = $this->termGateway->selectOffTimetableDaysByTermAndPerson($term['gibbonSchoolYearTermID'], $gibbonPersonID)->fetchKeyPair();

            $firstDay = new DateTimeImmutable($term['firstDay']);
            $lastDay = new DateTimeImmutable($term['lastDay']);

            if ($firstDayOfTheWeek == 'Monday') $firstDayModifier = 'Monday this week';
            elseif ($firstDayOfTheWeek == 'Sunday') $firstDayModifier = 'Sunday last week';
            elseif ($firstDayOfTheWeek == 'Saturday') $firstDayModifier = 'Saturday last week';

            $dateRange = new DatePeriod(
                $firstDay->modify($firstDayModifier),
                new DateInterval('P1D'),
                $lastDay->modify('+1 day')
            );

            $dayCount = 0;
            foreach ($dateRange as $i => $date) {
                if ($date > $today) continue;
                if ($date > $lastDay) continue;

                $week = floor($dayCount / count($daysOfWeek));
                $weekday = $date->format('D');
                $dateYmd = $date->format('Y-m-d');

                if (!isset($daysOfWeek[$weekday])) continue;

                $absentCount = $presentCount = $partialCount = 0;

                $logs[$dateYmd] = array_map(function ($log) use (&$absentCount, &$presentCount, &$partialCount) {
                    if ($log['direction'] == 'Out' && $log['scope'] == 'Offsite') {
                        $log['status'] = 'absent';
                        $log['statusClass'] = 'error';
                        $absentCount++;
                    } elseif ($log['scope'] == 'Onsite - Late' || $log['scope'] == 'Offsite - Late' || $log['scope'] == 'Offsite - Left') {
                        $log['status'] = 'partial';
                        $log['statusClass'] = 'warning';
                        $partialCount++;
                    } else {
                        $log['status'] = 'present';
                        $log['statusClass'] = $log['scope'] == 'Offsite' ? 'message' : 'success';
                        $presentCount++;
                    }

                    return $log;
                }, $logs[$dateYmd] ?? []);

                // Filter class logs for End of Day purposes
                if ($countClassAsSchool == 'N') {
                    $classLogs[$dateYmd] = array_filter($logs[$dateYmd], function ($log) {
                        return $log['context'] == 'Class';
                    });
                    $logs[$dateYmd] = array_filter($logs[$dateYmd], function ($log) {
                        return $log['context'] <> 'Class';
                    });
                }
                
                // School logs only (non-class)
				$schoolLogs = array_filter($logs[$dateYmd] ?? [], function ($log) {
					return $log['context'] !== 'Class';
				});

				// Sort by timestampTaken
				usort($schoolLogs, function ($a, $b) {
					return strcmp($a['timestampTaken'], $b['timestampTaken']);
				});

				$endOfDay = !empty($schoolLogs) ? end($schoolLogs) : [];
			
				// Get all timetable periods for this student on this date, augment classLogs with timetable periods (including missing attendance)
				$periods = $this->timetableGateway
					->selectTimetablePeriodsByPersonAndDate($gibbonSchoolYearID, $gibbonPersonID, $dateYmd, $dateYmd)
					->fetchAll();

				// Existing class logs for this day (may be empty)
				$classLogsForDay = $classLogs[$dateYmd] ?? [];

				// Index existing logs by timetable row, if available
				$logsByTT = [];
				foreach ($classLogsForDay as $log) {
					if (!empty($log['gibbonTTDayRowClassID'])) {
						$logsByTT[$log['gibbonTTDayRowClassID']] = $log;
					}
				}

				$mergedClassLogs = [];

				foreach ($periods as $period) {
                    if ($period['attendance'] != 'Y') continue;

					$ttID = $period['gibbonTTDayRowClassID'] ?? null;
                    $log = [];
                    
					if ($ttID !== null && !empty($logsByTT[$ttID])) {
						// Real attendance log exists for this period
						$log = $logsByTT[$ttID];

						// Ensure periodName is set even if it wasn't returned in the main query
						if (empty($log['periodName'])) {
							$log['periodName'] = $period['periodName'];
						}
					} 
					elseif ($showIncomplete === 'Y' && !empty($endOfDay['type']) && stripos($endOfDay['type'], 'Present') !== false) 
					{

						$log = [
							'periodName'     => $period['periodName'],
							'context'        => 'Class',
							'contextName'    => $period['courseName'].'.'.$period['className'],
							'type'           => __('Not Available'),
							'reason'         => '',
							'status'         => 'notTaken',
							'statusClass'    => 'dull',
							'timestampTaken' => null,
						];
					}

					$mergedClassLogs[] = $log;
				}

				// Replace classLogs for this day with the merged list
				$classLogs[$dateYmd] = array_filter($mergedClassLogs);

                // Handle cases where school-wide attendance does not exist, but class attendance does
                if (empty($endOfDay) && !empty($classLogs[$dateYmd])) {
                    $endOfDay = [
                        'date'        => $dateYmd,
                        'type'        => 'Incomplete',
                        'status'      => '',
                        'statusClass' => 'dull border-gray-700',
                    ];
                }

                // Handle off-timetable days where attendance is not taken
                if (empty($logs[$dateYmd]) && !empty($offTimetableDays[$dateYmd])) {
                    $endOfDay['status'] = 'present';
                    $presentCount++;
                }

                $dayData = [
                    'date'            => $dateYmd,
                    'dateDisplay'     => Format::date($dateYmd),
                    'logs'            => $logs[$dateYmd] ?? [],
                    'classLogs'       => $classLogs[$dateYmd] ?? [],
                    'endOfDay'        => $endOfDay,
                    'specialDay'      => $specialDays[$dateYmd] ?? '',
                    'offTimetable'    => $offTimetableDays[$dateYmd] ?? '',
                    'outsideTerm'     => $date < $firstDay || $date > $lastDay,
                    'beforeStartDate' => !empty($dateStart) && $dateYmd < $dateStart,
                    'afterEndDate'    => !empty($dateEnd) && $dateYmd > $dateEnd,
                    'absentCount'     => $absentCount,
                    'presentCount'    => $presentCount,
                    'partialCount'    => $partialCount,
                    'gibbonPersonID'  => $gibbonPersonID,
                ];

                $terms[$index]['daysOfWeek'] = $daysOfWeek;
                $terms[$index]['weeks'][$week][$weekday] = $dayData;
                $dayCount++;
            }
        }

        return new DataSet($terms);
    }
}
