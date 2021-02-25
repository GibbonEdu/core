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
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('View by Student'));

    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $search = $_GET['search'] ?? '';

    // CRITERIA
    $criteria = $reportArchiveEntryGateway->newQueryCriteria(true)
        ->searchBy($reportArchiveEntryGateway->getSearchableColumns(), $search)
        ->filterBy('all', $allStudents)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // FORM
    $form = Form::create('archiveByReport', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Reports/archive_byStudent.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description('Preferred, surname, username.');
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $gibbonSchoolYearID)->selected($gibbonRollGroupID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
        $row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();
    

    // QUERY
    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Past Reports');
    $roleCategory = getRoleCategory($gibbon->session->get('gibbonRoleIDCurrent'), $connection2);

    $reports = $reportArchiveEntryGateway->queryArchiveBySchoolYear($criteria, $gibbonSchoolYearID, $gibbonYearGroupID, $gibbonRollGroupID, $roleCategory, $canViewDraftReports, $canViewPastReports);

    // Data TABLE
    $table = DataTable::createPaginated('reportsView', $criteria)->withData($reports);
    $table->setTitle(__('View'));
    
    $table->modifyRows($container->get(StudentGateway::class)->getSharedUserRowHighlighter());

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function ($person) use ($guid, $allStudents) {
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&search=&allStudents='.$allStudents.'&sort=surname,preferredName';
            return Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true))
                   .'<br/>'.Format::small(Format::userStatusInfo($person));
        });

    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('rollGroup', __('Roll Group'));
    $table->addColumn('count', __('Reports'));

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
        ->addParam('gibbonRollGroupID', $gibbonRollGroupID)
        ->addParam('allStudents', $allStudents)
        ->addParam('search', $criteria->getSearchText())
        ->format(function ($report, $actions) {
            $actions->addAction('view', __('View'))
                ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                ->setURL('/modules/Reports/archive_byStudent_view.php');
        });

    echo $table->render($reports);
}
