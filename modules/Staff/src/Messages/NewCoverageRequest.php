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

class NewCoverageRequest extends Message
{
    protected $coverage;
    protected $details;
    protected $dates;

    public function __construct($coverage, $dates)
    {
        $this->coverage = $coverage;
        $this->dates = $dates;
        $this->details = [
            'nameAbsent'   => Format::name($coverage['titleAbsence'], $coverage['preferredNameAbsence'], $coverage['surnameAbsence'], 'Staff', false, true),
            'date'         => Format::dateRangeReadable($coverage['dateStart'], $coverage['dateEnd']),
            'time'         => $coverage['allDay'] == 'Y' ? __('All Day') : Format::timeRange($coverage['timeStart'], $coverage['timeEnd']),
            'type'         => trim($coverage['type'].' '.$coverage['reason']),
        ];


    }

    public function via() : array
    {
        return $this->coverage['urgent']
            ? ['database', 'mail']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Staff Coverage');
    }

    public function getText() : string
    {
        return __("{nameAbsent} has submitted a coverage request for their {type} absence on {date}.", $this->details);
    }

    public function getDetails() : array
    {
        $coverageDetails = [];

        foreach ($this->dates as $date) {
            $notes = !empty($date['notes']) ? ' ('.$date['notes'].')' : '';
            $coverageDetails[$date['period']] = $date['contextName'].$notes;
        }

        return [
            __('Staff')      => $this->details['nameAbsent'],
            __('Coverage')   => Format::listDetails($coverageDetails),
            __('Notes')      => $this->coverage['notesStatus'],
        ];
    }

    public function getModule() : string
    {
        return 'Staff';
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
