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

use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/progress_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Progress by Person'));

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $gibbonReportingScopeID = $_GET['gibbonReportingScopeID'] ?? '';
    $reportingProgressGateway = $container->get(ReportingProgressGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Reports/progress_byPerson.php');
    $form->addHiddenValue('gibbonReportingScopeID', $gibbonReportingScopeID);

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($gibbonReportingCycleID)
            ->placeholder();

    $reportingScopes = $reportingScopeGateway->selectReportingScopesBySchoolYear($gibbonSchoolYearID)->fetchAll();
    $scopesChained = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'chained'));
    $scopesOptions = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'name'));
    
    $row = $form->addRow();
        $row->addLabel('gibbonReportingScopeID', __('Scope'));
        $row->addSelect('gibbonReportingScopeID')
            ->fromArray($scopesOptions)
            ->chainedTo('gibbonReportingCycleID', $scopesChained)
            ->selected($gibbonReportingScopeID)
            ->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    // QUERY
    $criteria = $reportingProgressGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    $progress = $reportingProgressGateway->queryReportingProgressByPerson($criteria, $gibbonSchoolYearID, $gibbonReportingCycleID, $gibbonReportingScopeID)->toArray();
    $progressByPerson = array_reduce($progress, function ($group, $item) {
        if (isset($group[$item['gibbonPersonID']])) {
            $group[$item['gibbonPersonID']]['progressCount'] += $item['progressCount'];
            $group[$item['gibbonPersonID']]['totalCount'] += $item['totalCount'];
        } else {
            $group[$item['gibbonPersonID']] = $item;
        }
        return $group;
    }, []);

    // DATA TABLE
    $table = DataTable::createPaginated('progress', $criteria);
    $table->setTitle(__('View'));

    $table->addColumn('name', __('Name'))
        ->width('30%')
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Staff', true, true);
        });
    $table->addColumn('progress', __('Progress'))
        ->width('40%')
        ->format(function ($reporting) use (&$page) {
            return $page->fetchFromTemplate('ui/writingProgress.twig.html', [
                'progressCount' => $reporting['progressCount'],
                'totalCount'    => $reporting['totalCount'],
                'width'         => 'w-64',
            ]);
        });


    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->format(function ($reportingCycle, $actions) {
            $actions->addAction('view', __('View'))
                    ->setURL('/modules/Reports/reporting_my.php');
        });

    echo $table->render(new DataSet(array_values($progressByPerson)));

}
