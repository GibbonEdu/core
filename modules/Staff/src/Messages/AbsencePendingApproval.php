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

class AbsencePendingApproval extends Message
{
    protected $absence;
    protected $details;

    public function __construct($absence)
    {
        $this->absence = $absence;
        $this->details = [
            'name' => Format::name($absence['titleAbsence'], $absence['preferredNameAbsence'], $absence['surnameAbsence'], 'Staff', false, true),
            'date' => Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']),
            'time' => $absence['allDay'] == 'Y' ? __('All Day') : Format::timeRange($absence['timeStart'], $absence['timeEnd']),
            'type' => trim($absence['type'].' '.$absence['reason']),
        ];
    }

    public function via() : array
    {
        return $this->absence['urgent']
            ? ['database', 'mail']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Staff Absence').' '.$this->absence['status'];
    }

    public function getText() : string
    {
        return __("{name} is requesting leave on {date} for {type}. You may choose to approve or decline this request.", $this->details);
    }

    public function getDetails() : array
    {
        $details = [
            __('Staff')   => $this->details['name'],
            __('Type')    => $this->details['type'],
            __('Date')    => $this->details['date'],
            __('Time')    => $this->details['time'],
        ];
        
        $details += !empty($this->absence['commentConfidential'])
            ? [__('Confidential Comment') => $this->absence['commentConfidential']]
            : [__('Comment') => $this->absence['comment']];

        if (!empty($this->absence['coverageRequired']) && $this->absence['coverageRequired'] == 'Y') {
            $details[__('Cover Required')] = __('Yes');
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
        return 'index.php?q=/modules/Staff/absences_approval.php&gibbonStaffAbsenceID='.$this->absence['gibbonStaffAbsenceID'];
    }
}
