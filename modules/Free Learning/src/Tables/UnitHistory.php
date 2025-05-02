<?php
/*
Gibbon, Free & Open School System
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

namespace Gibbon\Module\FreeLearning\Tables;

use Gibbon\View\View;
use Gibbon\UI\Chart\Chart;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
use Gibbon\Domain\System\SettingGateway;

/**
 * UnitHistory
 *
 * @version v5.22.00
 * @since   v5.13.18
 */
class UnitHistory
{
    protected $unitStudentGateway;
    protected $templateView;
    protected $unitHistoryChart;

    public function __construct(UnitStudentGateway $unitStudentGateway, SettingGateway $settingGateway, View $templateView)
    {
        $this->unitStudentGateway = $unitStudentGateway;
        $this->templateView = $templateView;
        $this->unitHistoryChart = $settingGateway->getSettingByScope('Free Learning', 'unitHistoryChart');
    }

    public function create($gibbonPersonID, $summary = false, $canBrowse = true, $disableParentEvidence = false, $gibbonSchoolYearID = null, $dateStart = null, $dateEnd = null)
    {
        $criteria = $this->unitStudentGateway->newQueryCriteria()
            ->sortBy(['freeLearningUnitStudent.timestampJoined', 'schoolYear'], 'DESC')
            ->sortBy(['freeLearningUnitStudent.status', 'freeLearningUnit.name'])
            ->fromPOST('unitHistory');

        $inactiveUnits = ($this->unitHistoryChart == 'Stacked Bar Chart') ? false : true;
        $units = $this->unitStudentGateway->queryUnitsByStudent($criteria, $gibbonPersonID, $gibbonSchoolYearID, $dateStart, $dateEnd, $inactiveUnits);
        $flCourses = $this->unitStudentGateway->selectCoursesByStudent($gibbonPersonID, $gibbonSchoolYearID)->fetchGroupedUnique();

        $table = !$summary
            ? DataTable::createPaginated('unitHistory', $criteria)->withData($units)
            : DataTable::create('unitHistory')->withData($units);

        $output = '';

        if ($this->unitHistoryChart == 'Doughnut' or $this->unitHistoryChart == 'Stacked Bar Chart') {
            // Render chart
            $output .= "<h3>".__('Overview')."</h3>";

            if ($this->unitHistoryChart == 'Stacked Bar Chart') {        

                $unitStats = [
                    "Complete - Approved" => [],
                    "Evidence Not Yet Approved" => [],
                    "Complete - Pending" => [],
                    "Current" => [],
                    "Current - Pending" => [],
                    "Incomplete" => [],
                    "Exempt" => [],
                ];

                $statuses = array_keys($unitStats);

                foreach ($flCourses as $index => $course) {
                    $unitTotal = 0;

                    foreach ($statuses as $status) {
                        $unitStats[$status][$index] = 0;

                        // Count the unit stats for this status
                        foreach ($units as $unit) {
                            if ($unit['status'] != $status) continue;

                            if ($unit['flCourse'] == $course['name']) {
                                $unitStats[$unit['status']][$index]++;
                                $unitTotal++;
                            }
                        }
                    }

                    $unitStats['Incomplete'][$index] = $flCourses[$course['name']]['total'] - $unitTotal;
                }

                $chart = Chart::create('unitStats'.$gibbonPersonID, 'bar')
                    ->setOptions([
                        'height' => '340px',
                        'scales' => [
                            'x' => [
                                'stacked' => 'true',
                            ],
                            'y' => [
                                'stacked' => 'true',
                            ]
                        ],
                    ])
                    ->setLabels(array_column($flCourses, 'name'))
                    ->setColors(['#6EE7B7', '#FFD2A8', '#DCC5f4', '#BAE6FD', '#FAF089', '#dddddd']);

                foreach($statuses as $status) {
                    $chart->addDataset($status, __($status))->setData($unitStats[$status] ?? []);
                }
            } else {

                $unitStats = [
                    "Current - Pending" => 0,
                    "Current" => 0,
                    "Complete - Pending" => 0,
                    "Evidence Not Yet Approved" => 0,
                    "Complete - Approved" => 0,
                    "Exempt" => 0,
                ];
                foreach ($units as $unit) {
                    ++$unitStats[$unit['status']];
                }

                $chart = Chart::create('unitStats'.$gibbonPersonID, 'doughnut')
                    ->setOptions([
                        'height' => 80,
                        'legend' => [
                            'position' => 'right',
                        ]
                    ])
                    ->setLabels([__m('Current - Pending'), __m('Current'), __m('Complete - Pending'), __m('Evidence Not Yet Approved'), __m('Complete - Approved'), __m('Exempt')])
                    ->setColors(['#FAF089', '#BAE6FD', '#DCC5f4', '#FFD2A8', '#6EE7B7', '#f9dbf2']);

                $chart->addDataset('pie')
                    ->setData([$unitStats['Current - Pending'], $unitStats['Current'], $unitStats['Complete - Pending'], $unitStats['Evidence Not Yet Approved'], $unitStats['Complete - Approved'], $unitStats['Exempt']]);
            }

            $output .= $chart->render();
        }

        $output .= "<h3>".__('Details')."</h3>";

        $table->modifyRows(function ($student, $row) {
            if ($student['status'] == 'Current - Pending') $row->setClass('currentPending');
            if ($student['status'] == 'Current') $row->setClass('currentUnit');
            if ($student['status'] == 'Evidence Not Yet Approved') $row->setClass('warning');
            if ($student['status'] == 'Complete - Pending') $row->setClass('pending');
            if ($student['status'] == 'Complete - Approved') $row->setClass('success');
            if ($student['status'] == 'Exempt') $row->setClass('exempt');
            return $row;
        });

        $filterOptions = [
            'status:current - pending'         => __('Status') .': '.__m('Current - Pending'),
            'status:current'                   => __('Status') .': '.__m('Current'),
            'status:complete - pending'        => __('Status') .': '.__m('Complete - Pending'),
            'status:evidence not yet approved' => __('Status') .': '.__m('Evidence Not Yet Approved'),
            'status:complete - approved'       => __('Status') .': '.__m('Complete - Approved'),
            'status:exempt'                    => __('Status') .': '.__m('Exempt'),
        ];

        $learningAreas = $this->unitStudentGateway->selectLearningAreasByStudent($gibbonPersonID)->fetchKeyPair();
        foreach ($learningAreas as $learningArea) {
            $filterOptions['department:' . $learningArea] = __('Learning Area') .': '.__($learningArea);
        }

        $table->addMetaData('filterOptions', $filterOptions);

        $table->addExpandableColumn('commentStudent')
            ->format(function ($values) use ($disableParentEvidence) {
                if ($values['status'] == 'Current' || $values['status'] == 'Current - Pending') return;
                if (empty($values['commentStudent']) && empty($values['commentApproval'])) return;

                $logs = $this->unitStudentGateway->selectUnitStudentDiscussion($values['freeLearningUnitStudentID'])->fetchAll();

                $logs = array_map(function ($item) {
                    $item['comment'] = Format::hyperlinkAll($item['comment']);
                    return $item;
                }, $logs);

                if ($disableParentEvidence) {
                    for ($i = 0; $i < count($logs); $i++) {
                        $logs[$i]['attachmentLocation'] = null;
                    }
                }

                return $this->templateView->fetchFromTemplate('ui/discussion.twig.html', [
                    'discussion' => $logs
                ]);
            });

        $table->addColumn('timestampJoined', __('School Year'))->description(__('Date'))
            ->sortable(['freeLearningUnitStudent.timestampJoined', 'schoolYear'])
            ->format(function($values) {
                return $values['schoolYear'].'<br/>'.Format::small(Format::date($values['timestampJoined']));
            });

        $table->addColumn('unit', __('Unit'))
            ->description(__m('Learning Area').'/'.__m('Course'))
            ->format(function($values) use ($canBrowse) {
                if ($canBrowse) {
                    $url = './index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=' . $values['freeLearningUnitID'] . '&freeLearningUnitStudentID='.$values['freeLearningUnitStudentID'].'&sidebar=true';
                    $output = Format::link($url, $values['unit']);
                } else {
                    $output = $values['unit'];
                }

                if (!empty($values['learningArea'])) {
                    $output .= '<br/>'.Format::small($values['learningArea']);
                }
                if (!empty($values['flCourse'])) {
                    $output .= '<br/>'.Format::small($values['flCourse']);
                }

                return $output;
            });

        $table->addColumn('enrolmentMethod', __('Enrolment Method', [], "Free Learning")) // DON'T CHANGE TRANSLATION CALL!!!
            ->format(function ($values) {
                return ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values["enrolmentMethod"]));
            });

        $table->addColumn('class', __m('Class'))
            ->format(function ($values) {
                return !empty($values['class']) ? Format::courseClassName($values['course'], $values['class']) : Format::small(__('N/A'));
            });

        $table->addColumn('status', __('Status'))
            ->format(function ($values) {
                return __($values['status'], [], "Free Learning"); // DON'T CHANGE TRANSLATION CALL!!!
            });

        if (!$disableParentEvidence) {
            $table->addColumn('evidence', __('Evidence', [], "Free Learning")) // DON'T CHANGE TRANSLATION CALL!!!
                ->notSortable()
                ->width('10%')
                ->format(function ($values) {
                    if (empty($values['evidenceLocation'])) return;

                    $url = $values['evidenceType'] == 'Link'
                        ? $values['evidenceLocation']
                        : './'.$values['evidenceLocation'];

                    return Format::link($url, __('View'), ['target' => '_blank']);
                });
        }

        return $output.$table->getOutput();
    }
}
