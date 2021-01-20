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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_new') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $type = $_GET['type'] ?? '';
    $ignoreEnrolment = $_GET['ignoreEnrolment'] ?? false;
    $startDateFrom = $_GET['startDateFrom'] ?? '';
    $startDateTo = $_GET['startDateTo'] ?? '';
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('New Students'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
        $form->setTitle(__('Choose Options'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_students_new.php");

        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray(array('Current School Year' => __('Current School Year'), 'Date Range' => __('Date Range')))->selected($type)->required();

        $form->toggleVisibilityByClass('dateRange')->onSelect('type')->when('Date Range');

        $row = $form->addRow()->addClass('dateRange');
            $row->addLabel('startDateFrom', __('From Date'))->description('Earliest student start date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
            $row->addDate('startDateFrom')->setValue($startDateFrom)->required();

        $row = $form->addRow()->addClass('dateRange');
            $row->addLabel('startDateTo', __('To Date'))->description('Latest student start date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
            $row->addDate('startDateTo')->setValue($startDateTo)->required();

        $row = $form->addRow()->addClass('dateRange');
            $row->addLabel('ignoreEnrolment', __('Ignore Enrolment'))->description(__('This is useful for picking up students who are set to Full, have a start date but are not yet enrolled.'));
            $row->addCheckbox('ignoreEnrolment')->checked($ignoreEnrolment);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    if (empty($type)) {
        return;
    }

    $reportGateway = $container->get(StudentReportGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    // CRITERIA
    $criteria = $reportGateway->newQueryCriteria(true)
        ->sortBy(['rollGroup', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $students = $reportGateway->queryStudentStatusBySchoolYear(
        $criteria,
        $gibbonSchoolYearID,
        'Full',
        Format::dateConvert($startDateFrom),
        Format::dateConvert($startDateTo),
        $ignoreEnrolment
    );

    // Join a set of family adults per student
    $people = $students->getColumn('gibbonPersonID');
    $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people, true)->fetchGrouped();
    $students->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentsNew', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('New Students'));

    $table->modifyRows($reportGateway->getSharedUserRowHighlighter());

    if ($type == 'Date Range') {
        $table->setDescription(__('This report shows all students whose Start Date is on or between the indicated dates.'));
    } else {
        $table->setDescription(__('This report shows all students who are newly arrived in the school during the current academic year (e.g. they were not enrolled in the previous academic year).'));
    }

    $table->addRowCountColumn($students->getPageFrom());
    $table->addColumn('student', __('Student'))
        ->context('primary')
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($student) {
            return Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true)
                 . '<br/><small><i>'.Format::userStatusInfo($student).'</i></small>';
        });
    $table->addColumn('rollGroup', __('Roll Group'))
        ->context('primary');
    $table->addColumn('username', __('Username'));
    $table->addColumn('officialName', __('Official Name'));
    $table->addColumn('dateStart', __('Start Date'))
        ->context('secondary')
        ->format(Format::using('date', 'dateStart'));
    $table->addColumn('lastSchool', __('Last School'));

    $view = new View($container->get('twig'));
    $table->addColumn('contacts', __('Parents'))
        ->width('30%')
        ->notSortable()
        ->format(function ($student) use ($view) {
            return $view->fetchFromTemplate(
                'formats/familyContacts.twig.html',
                ['familyAdults' => $student['familyAdults'], 'includeCitizenship' => true]
            );
        });

    echo $table->render($students);
}
