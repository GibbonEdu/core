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
use Gibbon\Domain\Staff\StaffAbsenceGateway;

/**
 * Timetable UI: StaffAbsenceLayer
 *
 * @version  v29
 * @since    v29
 */
class StaffAbsenceLayer extends AbstractTimetableLayer
{
    protected $staffAbsenceGateway;

    public function __construct(StaffAbsenceGateway $staffAbsenceGateway)
    {
        $this->staffAbsenceGateway = $staffAbsenceGateway;

        $this->name = 'Staff Absence';
        $this->color = 'gray';
        $this->type = 'optional';
        $this->order = 15;
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {
        if (!$context->has('gibbonPersonID')) return;

        $criteria = $this->staffAbsenceGateway->newQueryCriteria()
            ->filterBy('dateStart', $dateRange->getStartDate()->format('Y-m-d'))
            ->filterBy('dateEnd', $dateRange->getEndDate()->format('Y-m-d'))
            ->filterBy('status', 'Approved');
                    
        $staffAbsences = $this->staffAbsenceGateway->queryAbsencesByPerson($criteria, $context->get('gibbonPersonID'), false);
        // $canViewAbsences = isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php');

        foreach ($staffAbsences as $absence) {
            $canViewAbsences = $absence['gibbonPersonID'] == $context->has('gibbonPersonID');
            
            $coverageName = Format::name($absence['titleCoverage'], $absence['preferredNameCoverage'], $absence['surnameCoverage'], 'Staff', false, true);
            $link = Url::fromModuleRoute('Staff', 'absences_view_details.php')->withQueryParam('gibbonStaffAbsenceID', $absence['gibbonStaffAbsenceID']);

            $this->createItem($absence['dateStart'], $absence['allDay'] == 'Y')->loadData([
                'type'      => __('Absent'),
                'title'     => $absence['allDay'] == 'Y' ? __('Absent') : '',
                // 'subtitle'  => $coverageName,
                'link'      => $canViewAbsences ? $link : '',
                'timeStart' => $absence['allDay'] == 'N' ? $absence['timeStart'] : null,
                'timeEnd'   => $absence['allDay'] == 'N' ? $absence['timeEnd'] : null,
                'style'     => 'stripe',
            ]);
        }
    }
}
