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

namespace Gibbon\Module\Attendance;

use Gibbon\Domain\DataSet;
use Gibbon\UI\Chart\Chart;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\DataTableView;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * Student History View
 *
 * @version v18
 * @since   v18
 */
class StudentHistoryView extends DataTableView implements RendererInterface
{
    /**
     * Render the table to HTML.
     *
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    public function renderTable(DataTable $table, DataSet $dataSet)
    {
        $this->addData('table', $table);

        if ($dataSet->count() > 0) {
            $summary = $this->getSummaryCounts($dataSet);
            $this->addData([
                'dataSet' => $dataSet,
                'summary' => $summary,
                'chart'   => $this->getChart($summary),
            ]);
        }

        return $this->render('components/studentHistory.twig.html');
    }

    protected function getSummaryCounts(DataSet $dataSet)
    {
        $summary = ['total' => 0, 'present' => 0, 'partial' => 0, 'absent' => 0, '' => 0];

        foreach ($dataSet as $terms) {
            if (empty($terms['weeks'])) continue;
            
            foreach ($terms['weeks'] as $week) {
                foreach ($week as $dayData) {
                    if (empty($dayData['endOfDay'])) continue;
                    if (!$dayData['specialDay'] && !$dayData['outsideTerm']) {
                        $summary['total'] += 1;
                        if (!isset($summary[$dayData['endOfDay']['status']])) {
                            $summary[$dayData['endOfDay']['status']] = 0;
                        }
                        $summary[$dayData['endOfDay']['status']] += 1;
                    }
                }
            }
        }

        return $summary;
    }

    protected function getChart($summary)
    {
        $chart = Chart::create('attendanceSummary', 'doughnut')
            ->setOptions(['height' => '240px'])
            ->setLabels([__('Present'), __('Partial'), __('Absent'), __('No Data')])
            ->setColors(['#9AE6B4', '#FFD2A8', '#FC8181', 'rgba(0, 0, 0, 0.05)']);
    
        $chart->addDataset('pie')
            ->setData([$summary['present'], $summary['partial'], $summary['absent'], $summary['']]);

        return $chart->render();
    }
}
