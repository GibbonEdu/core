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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingValueGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/progress_studentNameConflicts.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Student Name Conflicts'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingValueGateway = $container->get(ReportingValueGateway::class);
    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }
    
    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Reports/progress_studentNameConflicts.php');

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($gibbonReportingCycleID)
            ->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (empty($gibbonReportingCycleID)) return;
    
    // Get all student preferred names
    $names = $container->get(StudentGateway::class)->selectActiveStudentNames($session->get('gibbonSchoolYearID'))->fetchAll(\PDO::FETCH_COLUMN, 0);
    sort($names);
    $names = array_unique($names);

    // Get all student comments in the selected reporting cycle
    $reportingValues = $reportingValueGateway->selectReportingCommentsByCycle($gibbonReportingCycleID)->fetchAll();    
    $foundNames = [];

    // Check all comments for names that don't match this student's name
    foreach ($reportingValues as $values) {
        foreach ($names as $name) {
            if ($values['preferredName'] == $name) continue;

            $matches = [];
            if (preg_match('/\b'.$name.'\b/', $values['comment'], $matches)) {
                $key = $values['gibbonReportingValueID'];
                $foundNames[$key]['gibbonPersonIDStudent'] = $values['gibbonPersonID'];
                $foundNames[$key]['gibbonReportingScopeID'] = $values['gibbonReportingScopeID'];
                $foundNames[$key]['criteriaName'] = $values['criteriaName'];
                $foundNames[$key]['scopeType'] = $values['scopeType'];
                $foundNames[$key]['scopeTypeID'] = $values['scopeTypeID'];
                $foundNames[$key]['preferredName'] = $values['preferredName'];
                $foundNames[$key]['surname'] = $values['surname'];
                $foundNames[$key]['foundNames'][] = $name;
            }
        }
    }

    // DATA TABLE
    $table = DataTable::create('nameCheck');
    $table->setTitle(__('Student Name Conflicts'));
    $table->setDescription(__('This report checks all comments in a reporting cycle for any other student names that do not match the name of the student commented on.'));

    $table->addColumn('name', __('Name'))
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true, true]));
    
    $table->addColumn('scopeType', __('Scope Type'))
        ->format(function ($values) {
            return $values['scopeType'].'<br/>'.Format::small($values['criteriaName']);
        });

    $table->addColumn('found', __('Found Names'))
        ->format(function ($values) {
            if (!empty($values['foundNames']) && is_array($values['foundNames'])) {
                sort($values['foundNames']);
                $values['foundNames'] = array_unique($values['foundNames']);
            }
            return Format::tag(implode(', ', $values['foundNames'] ?? []), 'warning');
        });

    if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_write_byStudent.php')) {
        $table->addActionColumn()
            ->addParam('gibbonReportingCycleID', $gibbonReportingCycleID)
            ->addParam('gibbonReportingScopeID')
            ->addParam('gibbonPersonIDStudent')
            ->addParam('scopeType')
            ->addParam('scopeTypeID')
            ->format(function ($values, $actions) {
                $actions->addAction('view', __('View'))
                        ->setURL('/modules/Reports/reporting_write_byStudent.php');
            });
    }

    echo $table->render($foundNames);
}
