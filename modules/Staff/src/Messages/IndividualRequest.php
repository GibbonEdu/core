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

class IndividualRequest extends Message
{
    protected $coverage;

    public function __construct($coverage)
    {
        $this->coverage = $coverage;
    }

    public function via() : array
    {
        return $this->coverage['urgent']
            ? ['database', 'mail', 'sms']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Coverage Request');
    }

    public function getText() : string
    {
        return __("{name} sent you a coverage request for {date}. Are you available? Please login to accept or decline.", [
            'date' => Format::dateRangeReadable($this->coverage['dateStart'], $this->coverage['dateEnd']),
            'name' => Format::name($this->coverage['titleStatus'], $this->coverage['preferredNameStatus'], $this->coverage['surnameStatus'], 'Staff', false, true),
        ]);
    }

    public function getDetails() : array
    {
        return [
            __('Comment') => $this->coverage['notesStatus'],
            __('Date')    => Format::dateRangeReadable($this->coverage['dateStart'], $this->coverage['dateEnd']),
            __('Time')    => $this->coverage['allDay'] == 'Y' ? __('All Day') : Format::timeRange($this->coverage['timeStart'], $this->coverage['timeEnd']),
        ];
    }

    public function getModule() : string
    {
        return __('Staff');
    }

    public function getAction() : string
    {
        return __('View Coverage Requests');
    }

    public function getLink() : string
    {
        return 'index.php?q=/modules/Staff/coverage_view.php';
    }
}
