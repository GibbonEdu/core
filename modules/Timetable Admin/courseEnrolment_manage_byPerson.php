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
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Course Enrolment by Person'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if (empty($gibbonSchoolYearID)) {
        $page->addError(__('The specified record does not exist.'));
    } else {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $allUsers = isset($_GET['allUsers'])? $_GET['allUsers'] : '';
        $search = isset($_GET['search'])? $_GET['search'] : '';

        // CRITERIA
        $studentGateway = $container->get(StudentGateway::class);

        $criteria = $studentGateway->newQueryCriteria(true)
            ->searchBy($studentGateway->getSearchableColumns(), $search)
            ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->filterBy('all', $allUsers)
            ->fromPOST();

        echo '<h3>';
        echo __('Filters');
        echo '</h3>'; 
        
        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/courseEnrolment_manage_byPerson.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addLabel('allUsers', __('All Users'))->description(__('Include non-staff, non-student users.'));
            $row->addCheckbox('allUsers')->setValue('on')->checked($allUsers);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';
            
        $users = $studentGateway->queryStudentsAndTeachersBySchoolYear($criteria, $gibbonSchoolYearID, $session->get('gibbonRoleIDCurrentCategory'));

        // DATA TABLE
        $table = DataTable::createPaginated('courseEnrolmentByPerson', $criteria);

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

        // COLUMNS
        $table->addColumn('name', __('Name'))
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($person) {
                $roleCategory = ($person['roleCategory'] == 'Student' || !empty($person['yearGroup']))? 'Student' : 'Staff';
                return Format::name('', $person['preferredName'], $person['surname'], $roleCategory, true, true);
            });
        $table->addColumn('roleCategory', __('Role Category'))
            ->format(function($person) {
                return __($person['roleCategory']) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
            });
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('formGroup', __('Form Group'));

        $actions = $table->addActionColumn()
            ->addParam('search', $criteria->getSearchText(true))
            ->addParam('allUsers', $allUsers)
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonPersonID')
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                        ->addParam('type', $person['roleCategory'])
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php');
            });

        echo $table->render($users);
    }
}
