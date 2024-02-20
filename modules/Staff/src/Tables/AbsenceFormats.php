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

namespace Gibbon\Module\Staff\Tables;

use Gibbon\Services\Format;

/**
 * Reusable formats for displaying absence and coverage info in tables.
 * 
 * @version v18
 * @since   v18
 */
class AbsenceFormats
{
    public static function personDetails($absence)
    {
        $output = !empty($absence['surnameAbsence'])
            ? Format::name($absence['titleAbsence'], $absence['preferredNameAbsence'], $absence['surnameAbsence'], 'Staff', false, true)
            : '';
        $gibbonPersonID = $absence['gibbonPersonID'] ?? '';

        if (empty($output) && !empty($absence['surnameStatus'])) {
            $output = Format::name($absence['titleStatus'], $absence['preferredNameStatus'], $absence['surnameStatus'], 'Staff', false, true);
        }
        
        return Format::link('./index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$gibbonPersonID, $output);
    }

    public static function personAndTypeDetails($absence)
    {
        return static::personDetails($absence).'<br/>'.Format::small($absence['type'].' '.$absence['reason']);
    }

    public static function substituteDetails($coverage)
    {
        if (empty($coverage['gibbonPersonIDCoverage'])) {
            if ($coverage['status'] == 'Pending' || $coverage['status'] == 'Requested') {
                return Format::tag(__('Cover Required'), 'error whitespace-nowrap');
            } else if ($coverage['status'] == 'Not Required') {
                return Format::tag(__('Not Required'), 'dull whitespace-nowrap');
            }
        }

        $name = !empty($coverage['gibbonPersonIDCoverage'])
            ? Format::nameLinked($coverage['gibbonPersonIDCoverage'], $coverage['titleCoverage'], $coverage['preferredNameCoverage'], $coverage['surnameCoverage'], 'Staff', false, true)
            : Format::name($coverage['titleCoverage'], $coverage['preferredNameCoverage'], $coverage['surnameCoverage'], 'Staff', false, true);
        return !empty($coverage['surnameCoverage'])
            ? $name
            : Format::tag(__('Pending'), 'message');
    }

    public static function dateDetails($absence)
    {
        $output = Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']);
        if ($absence['allDay'] == 'Y' || $absence['days'] > 1) {
            if (!empty($absence['value']) && $absence['value'] != $absence['days']) {
                $output .= !empty($absence['foreignTableID']) || !empty($absence['gibbonTTDayRowClassID'])
                    ? '<br/>'.Format::small(__('{total} Periods (across {count} Days)', ['total' => round($absence['value'], 1), 'count' => $absence['days']]))
                    : '<br/>'.Format::small(__('{total} Total (across {count} Days)', ['total' => round($absence['value'], 1), 'count' => $absence['days']]));
            } else {
                $output .= !empty($absence['foreignTableID']) || !empty($absence['gibbonTTDayRowClassID'])
                    ? '<br/>'.Format::small(__n('{count} Period', '{count} Periods', intval($absence['days'])))
                    : '<br/>'.Format::small(__n('{count} Day', '{count} Days', intval($absence['days'])));
            }
        } else {
            $output .= '<br/>'.Format::small(Format::timeRange($absence['timeStart'], $absence['timeEnd']));
        }
        
        return Format::tooltip($output, $absence['value'] ?? '');
    }

    public static function timeDetails($absence)
    {
        if ($absence['allDay'] == 'N') {
            return Format::small(Format::timeRange($absence['timeStart'], $absence['timeEnd']));
        } else {
            return Format::small(__('All Day'));
        }
    }

    public static function typeAndReason($absence)
    {
        $output = $absence['type'];
        if (!empty($absence['reason'])) {
            $output .= '<br/>'.Format::small($absence['reason']);
        }
        if ($absence['status'] != 'Approved') {
            $output .= '<br/><span class="small emphasis">'.__($absence['status']).'</span>';
        }
        return $output;
    }

    public static function coverage($absence) {
        if (empty($absence['gibbonPersonIDCoverage']) && ($absence['coverage'] == 'Pending' || $absence['coverage'] == 'Declined')) {
            return Format::tag(__('Cover Required'), 'error whitespace-nowrap');
        }

        if ($absence['coverage'] == 'Accepted') {
            return Format::name($absence['titleCoverage'], $absence['preferredNameCoverage'], $absence['surnameCoverage'], 'Staff', false, true);
        } elseif ($absence['coverage'] == 'Requested' || $absence['coverage'] == 'Pending') {
            return '<span class="tag message">'.__('Pending').'</span>';
        }
        return '';
    }

    public static function coverageList($absence)
    {
        if (empty($absence['gibbonPersonIDCoverage']) && !empty($absence['coverage']) && ($absence['coverage'] == 'Pending' || $absence['coverage'] == 'Declined')) {
            return Format::tag(__('Cover Required'), 'error whitespace-nowrap');
        }

        $absence['coverageList'] = array_filter($absence['coverageList'], function ($item) {
            return !empty($item['gibbonPersonIDCoverage']);
        });

        if ($absence['coverageRequired'] == 'Y' && empty($absence['coverageList'])) {
            return Format::tag(__('Cover Required'), 'error whitespace-nowrap');
        }

        if ($absence['coverageRequired'] == 'N') {
            return Format::small(__('N/A'));
        }

        if (empty($absence['coverageList'])) {
            return '';
        }

        $names = [];
        foreach ($absence['coverageList'] as $absence) {
            $names[] = static::coverage($absence);
        }
        $names = array_unique($names);

        return implode('<br/>', $names);
    }

    public static function coverageStatus($coverage, $urgencyThreshold)
    {
        if ($coverage['status'] != 'Requested') {
            return __($coverage['status']);
        }

        $urgencyThreshold = max(1, intval($urgencyThreshold));
        $relativeSeconds = strtotime($coverage['dateStart']) - time();
        if ($relativeSeconds <= 0) {
            return '<span class="tag dull">'.__('Overdue').'</span>';
        } elseif ($relativeSeconds <= (86400 * $urgencyThreshold)) {
            return '<span class="tag error">'.__('Urgent').'</span>';
        } elseif ($relativeSeconds <= (86400 * ($urgencyThreshold * 3))) {
            return '<span class="tag warning">'.__('Upcoming').'</span>';
        } else {
            return __($coverage['status']);
        }
    }

    public static function createdOn($absence)
    {
        $output = Format::relativeTime($absence['timestampCreator'], 'M j, Y H:i');
        if ($absence['gibbonPersonID'] != $absence['gibbonPersonIDCreator']) {
            $output .= '<br/>'.Format::small(__('By').' '.Format::name('', $absence['preferredNameCreator'], $absence['surnameCreator'], 'Staff', false, true));
        }
        return $output;
    }
}
