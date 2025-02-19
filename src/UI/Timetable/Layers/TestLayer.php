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
use Gibbon\UI\Timetable\TimetableLayerInterface;

/**
 * Timetable UI: TestLayer
 *
 * @version  v29
 * @since    v29
 */
class TestLayer extends AbstractTimetableLayer
{
    public function __construct()
    {
        $this->name = 'Test Layer';
        $this->order = 10;
    }
    
    public function loadItems(string $dateStart, string $dateEnd, string $gibbonTTID = null, string $gibbonPersonID = null) 
    {
        $this->createItem('2025-02-20')->loadData([
            'title'     => 'Test 1',
            'timeStart' => '10:45:00',
            'timeEnd'   => '11:50:00',
        ]);

        $this->createItem('2025-02-21')->loadData([
            'title'     => 'Test 2',
            'timeStart' => '07:30:00',
            'timeEnd'   => '08:55:00',
        ]);

        $this->createItem('2025-02-18', true)->loadData([
            'title'     => 'Test 3',
        ]);

        $this->createItem('2025-02-18')->loadData([
            'title'     => 'Test 4',
            'timeStart' => '16:45:00',
            'timeEnd'   => '18:45:00',
        ]);
    }
}
