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
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Domain\Timetable\TimetableDayGateway;

/**
 * Timetable UI: ExceptionsLayer
 *
 * @version  v30
 * @since    v30
 */
class ExceptionsLayer extends AbstractTimetableLayer
{
    protected $timetableDayGateway;

    public function __construct(TimetableDayGateway $timetableDayGateway)
    {
        $this->timetableDayGateway = $timetableDayGateway;

        $this->name = 'Exceptions';
        $this->color = 'gray';
        $this->type = 'timetabled';
        $this->order = 1;
    }

    public function checkAccess(TimetableContext $context) : bool
    {
        return Access::allows('Timetable Admin', 'courseEnrolment_manage_byPerson_edit');
    }
    
    public function loadItems(\DatePeriod $dateRange, TimetableContext $context) 
    {   
        if (empty($context->get('gibbonPersonID'))) return;

        $exceptions = $this->timetableDayGateway->selectTTDayRowClassExceptionsByPersonAndRange($context->get('gibbonPersonID'), $dateRange->getStartDate()->format('Y-m-d'), $dateRange->getEndDate()->format('Y-m-d'))->fetchAll();

        foreach ($exceptions as $exception) {

            $item = $this->createItem($exception['date'])->loadData([
                'type'      => $exception['period'],
                'title'     => Format::courseClassName($exception['courseName'], $exception['className']),
                'subtitle'  => __('Class List Exception'),
                'timeStart' => $exception['timeStart'],
                'timeEnd'   => $exception['timeEnd'],
                'link'      => Url::fromModuleRoute('Departments', 'department_course_class')->withQueryParams(['gibbonCourseClassID' => $exception['gibbonCourseClassID'], 'currentDate' => $exception['date']]),
            ]);

            $item->set('secondaryAction', [
                'name'      => 'edit',
                'label'     => __('Remove Exception'),
                'url'       => Url::fromModuleRoute('Timetable Admin', 'tt_edit_day_edit_class_exception_deleteProcess')->withQueryParams(['gibbonSchoolYearID' => $context->get('gibbonSchoolYearID'), 'gibbonTTID' => $exception['gibbonTTID'], 'gibbonTTDayID' => $exception['gibbonTTDayID'], 'gibbonTTDayRowClassID' => $exception['gibbonTTDayRowClassID'], 'gibbonTTColumnRowID' => $exception['gibbonTTColumnRowID'], 'gibbonCourseClassID' => $exception['gibbonCourseClassID'], 'gibbonPersonID' => $context->get('gibbonPersonID'), 'gibbonTTDayRowClassExceptionID' => $exception['gibbonTTDayRowClassExceptionID']])->directLink(),
                'icon'      => 'user-minus',
                'iconClass' => 'text-gray-600 hover:text-gray-800',
            ]);
        }
    }
}
