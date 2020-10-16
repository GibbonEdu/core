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
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_myStudentHistory.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('My Student History'));

    // Query categories
    $studentGateway = $container->get(StudentGateway::class);

    $criteria = $studentGateway->newQueryCriteria()
        ->sortBy(['dob'], 'DESC') //Is it possible to add in second sort, in name, but ASC?
        ->fromPOST();

    $students = $studentGateway->queryStudentHistoryByPerson($criteria, $gibbon->session->get('gibbonPersonID'));

    // Render table
    $gridRenderer = new GridView($container->get('twig'));
    $table = $container->get(DataTable::class)->setRenderer($gridRenderer);

    $table->setTitle(__('Students'));

    $table->addColumn('student')
    ->notSortable()
    ->addClass('h-full')
    ->format(function($values) use ($guid, $gibbon) {
        $return = null;
        $return .= Format::userPhoto($values['image_240'], 'md', '')."<br/>";
        $return .= Format::name('', $values['preferredName'], $values['surname'], 'Student', false, true)."<br/>";
        $return .= "<span class='text-xxs italic'>".Format::date($values['dob'])."</span><br/><br/>";
        return $return;
    });

    echo $table->render($students);

}
