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

namespace Gibbon\Tables\Prefab;

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Planner\PlannerEntryGateway;


/**
 * TodaysLessonsTable
 *
 * @version v28
 * @since   v28
 */
class TodaysLessonsTable
{
    protected $db;
    protected $settingGateway;
    protected $plannerEntryGateway;

    protected $homeworkNameSingular;
    protected $homeworkNamePlural;

    public function __construct(Connection $db, SettingGateway $settingGateway, PlannerEntryGateway $plannerEntryGateway)
    {
        $this->db = $db;
        $this->db = $settingGateway;
        $this->plannerEntryGateway = $plannerEntryGateway;

        $this->homeworkNameSingular = $settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');
        $this->homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
    }

    public function create($gibbonSchoolYearID, $gibbonPersonID, $viewingAs = 'Student')
    {
        $criteria = $this->plannerEntryGateway->newQueryCriteria()
            ->sortBy('date', 'ASC')
            ->sortBy('timeStart', 'ASC')
            ->fromPOST('todaysLessons');

        $planner = $this->plannerEntryGateway->queryPlannerByDate($criteria, $gibbonSchoolYearID, $gibbonPersonID, date('Y-m-d'), $viewingAs);

        $table = DataTable::create('todaysLessons')->withData($planner);
        $table->setTitle(__("Today's Lessons"));

        $table->addMetaData('blankSlate', __('There are no lessons on this date.'));

        $table->modifyRows(function ($values, $row) {
            $now = date('H:i:s');
            $today = date('Y-m-d');
            
            if ($now > $values['timeStart'] && $now < $values['timeEnd'] && $values['date'] == $today) {
                $row->addClass('current');
            }
            return $row;
        });

        $table->addHeaderAction('view', __('View Planner'))
            ->setURL(Url::fromModuleRoute('Planner', 'planner')->withQueryParams(['search' => $viewingAs == 'Parent' ? $gibbonPersonID : '']))
            ->setIcon('calendar')
            ->displayLabel();

        $table->addColumn('class', __('Class'))
            ->context('primary')
            ->width('12%')
            ->format(function ($values) {
                return Format::bold(Format::courseClassName($values['course'], $values['class'])).'<br/>'
                    .Format::small(Format::timeRange($values['timeStart'], $values['timeEnd']));
            });

        $table->addColumn('lesson', __('Lesson'))
            ->description(__('Unit'))
            ->context('secondary')
            ->format(function ($values) {
                return !empty($values['unit'])
                    ? Format::bold($values['lesson']).'<br/>'.Format::small($values['unit'])
                    : Format::bold($values['lesson']);
            });

        $table->addColumn('homework', __($this->homeworkNameSingular))
            ->width('10%')
            ->format(function ($values) {
                $output = '';
                if ($values['homework'] == 'N' && empty($values['myHomeworkDueDateTime'])) {
                    $output .= __('No');
                } else {
                    if ($values['homework'] == 'Y') {
                        $output .= __('Yes').': '.__('Teacher Recorded').'<br/>';
                        if ($values['homeworkSubmission'] == 'Y') {
                            $output .= Format::small('+'.__('Submission')).'<br/>';
                            if ($values['homeworkCrowdAssess'] == 'Y') {
                                $output .= Format::small('+'.__('Crowd Assessment')).'<br/>';
                            }
                        }
                    }
                    if (!empty($values['myHomeworkDueDateTime'])) {
                        $output .= __('Yes').': '.__('Student Recorded').'</br>';
                    }
                }

                return $output;
            });

        $table->addColumn('summary', __('Summary'))
            ->width('40%');

        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonPlannerEntryID')
            ->addParam('gibbonCourseClassID')
            ->addParam('viewBy', 'class')
            ->addParam('search', $gibbonPersonID)
            ->format(function ($values, $actions) {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Planner/planner_view_full.php');
            });

        return $table;
    }
}
