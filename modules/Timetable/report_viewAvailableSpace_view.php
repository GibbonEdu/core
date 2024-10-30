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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\FacilityGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/report_viewAvailableSpaces.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

    $date = $_GET['date'] ?? '';
    $period = $_GET['period'] ?? '';
    $facilityNameList = $_GET['ids'] ?? [];

    $facilityGateway = $container->get(FacilityGateway::class);
    $facilities = $facilityGateway->selectFacilityInfoByName($facilityNameList)->fetchAll();

    // DATA TABLE
    $table = DataTable::create('facilityList');
    $table->setTitle(Format::dateReadable($date). ' - '. $period);
    $table->setDescription(__('View Available Facilities'));

    $table->addColumn('name', __('Name'))
        ->format(function($values) use ($date) {
            return Format::link('./index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.$values['gibbonSpaceID'].'&ttDate='.$date, $values['name']);
        });
    $table->addColumn('type', __('Type'));
    $table->addColumn('capacity', __('Capacity'));
    $table->addColumn('facilities', __('Facilities'))
        ->notSortable()
        ->format(function($values) {
            $return = null;
            $return .= ($values['computer'] == 'Y') ? __('Teaching computer').'<br/>':'';
            $return .= ($values['computerStudent'] > 0) ? $values['computerStudent'].' '.__('student computers').'<br/>':'';
            $return .= ($values['projector'] == 'Y') ? __('Projector').'<br/>':'';
            $return .= ($values['tv'] == 'Y') ? __('TV').'<br/>':'';
            $return .= ($values['dvd'] == 'Y') ? __('DVD Player').'<br/>':'';
            $return .= ($values['hifi'] == 'Y') ? __('Hifi').'<br/>':'';
            $return .= ($values['speakers'] == 'Y') ? __('Speakers').'<br/>':'';
            $return .= ($values['iwb'] == 'Y') ? __('Interactive White Board').'<br/>':'';
            $return .= ($values['phoneInternal'] != '') ? __('Extension Number').': '.$values['phoneInternal'].'<br/>':'';
            $return .= ($values['phoneExternal'] != '') ? __('Phone Number').': '.Format::phone($values['phoneExternal']).'<br/>':'';
            return $return;
        });
    $table->addColumn('comment', __('Comment'))->format(Format::using('truncate', ['comment', 120]));

    echo $table->render($facilities);

}
