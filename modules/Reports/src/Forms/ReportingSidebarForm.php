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

namespace Gibbon\Module\Reports\Forms;

use Gibbon\Forms\Form;
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;

/**
 * ReportingSidebarForm
 *
 * @version v19
 * @since   v19
 */
class ReportingSidebarForm extends Form
{
    protected $databaseFormFactory;
    protected $reportingCycleGateway;
    protected $reportingCriteriaGateway;
    protected $session;

    public function __construct(Session $session, ReportingCycleGateway $reportingCycleGateway, ReportingCriteriaGateway $reportingCriteriaGateway, DatabaseFormFactory $databaseFormFactory)
    {
        $this->session = $session;
        $this->databaseFormFactory = $databaseFormFactory;
        $this->reportingCycleGateway = $reportingCycleGateway;
        $this->reportingCriteriaGateway = $reportingCriteriaGateway;
    }

    public function createForm($urlParams)
    {
        $gibbonPersonID = $urlParams['gibbonPersonID'] ?? $this->session->get('gibbonPersonID');

        $form = parent::create('reportingSelector', $this->session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory($this->databaseFormFactory);
        $form->setClass('smallIntBorder w-full mt-4');

        $form->addHiddenValue('q', '/modules/Reports/reporting_write.php');
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('allStudents', $urlParams['allStudents'] ?? '');
        $form->addHiddenValue('gibbonPersonIDStudent', $urlParams['gibbonPersonIDStudent'] ?? '');

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'))->addClass('sm:text-xxs');
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Recent')
                ->setClass('auto-submit w-64 lg:w-40')
                ->selected($urlParams['gibbonSchoolYearID'])
                ->placeholder(null);

        $reportingCycles = $this->reportingCycleGateway->selectReportingCyclesBySchoolYear($urlParams['gibbonSchoolYearID']);
        $row = $form->addRow();
            $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'))->addClass('sm:text-xxs');
            $row->addSelect('gibbonReportingCycleID')
                ->fromResults($reportingCycles)
                ->setClass('auto-submit w-64 lg:w-40')
                ->selected($urlParams['gibbonReportingCycleID'])
                ->placeholder();

        if (!empty($urlParams['gibbonReportingCycleID'])) {
            $criteria = $this->reportingCriteriaGateway->newQueryCriteria()->sortBy(['sequenceNumber', 'nameOrder']);
            $criteriaGroups = $this->reportingCriteriaGateway->queryReportingCriteriaGroupsByCycle($criteria, $urlParams['gibbonReportingCycleID']);

            $row = $form->addRow();
                $row->addLabel('criteriaSelector', __('Scope'))->addClass('sm:text-xxs');
                $row->addSelect('criteriaSelector')
                    ->fromDataSet($criteriaGroups, 'value', 'name', 'scopeName')
                    ->setClass('auto-submit w-64 lg:w-40')
                    ->selected($urlParams['gibbonReportingScopeID'].'-'.$urlParams['scopeTypeID'])
                    ->placeholder();
        } else {
            $form->addHiddenValue('criteriaSelector', '0-0');
        }

        return $form;
        
    }
}
