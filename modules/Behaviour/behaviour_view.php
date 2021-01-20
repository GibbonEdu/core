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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('View Behaviour Records'));

        $search = isset($_GET['search'])? $_GET['search'] : '';

        if ($highestAction == 'View Behaviour Records_all') {
            $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
            $form->setTitle(__('Search'));
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', '/modules/Behaviour/behaviour_view.php');

            $row = $form->addRow();
                $row->addLabel('search',__('Search For'))->description(__('Preferred, surname, username.'));
                $row->addTextField('search')->setValue($search)->maxLength(30);

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session, __('Clear Search'));

            echo $form->getOutput();
        }

        $studentGateway = $container->get(StudentGateway::class);

        // DATA TABLE
        if ($highestAction == 'View Behaviour Records_all') {
            
            $criteria = $studentGateway->newQueryCriteria(true)
                ->searchBy($studentGateway->getSearchableColumns(), $search)
                ->sortBy(['surname', 'preferredName'])
                ->fromPOST();

            $students = $studentGateway->queryStudentsBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], false);

            $table = DataTable::createPaginated('behaviour', $criteria);
            $table->setTitle(__('Choose A Student'));

        } else if ($highestAction == 'View Behaviour Records_myChildren') {
            $students = $studentGateway->selectActiveStudentsByFamilyAdult($_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'])->toDataSet();

            $table = DataTable::create('behaviour');
            $table->setTitle( __('My Children'));
        } else {
            return;
        }

        // COLUMNS
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) use ($guid) {
                $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&subpage=Behaviour&gibbonPersonID='.$person['gibbonPersonID'].'&search=&allStudents=&sort=surname,preferredName';
                return Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true));
            });
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('rollGroup', __('Roll Group'));

        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('search', $search)
            ->format(function ($row, $actions) {
                $actions->addAction('view', __('View Details'))
                    ->setURL('/modules/Behaviour/behaviour_view_details.php');
            });

        echo $table->render($students);
    }
}
