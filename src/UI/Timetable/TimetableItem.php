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
    protected $active = true;
    protected $statuses;

    protected $id;
    protected $label;
    protected $title;
    protected $subtitle;
    protected $description;
    protected $overlap;

    protected $period;
    protected $location;
    protected $phone;

    protected $link;
    protected $type;
    protected $index;

    protected $color;
    protected $style;
    
    protected $date;
    protected $allDay;
    
    protected $timeStart;
    protected $timeEnd;
    protected $duration;

    protected $primaryAction;
    protected $secondaryAction;

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

    /**
     * Set a property of a given name
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $default
     * @return self
     */
    public function set(string $property, $value, $default = null)
    {
        $this->$property = $value ?? $default;

        return $this;
    }

    /**
     * Add a status tag to the statuses array.
     *
     * @param string $status
     * @return self
     */
    public function addStatus(string $status)
    {
        $this->statuses[$status] = $status;

        return $this;
    }

    /**
     * Check if a status tag has been added to this item.
     *
     * @param string $status
     * @return bool
     */
    public function hasStatus(string $status)
    {
        return !empty($this->statuses[$status]);
    }

    /**
     * Gets if the current item should display on the timetable.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Returns a time-specific key, used when finding overlapping items.
     *
     * @return string
     */
    public function getKey() : string
    {
        return $this->date.'-'.$this->timeStart.'-'.$this->timeEnd;
    }

    /**
     * Load values from an array into the properties for this object
     *
     * @param array $data
     * @return self
     */
    public function loadData(array $data)
    {
        $this->id = $data['id'] ?? $this->id;
        $this->title = $data['title'] ?? $this->title;
        $this->label = $data['label'] ?? $this->label;
        $this->subtitle = $data['subtitle'] ?? $this->subtitle;
        $this->description = $data['description'] ?? $this->description;
        $this->overlap = $data['overlap'] ?? $this->overlap;

        $this->period = $data['period'] ?? $this->period;
        $this->location = $data['location'] ?? $this->location;
        $this->phone = $data['phone'] ?? $this->phone;
        
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

    /**
     * Constrains the start and end time of this item, and deactivates if the 
     * timing is outside the provided range.
     *
     * @param string $timeStart
     * @param string $timeEnd
     * @return void
     */
    public function constrainTiming($timeStart, $timeEnd)
    {
        if ($this->allDay == 'Y') return;

        if (!empty($timeStart)) {
            if ($this->timeEnd < $timeStart) {
                $this->active = false;
            }

            if ($this->timeStart < $timeStart) {
                $this->timeStart = $timeStart;
            }
        }

        if (!empty($timeEnd)) {
            if ($this->timeStart > $timeEnd) {
                $this->active = false;
            }

            if ($this->timeEnd > $timeEnd) {
                $this->timeEnd = $timeEnd;
            }
        }

        if ($this->timeStart == $this->timeEnd) {
            $this->active = false;
        }
    }

    public function checkOverlap(TimetableItem $other, bool $checkAllDay = true)
    {
        if ($other->date != $this->date) return false;

        if ($other->allDay == 'Y' && $checkAllDay) return true;

        return ($this->timeStart >= $other->timeStart && $this->timeStart < $other->timeEnd)
            || ($other->timeStart >= $this->timeStart && $other->timeStart < $this->timeEnd);
    }
}
