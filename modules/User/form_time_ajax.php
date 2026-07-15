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
use Gibbon\Domain\Timetable\TimetableDayDateGateway;

require_once '../../gibbon.php';

if (!isset($_SESSION[$guid]) || !$session->exists('gibbonPersonID')) {
    die( __('Your request failed because you do not have access to this action.') );
} else {
    $key = $_POST['key'] ?? '';
    $date = !empty($_POST[$key]) ? preg_replace('/[^0-9\-]/', '', $_POST[$key]) : date('Y-m-d');

    $periods = $container->get(TimetableDayDateGateway::class)->selectTimetabledPeriodsByDate($date)->fetchAll();
    $periods = array_map(function($item) {
        $item['time'] = Format::time($item['timeStart']);
        $item['timeEnd'] = Format::time($item['timeEnd']);
        return $item;
    }, $periods);

    if (empty($periods)) die(__('Unknown'));

    $endOfDay = end($periods);
    $periods[] = ['period' => Format::small(__('End of Day')), 'time' => Format::time($endOfDay['timeEnd']), 'timeEnd' => Format::time($endOfDay['timeEnd'])];

    foreach ($periods as $index => $period) {
        echo <<<HTML
            <li x-from-template :value="time" role="option" tabindex="0"
            x-on:click="selectTime({$index}, '{$period['time']}', '{$period['timeEnd']}')"
            x-on:keydown.enter="selectTime({$index}, '{$period['time']}', '{$period['timeEnd']}')"
            class="px-3 py-1 text-sm text-gray-700 rounded hover:bg-blue-500 hover:text-white focus:bg-blue-500 focus:text-white cursor-pointer whitespace-nowrap"
            >
            <span>{$period['period']}</span>
            </li>
        HTML;
    }
}
