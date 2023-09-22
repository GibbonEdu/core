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
use Gibbon\Domain\Planner\UnitGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View All Assessments'));
    
    $sql = getLessons($guid, $connection2);
    $lessons = $pdo->select($sql[1], $sql[0])->fetchAll();
    $unitGateway = $container->get(UnitGateway::class);

    $table = DataTable::create('crowdAssessment');
    $table->setDescription(__('The list below shows all lessons in which there is work that you can crowd assess.'));

    $table->addColumn('class', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('lesson', __('Lesson'))->description(__('Unit'))
        ->format(function ($lesson) use ($unitGateway) {
            $output = '<b>'.$lesson['name'].'</b>';
            if (!empty($lesson['gibbonUnitID'])) {
                $unit = $unitGateway->getByID($lesson['gibbonUnitID']);
                $output .= '<br/>'.Format::small($unit['name']);
            }
            return $output;
        });
    $table->addColumn('date', __('Date'))->format(Format::using('date', 'date'));

    $table->addActionColumn()
        ->addParam('gibbonPlannerEntryID')
        ->format(function ($row, $actions) {
            $actions->addAction('view', __('View Details'))
                ->setURL('/modules/Crowd Assessment/crowdAssess_view.php');
        });

    echo $table->render($lessons);
}
