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

use Gibbon\Services\Format;

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_add.php') == false) {
    die();
} else {
    // Proceed!
    $dateStart = $_POST['dateStart'] ?? '';
    $dateEnd = $_POST['dateEnd'] ?? '';
    
    $start = new DateTime(Format::dateConvert($dateStart).' 00:00:00');
    $end = new DateTime(Format::dateConvert($dateEnd).' 23:00:00');

    $dateRange = new DatePeriod($start, new DateInterval('P1D'), $end);
    $invalidDates = 0;

    // Count the valid school days in the selected range
    foreach ($dateRange as $date) {
        if (!isSchoolOpen($guid, $date->format('Y-m-d'), $connection2)) {
            $invalidDates++;
        }
    }

    echo !empty($invalidDates) ? 0 : 1;
}
