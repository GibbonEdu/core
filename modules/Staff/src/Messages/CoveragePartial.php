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

class CoveragePartial extends Message
{
    protected $coverage;
    protected $uncoveredDates;

    public function __construct($coverage, $uncoveredDates)
    {
        $this->coverage = $coverage;

        $this->uncoveredDates = array_map(function ($date) {
            return Format::dateReadable($date, Format::MEDIUM_NO_YEAR);
        }, $uncoveredDates);
    }

    public function via() : array
    {
        return $this->coverage['urgent']
            ? ['database', 'mail', 'sms']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Coverage Partially Accepted');
    }

    public function getText() : string
    {
        return __("{name} has partially accepted your coverage request for {date}. They are unavailable to cover {otherDates}.", [
            'date' => Format::dateRangeReadable($this->coverage['dateStart'], $this->coverage['dateEnd']),
            'name' => Format::name($this->coverage['titleCoverage'], $this->coverage['preferredNameCoverage'], $this->coverage['surnameCoverage'], 'Staff', false, true),
            'otherDates' => implode(', ', $this->uncoveredDates),
        ]);
    }

    public function getDetails() : array
    {
        return [
            __('Reply') => $this->coverage['notesCoverage'],
        ];
    }

    public function getModule() : string
    {
        return __('Staff');
    }

    public function getAction() : string
    {
        return __('New Coverage Request');
    }

    public function getLink() : string
    {
        return 'index.php?q=/modules/Staff/coverage_request.php&gibbonStaffAbsenceID='.$this->coverage['gibbonStaffAbsenceID'];
    }
}
