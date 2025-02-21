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
 * Timetable UI: TimetableItem
 * 
 * A flat no-dependency class for holding the timetable item data model.
 *
 * @version  v29
 * @since    v29
 */
class TimetableItem 
{
    protected $title;
    protected $subtitle;
    protected $description;

    protected $link;
    protected $type;
    protected $index;

    protected $color;
    protected $style;
    
    protected $date;
    protected $allDay;
    
    protected $timeStart;
    protected $timeEnd;

    public function __construct(string $date, bool $allDay = false)
    {
        $this->date = $date;
        $this->allDay = $allDay;
    }

    /**
     * Allow read-only access of model properties.
     *
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
        return isset($this->$property) ? $this->$property : null;
    }

    /**
     * Check if a model property exists.
     *
     * @param string $property
     * @return mixed
     */
    public function __isset(string $property)
    {
        return isset($this->$property);
    }

    public function set($key, $value, $default = null)
    {
        $this->$key = $value ?? $default;
    }

    public function loadData(array $data)
    {
        $this->title = $data['title'] ?? $this->title;
        $this->subtitle = $data['subtitle'] ?? $this->subtitle;
        $this->description = $data['description'] ?? $this->description;
        
        $this->type = $data['type'] ?? $this->type;
        $this->link = $data['link'] ?? $this->link;
        $this->index = $data['index'] ?? $this->index;

        $this->color = $data['color'] ?? $this->color;
        $this->style = $data['style'] ?? $this->style;

        $this->date = $data['date'] ?? $this->date;
        $this->allDay = $data['allDay'] ?? $this->allDay;

        $this->timeStart = $data['timeStart'] ?? $this->timeStart;
        $this->timeEnd = $data['timeEnd'] ?? $this->timeEnd;

        return $this;
    }
}
