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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\FormFactory;
use Gibbon\Forms\FormFactoryInterface;

require_once '../../gibbon.php';

$date = $_POST['date'] ?? '';
$timeStart = $_POST['timeStart'] ?? '';
$timeEnd = $_POST['timeEnd'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    die(Format::alert(__('You do not have access to this action.')));
} elseif (empty($date) || empty($timeStart) || empty($timeEnd)) {
    die();
} else {
    // Proceed!
    $substituteGateway = $container->get(SubstituteGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);

    $availableByDate = $substituteGateway->queryAvailableSubsByDate($criteria, $date, $timeStart, $timeEnd)->toArray();

    $availableSubsOptions = array_reduce($availableByDate, function ($group, $item) {
        $group[$item['type']][$item['gibbonPersonID']] = Format::name($item['title'], $item['preferredName'], $item['surname'], 'Staff', true, true);
        return $group;
    }, []);

    $formFactory = $container->get(FormFactory::class);

    echo $formFactory->createSelectPerson('gibbonPersonIDCoverage')
        ->fromArray($availableSubsOptions)
        ->placeholder()
        ->getOutput();
}
