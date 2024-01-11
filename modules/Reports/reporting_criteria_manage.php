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
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $urlParams = [
        'gibbonReportingCycleID' => $_GET['gibbonReportingCycleID'] ?? '',
        'gibbonReportingScopeID' => $_GET['gibbonReportingScopeID'] ?? '',
        'gibbonYearGroupID'      => $_GET['gibbonYearGroupID'] ?? '',
        'gibbonFormGroupID'      => $_GET['gibbonFormGroupID'] ?? '',
        'gibbonCourseID'         => $_GET['gibbonCourseID'] ?? '',
    ];

    $page->breadcrumbs->add(__('Manage Criteria'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);

    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Reports/reporting_criteria_manage.php');
    $form->addHiddenValue('gibbonReportingScopeID', $urlParams['gibbonReportingScopeID']);
    $form->addHiddenValue('gibbonReportingCycleID', $urlParams['gibbonReportingCycleID']);

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($urlParams['gibbonReportingCycleID'])
            ->placeholder()
            ->required();

    $reportingScopes = $reportingScopeGateway->selectReportingScopesBySchoolYear($gibbonSchoolYearID)->fetchAll();
    $scopesChained = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'chained'));
    $scopesOptions = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'name'));
    
    $row = $form->addRow();
        $row->addLabel('gibbonReportingScopeID', __('Scope'));
        $row->addSelect('gibbonReportingScopeID')
            ->fromArray($scopesOptions)
            ->selected($urlParams['gibbonReportingScopeID'])
            ->chainedTo('gibbonReportingCycleID', $scopesChained)
            ->placeholder();

    if (!empty($urlParams['gibbonReportingScopeID']) && !empty($urlParams['gibbonReportingCycleID'])) {
        $reportingScope = $reportingScopeGateway->getByID($urlParams['gibbonReportingScopeID']);
        $reportingCycle = $reportingCycleGateway->getByID($reportingScope['gibbonReportingCycleID']);
        if (empty($reportingScope) || empty($reportingCycle)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        if ($reportingScope['scopeType'] == 'Year Group') {
            $scopeTypeID = $urlParams['gibbonYearGroupID'];
            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupID', __('Year Group'));
                $row->addSelectYearGroup('gibbonYearGroupID')
                    ->selected($urlParams['gibbonYearGroupID']);
        }

        if ($reportingScope['scopeType'] == 'Form Group') {
            $scopeTypeID = $urlParams['gibbonFormGroupID'];
            $row = $form->addRow();
                $row->addLabel('gibbonFormGroupID', __('Form Group'));
                $row->addSelectFormGroup('gibbonFormGroupID', $reportingCycle['gibbonSchoolYearID'])
                    ->selected($urlParams['gibbonFormGroupID']);
        }

        if ($reportingScope['scopeType'] == 'Course') {
            $scopeTypeID = $urlParams['gibbonCourseID'];
            $row = $form->addRow();
                $row->addLabel('gibbonCourseID', __('Course'));
                $row->addSelectCourseByYearGroup('gibbonCourseID', $reportingCycle['gibbonSchoolYearID'], $reportingCycle['gibbonYearGroupIDList'])
                    ->selected($urlParams['gibbonCourseID']);
        }
    }

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (empty($urlParams['gibbonReportingCycleID'])) {
        return;
    }

    if (empty($urlParams['gibbonReportingScopeID'])) {
        $table = DataTable::create('reportScopes')->setTitle(__('Criteria'));
        $table->addHeaderAction('scopes', __('Manage Scopes & Criteria'))
            ->setURL('/modules/Reports/reporting_scopes_manage.php')
            ->addParam('gibbonReportingCycleID', $urlParams['gibbonReportingCycleID'])
            ->setIcon('markbook')
            ->displayLabel();

        echo $table->render([]);
        return;
    }

    // QUERY
    $criteria = $reportingCriteriaGateway->newQueryCriteria(true)
        ->sortBy(['scopeSequence', 'gibbonReportingCriteria.sequenceNumber'])
        ->fromPOST();

    $reportingCriteria = $reportingCriteriaGateway->queryReportingCriteriaByScope($criteria, $urlParams['gibbonReportingScopeID'], $reportingScope['scopeType'], $scopeTypeID);

    // DATA TABLE
    $table = empty($scopeTypeID) ? DataTable::createPaginated('reportCriteriaManage', $criteria) : DataTable::create('reportCriteriaManage');
    $table->setTitle(__('Criteria'));

    if (empty($urlParams['gibbonYearGroupID']) && empty($urlParams['gibbonFormGroupID']) && empty($urlParams['gibbonCourseID'])) {
        $table->addHeaderAction('addMulti', __('Add Multiple'))
            ->setIcon('page_new_multi')
            ->setURL('/modules/Reports/reporting_criteria_manage_addMultiple.php')
            ->addParam('gibbonReportingCycleID', $urlParams['gibbonReportingCycleID'])
            ->addParam('gibbonReportingScopeID', $urlParams['gibbonReportingScopeID'])
            ->displayLabel();
    } else {
        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Reports/reporting_criteria_manage_add.php')
            ->addParam('gibbonReportingCycleID', $urlParams['gibbonReportingCycleID'])
            ->addParam('gibbonReportingScopeID', $urlParams['gibbonReportingScopeID'])
            ->addParam('gibbonYearGroupID', $urlParams['gibbonYearGroupID'])
            ->addParam('gibbonFormGroupID', $urlParams['gibbonFormGroupID'])
            ->addParam('gibbonCourseID', $urlParams['gibbonCourseID'])
            ->displayLabel();
    }

    $table->addHeaderAction('criteria', __('Manage Criteria Types'))
            ->setIcon('markbook')
            ->setURL('/modules/Reports/criteriaTypes_manage.php')
            ->displayLabel()
            ->prepend(' | ');

    if (empty($scopeTypeID)) {
        $table->addColumn('scopeTypeName', $reportingScope['scopeType'])
            ->format(function ($reportingCriteria) use (&$urlParams) {
                $url = './index.php?q=/modules/Reports/reporting_criteria_manage.php&'.http_build_query([
                    'gibbonYearGroupID' => $reportingCriteria['gibbonYearGroupID'],
                    'gibbonFormGroupID' => $reportingCriteria['gibbonFormGroupID'],
                    'gibbonCourseID' => $reportingCriteria['gibbonCourseID']
                    ] + $urlParams);
                return Format::link($url, $reportingCriteria['scopeTypeName']);
            });
    } else {
        $table->addDraggableColumn('gibbonReportingCriteriaID', $session->get('absoluteURL').'/modules/Reports/reporting_criteria_manage_editOrderAjax.php', ['gibbonReportingCycleID' => $urlParams['gibbonReportingCycleID'], 'gibbonReportingScopeID' => $urlParams['gibbonReportingScopeID']]);
    }

    $table->addColumn('name', __('Criteria'));
    $table->addColumn('criteriaType', __('Type'));
    $table->addColumn('target', __('Target'));
    $table->addColumn('values', __('Status'))
        ->format(function ($reportingCriteria) {
            return ($reportingCriteria['values'] > 0)
                ? '<span class="tag warning" title="'.__('This criteria is already in use in {count} locations. It cannot be deleted or changed to a different criteria type.', ['count' => $reportingCriteria['values']]).'">'.__('Locked').'</span>'
                : '<span class="tag dull" title="'.__('This criteria has not been used yet. It can safely be edited or deleted.').'">'.__('Unlocked').'</span>';
        });

    $table->addActionColumn()
        ->addParam('gibbonReportingCycleID', $urlParams['gibbonReportingCycleID'])
        ->addParam('gibbonReportingScopeID', $urlParams['gibbonReportingScopeID'])
        ->addParam('gibbonYearGroupID', $urlParams['gibbonYearGroupID'])
        ->addParam('gibbonFormGroupID', $urlParams['gibbonFormGroupID'])
        ->addParam('gibbonCourseID', $urlParams['gibbonCourseID'])
        ->addParam('gibbonReportingCriteriaID')
        ->format(function ($reportingCriteria, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/reporting_criteria_manage_edit.php');

            if ($reportingCriteria['values'] <= 0) {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Reports/reporting_criteria_manage_delete.php');
            }
        });
    

    echo $table->render($reportingCriteria);
}
