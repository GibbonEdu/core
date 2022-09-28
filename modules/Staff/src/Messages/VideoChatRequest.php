<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

class VideoChatRequest extends Message
{
    protected $absence;
    protected $details;

    public function __construct($absence)
    {
        $this->absence = $absence;
    }

    public function via() : array
    {
        return ['mail', 'sms', 'database'];
    }

    public function getTitle() : string
    {
        return __('Video Chat Request');
    }

    public function getText() : string
    {
        return __('You have a meeting with {person}.', [
            'person' => $this->absence['from'],
        ]);
    }

    public function getModule() : string
    {
        return __('Staff');
    }

    public function getAction() : string
    {
        return __('Go Video Chat');
    }

    public function getLink() : string
    {
        return $this->absence['link'];
    }
}