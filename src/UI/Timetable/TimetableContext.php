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

namespace Gibbon\UI\Timetable;

/**
 * Timetable UI: TimetableContext
 * 
 * A simple data model to hold contextual information for loading timetable layers.
 *
 * @version  v29
 * @since    v29
 */
class TimetableContext
{
    protected $data = [
        'gibbonSchoolYearID' => '',
        'gibbonPersonID'     => '',
        'gibbonSpaceID'      => '',
        'ttOptions'          => '',
        'ttLayers'           => '',
        'format'             => '',
    ];

    public function has($key)
    {
        return !empty($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set($key, $value, $default = null)
    {
        $this->data[$key] = $value ?? $default;

        return $this;
    }

    public function loadData(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }
}
