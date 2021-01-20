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
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Staff\StaffGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
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
        $page->breadcrumbs->add(__('View Timetable by Person'));

        $gibbonPersonID = isset($_GET['gibbonPersonID']) ? $_GET['gibbonPersonID'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $allUsers = (isset($_GET['allUsers']) && $_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Staff') ? $_GET['allUsers'] : '';

        $studentGateway = $container->get(StudentGateway::class);
        $staffGateway = $container->get(StaffGateway::class);

        $canViewAllTimetables = $highestAction == 'View Timetable by Person' || $highestAction == 'View Timetable by Person_allYears';

        if ($canViewAllTimetables) {
            $criteria = $studentGateway->newQueryCriteria(true)
                ->searchBy($studentGateway->getSearchableColumns(), $search)
                ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->filterBy('all', $allUsers)
                ->fromPOST();


            $form = Form::create('ttView', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
            $form->setClass('noIntBorder fullWidth');
            $form->setTitle(__('Search'));

            $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/tt.php');

            $row = $form->addRow();
                $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
                $row->addTextField('search')->setValue($criteria->getSearchText());

            if ($gibbon->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
                $row = $form->addRow();
                    $row->addLabel('allUsers', __('All Users'))->description(__('Include non-staff, non-student users.'));
                    $row->addCheckbox('allUsers')->checked($allUsers);
            }

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session);

            echo $form->getOutput();
        }

        echo '<h2>';
        echo __('Choose A Person');
        echo '</h2>';

        if ($highestAction == 'View Timetable by Person_my') {
            $role = getRoleCategory($_SESSION[$guid]['gibbonRoleIDPrimary'], $connection2);
            if ($role == 'Student') {
                $result = $studentGateway->selectActiveStudentByPerson($_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID']);
            } else {
                $result = $staffGateway->selectStaffByID($_SESSION[$guid]['gibbonPersonID'], 'Teaching');
            }
            $users = $result->toDataSet();

            $table = DataTable::create('timetables');

        } else if ($highestAction == 'View Timetable by Person_myChildren') {
            $result = $studentGateway->selectActiveStudentsByFamilyAdult($_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID']);
            $users = $result->toDataSet();

            $table = DataTable::create('timetables');

        } else if ($canViewAllTimetables) {

            $users = $studentGateway->queryStudentsAndTeachersBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $gibbon->session->get('gibbonRoleIDCurrentCategory'));

            $table = DataTable::createPaginated('timetables', $criteria);

            $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

            $table->addMetaData('filterOptions', [
                'role:student'    => __('Role').': '.__('Student'),
                'role:staff'      => __('Role').': '.__('Staff'),
            ]);

            if ($criteria->hasFilter('all')) {
                $table->addMetaData('filterOptions', [
                    'all:on'          => __('All Users'),
                    'status:full'     => __('Status').': '.__('Full'),
                    'status:left'     => __('Status').': '.__('Left'),
                    'status:expected' => __('Status').': '.__('Expected'),
                    'date:starting'   => __('Before Start Date'),
                    'date:ended'      => __('After End Date'),
                ]);
            }
        }

        if (!$canViewAllTimetables && count($users) == 0) {
            echo '<div class="error">';
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
            return;
        }

        // COLUMNS
        $table->addColumn('name', __('Name'))
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($person) {
                $roleCategory = ($person['roleCategory'] == 'Student' || !empty($person['yearGroup']))? 'Student' : 'Staff';
                return Format::name('', $person['preferredName'], $person['surname'], $roleCategory, true, true);
            });
        if ($canViewAllTimetables) {
            $table->addColumn('roleCategory', __('Role Category'))
                ->format(function($person) {
                    return __($person['roleCategory']) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
                });
        }
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('rollGroup', __('Roll Group'));

        $actions = $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->format(function ($person, $actions) {
                $actions->addAction('view', __('View Details'))
                    ->setURL('/modules/Timetable/tt_view.php');
            });

        if ($canViewAllTimetables) {
            $actions->addParam('search', $criteria->getSearchText(true))
                    ->addParam('allUsers', $criteria->getFilterValue('all'));
        }

        echo $table->render($users);
    }
}
