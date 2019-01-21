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

use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\School\HouseGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byHouse.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
    $gibbonYearGroupIDList = explode(',', $_GET['gibbonYearGroupIDList'] ?? '');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students by House'));
    }

    $houseGateway = $container->get(HouseGateway::class);
    $criteria = $houseGateway->newQueryCriteria()
        ->sortBy(['gibbonYearGroup.sequenceNumber'])
        ->sortBy(['gibbonHouse.name'])
        ->pageSize(0)
        ->fromPOST();

    $houseCounts = $houseGateway->queryStudentHouseCountByYearGroup($criteria, $gibbonSchoolYearID);
    $houses = [];

    // Group each year group result by house, and total up houses as we go
    $yearGroupCounts = array_reduce($houseCounts->toArray(), function ($group, $item) use (&$houses) {
        $yearGroup = $item['gibbonYearGroupID'];
        $house = $item['gibbonHouseID'];
        
        $group[$yearGroup]['yearGroupName'] = $item['yearGroupName'];
        $group[$yearGroup][$house] = [
            'totalFemale' => $item['totalFemale'],
            'totalMale'   => $item['totalMale'],
            'total'       => $item['total'],
        ];
        $houses[$house] = [
            'houseName' => $item['house'],
            'totalFemale' => ($houses[$house]['totalFemale'] ?? 0) + $item['totalFemale'],
            'totalMale'   => ($houses[$house]['totalMale'] ?? 0) + $item['totalMale'],
            'total'       => ($houses[$house]['total'] ?? 0) + $item['total'],
        ];
        return $group;
    }, []);

    // Add the bottom row with a total count
    $yearGroupCounts[] = $houses + ['yearGroupName' => __('Total')];

    // DATA TABLE
    $table = ReportTable::createPaginated('studentsByHouse', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Students by House'));
    $table->modifyRows(function ($house, $row) {
        if ($house['yearGroupName'] == __('Total')) $row->addClass('dull');
        return $row;
    });

    if (isset($_GET['count'])) {
        $table->setDescription(sprintf(__('%1$s students have been assigned to houses. These results include all student counts by house, updated year groups are highlighted in green. Hover over a number to see the balance by gender.'), $_GET['count']));
    }

    $table->addColumn('yearGroupName', __('Year Group'))
        ->sortable(['gibbonYearGroup.sequenceNumber'])
        ->width('20%');

    foreach ($houses as $gibbonHouseID => $house) {
        $table->addColumn($gibbonHouseID, $house['houseName'])
            ->notSortable()
            ->format(function ($houses) use ($gibbonHouseID) {
                $house = $houses[$gibbonHouseID] ?? null;
                if (is_null($house)) return '0';

                $output = '<span title="'.$house['totalFemale'].' '.__('Female').'<br/>'.$house['totalMale'].' '.__('Male').'">';
                $output .= $house['total'];
                $output .= '</span>';
                return $output;
            });
    }

    echo $table->render(new DataSet($yearGroupCounts));
}
