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

namespace Gibbon\Module\Staff\Messages;

use Gibbon\Module\Staff\Message;
use Gibbon\Services\Format;

class CoverageCancelled extends Message
{
    protected $coverage;
    protected $dates;

    public function __construct($coverage, $dates)
    {
        $this->coverage = $coverage;
        $this->dates = $dates;
    }

    public function via() : array
    {
        return $this->coverage['urgent']
            ? ['database', 'mail', 'sms']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Coverage Cancelled');
    }

    public function getText() : string
    {
        $name = !empty($this->coverage['preferredNameAbsence'])
            ? Format::name($this->coverage['titleAbsence'], $this->coverage['preferredNameAbsence'], $this->coverage['surnameAbsence'], 'Staff', false, true)
            : Format::name($this->coverage['titleStatus'], $this->coverage['preferredNameStatus'], $this->coverage['surnameStatus'], 'Staff', false, true);
            
        return __("{name}'s coverage request for {date} has been cancelled.", [
            'date' => Format::dateRangeReadable($this->coverage['dateStart'], $this->coverage['dateEnd']),
            'name' => $name,
        ]);
    }

    public function getDetails() : array
    {
        $coverageDetails = [];

        foreach ($this->dates as $date) {
            $notes = !empty($date['notes']) ? ' ('.$date['notes'].')' : '';
            if (!empty($date['period']) && !empty($date['contextName'])) {
                $coverageDetails[$date['period']] = $date['contextName'].$notes;
            } else {
                $dateReadable = Format::dateReadable($date['date']);
                $coverageDetails[$dateReadable] = (!empty($date['surnameCoverage']) ? Format::name($date['titleCoverage'], $date['preferredNameCoverage'], $date['surnameCoverage'], 'Staff', false, true) : '').$notes;
            }
        }

        $details =  [
            __('Comment') => $this->coverage['notesStatus'],
        ];

        if (!empty($coverageDetails)) {
            $details[__('Coverage Cancelled')] = Format::listDetails($coverageDetails);
        }

        return $details;
    }

    public function getModule() : string
    {
        return __('Staff');
    }

    public function getAction() : string
    {
        return __('View Details');
    }

    public function getLink() : string
    {
        return 'index.php?q=/modules/Staff/coverage_view_details.php&gibbonStaffCoverageID='.$this->coverage['gibbonStaffCoverageID'];
    }
}
