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

namespace Gibbon\UI\Timetable\Layers;

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Staff\StaffCoverageGateway;

/**
 * Timetable UI: StaffCoverLayer
 *
 * @version  v29
 * @since    v29
 */
class StaffCoverLayer extends AbstractTimetableLayer
{
    protected $staffCoverageGateway;

    public function __construct(StaffCoverageGateway $staffCoverageGateway)
    {
        $this->staffCoverageGateway = $staffCoverageGateway;

        $this->name = 'Staff Cover';
        $this->color = 'pink';
        $this->order = 10;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if (!$context->has('gibbonSchoolYearID') || !$context->has('gibbonPersonID')) return;

        $criteria = $this->staffCoverageGateway->newQueryCriteria()
            ->filterBy('dateStart', $dateRange->getStartDate()->format('Y-m-d'))
            ->filterBy('dateEnd', $dateRange->getEndDate()->format('Y-m-d'))
            ->filterBy('status', 'Accepted');
                    
        $staffCoverage = $this->staffCoverageGateway->queryCoverageByPersonCovering($criteria, $context->get('gibbonSchoolYearID'), $context->get('gibbonPersonID'));


        foreach ($staffCoverage as $coverage) {
            $fullName = !empty($coverage['surnameAbsence']) 
                ? Format::name($coverage['titleAbsence'], $coverage['preferredNameAbsence'], $coverage['surnameAbsence'], 'Staff', false, true)
                : Format::name($coverage['titleStatus'], $coverage['preferredNameStatus'], $coverage['surnameStatus'], 'Staff', false, true);

            $this->createItem($coverage['date'])->loadData([
                'type'      => __('Covering'),
                'title'     => $coverage['contextName'],
                'subtitle'  => $fullName,
                'allDay'    => $coverage['allDay'] == 'Y',
                'link'      => Url::fromModuleRoute('Staff', 'coverage_my.php'),
                'timeStart' => $coverage['timeStart'],
                'timeEnd'   => $coverage['timeEnd'],
            ]);
        }
    }
}
