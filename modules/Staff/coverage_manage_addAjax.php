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
use Gibbon\Domain\DataSet;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\SubstituteGateway;

require_once '../../gibbon.php';

$request = [
    'dateStart' => $_POST['dateStart'] ?? '',
    'dateEnd'   => $_POST['dateEnd'] ?? '',
    'allDay'    => $_POST['allDay'] ?? 'N',
    'timeStart' => isset($_POST['timeStart']) ? $_POST['timeStart'].':00' : '',
    'timeEnd'   => isset($_POST['timeEnd']) ? $_POST['timeEnd'].':00' : '',
];

$gibbonPersonIDCoverage = $_POST['gibbonPersonIDCoverage'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_add.php') == false) {
    die(Format::alert(__('Your request failed because you do not have access to this action.')));
} elseif (empty($request['dateStart']) || empty($request['dateEnd'])|| $gibbonPersonIDCoverage == 'Please select...') {
    die();
} else {
    // Proceed!
    $substituteGateway = $container->get(SubstituteGateway::class);

    // DATA TABLE
    $substitute = $substituteGateway->selectBy(['gibbonPersonID'=> $gibbonPersonIDCoverage])->fetch();
    $person = $container->get(UserGateway::class)->getByID($gibbonPersonIDCoverage);
    $unavailable = $substituteGateway->selectUnavailableDatesBySub($gibbonPersonIDCoverage)->fetchGrouped();

    $start = new DateTime(Format::dateConvert($request['dateStart']).' 00:00:00');
    $end = new DateTime(Format::dateConvert($request['dateEnd']).' 23:00:00');

    $dates = [];
    $dateRange = new DatePeriod($start, new DateInterval('P1D'), $end);
    foreach ($dateRange as $date) {
        if (!isSchoolOpen($guid, $date->format('Y-m-d'), $connection2)) continue;
        
        $dates[] = ['date' => $date->format('Y-m-d')];
    }

    if (empty($dates) || empty($substitute)) {
        die();
    }

    $fullName = Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);

    $table = DataTable::create('staffAbsenceDates');
    $table->setTitle(__('Availability'));
    $table->setDescription('<strong>'.$fullName.'</strong><br/><br/>'.$substitute['details']);
    $table->getRenderer()->addData('class', 'bulkActionForm');

    $table->modifyRows(function ($values, $row) {
        return $row->addClass('h-10');
    });

    $table->addColumn('dateLabel', __('Date'))
        ->format(Format::using('dateReadable', 'date'));

    $table->addCheckboxColumn('requestDates', 'date')
        ->width('15%')
        ->checked(true)
        ->format(function ($date) use (&$unavailable, &$request) {
            // Is this date unavailable: absent, already booked, or has an availability exception
            if (isset($unavailable[$date['date']])) {
                $times = $unavailable[$date['date']];

                foreach ($times as $time) {
                
                    // Handle full day and partial day unavailability
                    if ($time['allDay'] == 'Y' 
                    || ($time['allDay'] == 'N' && $request['allDay'] == 'Y')
                    || ($time['allDay'] == 'N' && $request['allDay'] == 'N'
                        && $time['timeStart'] < $request['timeEnd']
                        && $time['timeEnd'] > $request['timeStart'])) {
                        return Format::small(__($time['status'] ?? 'Not Available'));
                    }
                }
            }
        });

    echo $table->render(new DataSet($dates));
}
