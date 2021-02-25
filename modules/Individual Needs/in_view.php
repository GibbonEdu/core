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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php') == false) {
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
        $page->breadcrumbs->add(__('View Student Records'));

        $studentGateway = $container->get(StudentGateway::class);

        $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
        $search = isset($_GET['search'])? $_GET['search'] : '';
        $allStudents = (isset($_GET['allStudents']) ? $_GET['allStudents'] : '');

        // CRITERIA
        $criteria = $studentGateway->newQueryCriteria(true)
            ->searchBy($studentGateway->getSearchableColumns(), $search)
            ->sortBy(['surname', 'preferredName'])
            ->filterBy('all', $allStudents)
            ->fromPOST();

        echo '<h2>';
        echo __('Search');
        echo '</h2>';

        $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/in_view.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
            $row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();

        echo '<h2>';
        echo __('Choose A Student');
        echo '</h2>';
        echo '<p>';
        echo __('This page displays all students enrolled in the school, including those who have not yet met their start date. With the right permissions, you can set Individual Needs status and Individual Education Plan details for any student.');
        echo '</p>';

        $students = $studentGateway->queryStudentsBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

        // DATA TABLE
        $table = DataTable::createPaginated('inView', $criteria);

        $table->addMetaData('filterOptions', [
            'all:on'        => __('All Students')
        ]);

        $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

        // COLUMNS
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) use ($allStudents) {
                return Format::nameLinked($person['gibbonPersonID'], '', $person['preferredName'], $person['surname'], 'Student', true, true, ['subpage' => 'Individual Needs', 'allStudents' => $allStudents]) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
            });
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('rollGroup', __('Roll Group'));

        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($person, $actions) use ($highestAction) {
                if ($person['status'] != 'Full') return;

                if ($highestAction == 'Individual Needs Records_view') {
                    $actions->addAction('view', __('View Individual Needs Details'))
                            ->setURL('/modules/Individual Needs/in_edit.php');
                } else if ($highestAction == 'Individual Needs Records_viewEdit' or $highestAction == 'Individual Needs Records_viewContribute') {
                    $actions->addAction('edit', __('Edit Individual Needs Details'))
                            ->setURL('/modules/Individual Needs/in_edit.php');
                }
            });

        echo $table->render($students);
    }
}
