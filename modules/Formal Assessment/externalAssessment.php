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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View All Assessments'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        echo Format::alert(__('The highest grouped action cannot be determined.'));
        return;
    }

    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ??  '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Formal Assessment/externalAssessment.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, regardless of status and current enrolment. Some data may not display.'));
        $row->addCheckbox('allStudents')->checked($allStudents);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    $studentGateway = $container->get(StudentGateway::class);
    $criteria = $studentGateway->newQueryCriteria(true)
        ->searchBy($studentGateway->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('all',$allStudents)
        ->fromPOST();

    $students = $studentGateway->queryStudentsBySchoolYear($criteria, $gibbonSchoolYearID);

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Formal Assessment/externalAssessment_manage_processBulk.php');
    $form->setTitle(__('Choose A Student'));
    $form->addHiddenValue('search', $search);


    // DATA TABLE
    $table = $form->addRow()->addDataTable('students', $criteria)->withData($students);
    $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

    $table->addMetaData('filterOptions', [
        'all:on'        => __('All Students')
    ]);

    if ($criteria->hasFilter('all')) {
        $table->addMetaData('filterOptions', [
            'status:full'     => __('Status').': '.__('Full'),
            'status:expected' => __('Status').': '.__('Expected'),
            'date:starting'   => __('Before Start Date'),
            'date:ended'      => __('After End Date'),
        ]);
    }

    // BULK ACTION
    if ($highestAction == 'External Assessment Data_manage') {
        $bulkActions = array(
            'Add' => __('Add'),
        );

        $col = $form->createBulkActionColumn($bulkActions);
            $sql = "SELECT gibbonExternalAssessmentID as value, name FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name";
            $col->addSelect('gibbonExternalAssessmentID')
                ->fromQuery($pdo, $sql)
                ->required()
                ->placeholder()
                ->setClass('w-32');
            $col->addDate('date')
                ->placeholder(__('Date'))
                ->setClass('w-32');
            $col->addYesNo('copyToGCSECheck')
                ->required()
                ->placeholder(__('Copy Target Grades?'))
                ->setClass('w-32 copyToGCSE');
            $col->addSubmit(__('Go'));

        $form->toggleVisibilityByClass('copyToGCSE')->onSelect('gibbonExternalAssessmentID')->when('0002');

        $table->addMetaData('bulkActions', $col);
    }

    // COLUMNS
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
        });
    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('formGroup', __('Form Group'));

    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->addParam('search', $search)
        ->addParam('allStudents', $allStudents)
        ->format(function ($row, $actions) {
            $actions->addAction('view', __('View Details'))
                ->setURL('/modules/Formal Assessment/externalAssessment_details.php');
        });

    if ($highestAction == 'External Assessment Data_manage') {
        $table->addCheckboxColumn('gibbonPersonID');
    }

    echo $form->getOutput();
}
