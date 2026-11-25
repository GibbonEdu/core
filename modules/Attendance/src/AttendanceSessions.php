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

use Gibbon\Domain\System\SettingGateway;

/**
 * Attendance Sessions
 *
 * Works out the attendance status of each registration session in a day (such as AM and PM), or of the
 * whole day when sessions are not in use, and counts them up for summaries and percentages.
 *
 * @version v31
 * @since   v31
 */
class AttendanceSessions
{
    /**
     * The named registration sessions for form group attendance, such as ['AM', 'PM'].
     * An empty array means attendance is recorded once per day.
     *
     * @param SettingGateway $settingGateway
     * @return array
     */
    public static function getSessions(SettingGateway $settingGateway)
    {
        $sessions = $settingGateway->getSettingByScope('Attendance', 'formGroupAttendanceSessions');

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $sessions ?? '')), 'strlen')));
    }

    /**
     * The status of a single log: present, partial (late or left early) or absent. This matches Student History.
     *
     * @param array $log  Requires the direction and scope of the log's attendance code.
     * @return string
     */
    public static function getStatus(array $log)
    {
        if ($log['direction'] == 'Out' && $log['scope'] == 'Offsite') {
            return 'absent';
        }
        if (in_array($log['scope'], ['Onsite - Late', 'Offsite - Late', 'Offsite - Left'])) {
            return 'partial';
        }

        return 'present';
    }

    /**
     * Whether a log records the student arriving late.
     *
     * @param array $log
     * @return bool
     */
    public static function isLate(array $log)
    {
        return $log['scope'] == 'Onsite - Late' || $log['scope'] == 'Offsite - Late';
    }

    /**
     * Picks the log that decides each session of one day. A session's status comes from its latest log:
     * either one recorded for that session, or one recorded for the whole day with no session, such as
     * attendance taken by person or a future absence. Class attendance is not used for sessions.
     * When sessions are not in use, the day's latest log is returned under an empty key.
     *
     * @param array $dayLogs   Logs for one day, with session, context and timestampTaken.
     * @param array $sessions
     * @param bool $countClassAsSchool  Only applies when sessions are not in use.
     * @return array  Session name => log, or null when the session has no data.
     */
    public static function getSessionLogs(array $dayLogs, array $sessions, $countClassAsSchool = false)
    {
        usort($dayLogs, function ($a, $b) {
            return [$a['timestampTaken'] ?? '', $a['gibbonAttendanceLogPersonID'] ?? 0] <=> [$b['timestampTaken'] ?? '', $b['gibbonAttendanceLogPersonID'] ?? 0];
        });

        if (empty($sessions)) {
            $logs = array_filter($dayLogs, function ($log) use ($countClassAsSchool) {
                return $countClassAsSchool || ($log['context'] ?? '') != 'Class';
            });

            return ['' => !empty($logs) ? end($logs) : null];
        }

        $sessionLogs = [];
        foreach ($sessions as $session) {
            $logs = array_filter($dayLogs, function ($log) use ($session) {
                return ($log['context'] ?? '') != 'Class' && (empty($log['session']) || $log['session'] == $session);
            });
            $sessionLogs[$session] = !empty($logs) ? end($logs) : null;
        }

        return $sessionLogs;
    }

    /**
     * Counts the recorded sessions (or days, when sessions are not in use) by status, and the percentage
     * attended. Late and left-early marks count as attended. Sessions with no data are not counted.
     *
     * @param array $logsByDate  Logs grouped by date (Y-m-d => array of logs). Leave out school closures.
     * @param array $sessions
     * @param bool $countClassAsSchool
     * @return array  total, present, partial, absent, late, minutesLate, attended, percentage (null when nothing is recorded)
     */
    public static function countStatuses(array $logsByDate, array $sessions, $countClassAsSchool = false)
    {
        $counts = ['total' => 0, 'present' => 0, 'partial' => 0, 'absent' => 0, 'late' => 0, 'minutesLate' => 0];

        foreach ($logsByDate as $dayLogs) {
            foreach (static::getSessionLogs($dayLogs, $sessions, $countClassAsSchool) as $log) {
                if (empty($log)) continue;

                $counts[static::getStatus($log)]++;
                $counts['total']++;

                if (static::isLate($log)) {
                    $counts['late']++;
                    $counts['minutesLate'] += intval($log['minutesLate'] ?? 0);
                }
            }
        }

        $counts['attended'] = $counts['present'] + $counts['partial'];
        $counts['percentage'] = $counts['total'] > 0 ? round($counts['attended'] / $counts['total'] * 100, 1) : null;

        return $counts;
    }
}
