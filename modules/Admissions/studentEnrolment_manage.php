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

if (isActionAccessible($guid, $connection2, '/modules/Admissions/studentEnrolment_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Student Enrolment'));

    $search = $_GET['search'] ?? '';
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $studentGateway = $container->get(StudentGateway::class);

        $criteria = $studentGateway->newQueryCriteria(true)
            ->searchBy($studentGateway->getSearchableColumns(), $search)
            ->sortBy(['surname', 'preferredName'])
            ->fromPOST();

        echo '<h3>';
        echo __('Search');
        echo '</h3>';

        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php','get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/studentEnrolment_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText());

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';
        echo '<p>';
        echo __("Students highlighted in red are marked as 'Full' but have either not reached their start date, or have exceeded their end date.");
        echo '<p>';

        $students = $studentGateway->queryStudentEnrolmentBySchoolYear($criteria, $gibbonSchoolYearID);

        // DATA TABLE
        $table = DataTable::createPaginated('students', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Admissions/studentEnrolment_manage_add.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('search', $criteria->getSearchText(true))
            ->displayLabel();
    
        $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

        $table->addMetaData('filterOptions', [
            'status:full'     => __('Status').': '.__('Full'),
            'status:left'     => __('Status').': '.__('Left'),
            'status:expected' => __('Status').': '.__('Expected'),
            'date:starting'   => __('Before Start Date'),
            'date:ended'      => __('After End Date'),
        ]);

        // COLUMNS
        $table->addColumn('student', __('Student'))
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) {
                return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
            });
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('formGroup', __('Form Group'))
            ->description(__('Roll Order'))
            ->format(function($row) {
                return $row['formGroup'] . (!empty($row['rollOrder']) ? '<br/><span class="small emphasis">'.$row['rollOrder'].'</span>' : '');
            });

        $table->addActionColumn()
            ->addParam('gibbonStudentEnrolmentID')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($row, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Admissions/studentEnrolment_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Admissions/studentEnrolment_manage_delete.php');
            });

        echo $table->render($students);
    }
}
