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

use Gibbon\UI\Timetable\TimetableContext;

/**
 * Timetable UI: TimetableLayerInterface
 *
 * @version  v29
 * @since    v29
 */
interface TimetableLayerInterface
{
    public function getName() : string;

    public function getID() : string;

    public function getOrder() : int;
    
    public function getColor() : string;
    
    public function getType() : string;
    
    public function isActive() : bool;

    public function setActive(bool $active);

    public function setOrder(int $order);

    public function createItem(string $date, bool $allDay = false) : TimetableItem;

    public function addItem(TimetableItem $item);

    public function updateItem(TimetableItem $item, string $status);

    public function getItems() : array;

    public function getItemsByDate(string $date, bool $allDay = false) : array;

    public function countItems() : int;

    public function filterItems(callable $callback);

    public function checkAccess(TimetableContext $context) : bool;

    public function loadItems(\DatePeriod $dateRange, TimetableContext $context);
}
