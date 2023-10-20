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
use Gibbon\Domain\School\FacilityGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;

include '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Timetable/spaceBooking_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    echo Format::alert(__('You do not have access to this action.'));
} else {
    
    $gibbonTTDayRowClassID = substr($_POST['gibbonTTDayRowClassID'] ?? '', 0, 12);
    $date = substr($_POST['gibbonTTDayRowClassID'] ?? '', 13);
    $gibbonSpaceID = $_POST['gibbonSpaceID'] ?? '';

    if (empty($gibbonTTDayRowClassID) || empty($date) || empty($gibbonSpaceID)) {
        echo Format::alert(__('You have not specified one or more required parameters.'));
        return;
    }

    $facilityGateway = $container->get(FacilityGateway::class);
    $ttDayDateGateway = $container->get(TimetableDayDateGateway::class);

    $facility = $facilityGateway->getByID($gibbonSpaceID);
    $period = $ttDayDateGateway->getTimetablePeriodByDayRowClass($gibbonTTDayRowClassID);

    if (empty($period) || empty($facility)) {
        echo Format::alert(__('You have not specified one or more required parameters.'));
        return;
    }

    $inUse = $facilityGateway->selectFacilityInUseByDateAndTime($gibbonSpaceID, $date, $period['timeStart'], $period['timeEnd'])->fetchAll(\PDO::FETCH_COLUMN, 0);

    if (!empty($inUse)) {
        echo Format::alert(__('In Use by {name} (Capacity: {capacity})', ['name' => implode(', ', $inUse), 'capacity' => $facility['capacity']]), 'error');
    } else {
        echo Format::alert(__('Available (Capacity: {capacity})', ['capacity' => $facility['capacity']]), 'success');
    }
    
}
