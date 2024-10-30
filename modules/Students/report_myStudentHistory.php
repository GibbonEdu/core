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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/report_myStudentHistory.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('My Student History'));

    // Query categories
    $studentGateway = $container->get(StudentGateway::class);

    $criteria = $studentGateway->newQueryCriteria()
        ->sortBy('dob', 'ASC')
        ->sortBy('surname')
        ->fromPOST();

    $students = $studentGateway->queryStudentHistoryByPerson($criteria, $session->get('gibbonPersonID'));

    // Render table
    $gridRenderer = new GridView($container->get('twig'));
    $table = $container->get(DataTable::class)->setRenderer($gridRenderer);
    $table->getRenderer()->setCriteria($criteria);
    $table->setId('myStudentHistory');
    $table->setTitle(__('Students'));
    $table->setDescription(__("This page allows a teacher to see every student they've ever taught on a single page, in reverse chronological order by student age. Only displays students who have a photo. Hover over a student to see their name."));
    $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-2 text-center');

    $table->addColumn('student')
        ->notSortable()
        ->addClass('h-full')
        ->format(function($values) {
            $photo = Format::userPhoto($values['image_240'], 'md', '');
            $title = Format::name('', $values['preferredName'], $values['surname'], 'Student', false, true).'<br/>'.Format::date($values['dob']);
            
            return '<div title="'.$title.'" class="mb-4">'.$photo.'</div>';
        });

    echo $table->render($students);

}
