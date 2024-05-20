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

class AbsenceApproval extends Message
{
    protected $absence;
    protected $details;

    public function __construct($absence)
    {
        $this->absence = $absence;
        $this->details = [
            'name'     => Format::name($absence['titleApproval'], $absence['preferredNameApproval'], $absence['surnameApproval'], 'Staff', false, true),
            'date'     => Format::dateRangeReadable($absence['dateStart'], $absence['dateEnd']),
            'type'     => trim($absence['type'].' '.$absence['reason']),
            'actioned' => strtolower($absence['status']),
        ];
    }

    public function via() : array
    {
        return $this->absence['urgent']
            ? ['database', 'mail', 'sms']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Staff Absence').' '.$this->absence['status'];
    }

    public function getText() : string
    {
        return __("{name} has {actioned} your {type} absence for {date}.", $this->details);
    }

    public function getDetails() : array
    {
        return [
            __($this->absence['status'])  => Format::dateTimeReadable($this->absence['timestampApproval']),
            __('Reply') => $this->absence['notesApproval'],
        ];
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
        return 'index.php?q=/modules/Staff/absences_view_details.php&gibbonStaffAbsenceID='.$this->absence['gibbonStaffAbsenceID'];
    }
}
