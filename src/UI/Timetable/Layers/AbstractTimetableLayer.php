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

namespace Gibbon\UI\Timetable\Layers;

use Gibbon\UI\Timetable\TimetableItem;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\UI\Timetable\TimetableLayerInterface;

/**
 * Timetable UI: AbstractTimetableLayer
 *
 * @version  v29
 * @since    v29
 */
abstract class AbstractTimetableLayer implements TimetableLayerInterface
{
    protected $name = '';
    protected $active = true;
    protected $type = 'timetabled';
    protected $color = '';
    protected $order = 0;

    protected $items = [];

    public function getName() : string
    {
        return $this->name;
    }

    public function getID() : string
    {
        return str_replace(' ', '', $this->name);
    }

    public function getOrder() : int
    {
        return $this->order;
    }

    public function getColor() : string
    {
        return $this->color;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    public function setOrder(int $order)
    {
        $this->order = $order;
    }

    public function createItem(string $date, bool $allDay = false) : TimetableItem
    {
        $item = new TimetableItem($date, $allDay);
        $this->addItem($item);

        return $item;
    }

    public function addItem(TimetableItem $item)
    {
        $key = $item->date.'-'.($item->allDay ? 'Y' : 'N');

        $this->items[$key][] = $item;
    }

    public function getItems() : array
    {
        $allItems = array_values($this->items);
        return array_merge(...$allItems);
    }

    public function getItemsByDate(string $date, bool $allDay = false) : array
    {
        $key = $date.'-'.($allDay ? 'Y' : 'N');

        return $this->items[$key] ?? [];
    }

    public function countItems() : int
    {
        return count($this->items, COUNT_RECURSIVE) - count($this->items);
    }

    public function filterItems(callable $callback)
    {
        foreach ($this->items as $key => $items) {
            $this->items[$key] = array_filter($items, $callback);
        }
    }

    public function updateItem(TimetableItem $item, string $status)
    {
        if ($status == 'absent') {
            $item->addStatus('absent')->set('style', 'stripe');
        }
    }

    public abstract function checkAccess(TimetableContext $context) : bool;

    public abstract function loadItems(\DatePeriod $dateRange, TimetableContext $context);
}
