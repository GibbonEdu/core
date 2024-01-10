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

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $search = $_GET['search'] ?? '';

    // CRITERIA
    $criteria = $reportArchiveEntryGateway->newQueryCriteria(true)
        ->searchBy($reportArchiveEntryGateway->getSearchableColumns(), $search)
        ->filterBy('all', $allStudents)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
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
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $gibbonSchoolYearID)->selected($gibbonFormGroupID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
        $row->addCheckbox('allStudents')->setValue('on')->checked($allStudents);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    // QUERY
    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Past Reports');
    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

    $reports = $reportArchiveEntryGateway->queryArchiveBySchoolYear($criteria, $gibbonSchoolYearID, $gibbonYearGroupID, $gibbonFormGroupID, $roleCategory, $canViewDraftReports, $canViewPastReports);

    // Data TABLE
    $table = DataTable::createPaginated('reportsView', $criteria)->withData($reports);
    $table->setTitle(__('View'));

    $table->modifyRows($container->get(StudentGateway::class)->getSharedUserRowHighlighter());

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function ($person) use ($session, $allStudents) {
            $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&search=&allStudents='.$allStudents.'&sort=surname,preferredName';
            return Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true))
                   .'<br/>'.Format::small(Format::userStatusInfo($person));
        });

    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('formGroup', __('Form Group'));
    $table->addColumn('count', __('Reports'));

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
        ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
        ->addParam('allStudents', $allStudents)
        ->addParam('search', $criteria->getSearchText())
        ->format(function ($report, $actions) {
            $actions->addAction('view', __('View'))
                ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                ->setURL('/modules/Reports/archive_byStudent_view.php');
        });

    echo $table->render($reports);
}
