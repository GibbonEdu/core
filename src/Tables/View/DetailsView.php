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

namespace Gibbon\Tables\View;

use Gibbon\View\View;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * DetailsView
 *
 * @version v20
 * @since   v20
 */
class DetailsView extends View implements RendererInterface
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
        $dataSet->htmlEncode($table->getMetaData('allowHTML', []));
        $this->addData('table', $table);

        if ($dataSet->count() > 0) {
            $this->addData([
                'groups'     => $this->getColumnGroups($table),
                'columns'    => $table->getColumns(),
                'rows'       => $dataSet,
                'blankSlate' => $table->getMetaData('blankSlate'),
                'gridClass'  => $table->getMetaData('gridClass'),
            ]);
        }

        return $this->render('components/detailsTable.twig.html');
    }

    /**
     * 
     *
     * @param DataTable $table
     * @return array
     */
    protected function getColumnGroups(DataTable $table)
    {
        $groups = [];

        foreach ($table->getColumns(0) as $columnIndex => $column) {
            if ($column->hasNestedColumns()) {
                foreach ($column->getColumns() as $subColumnIndex => $subColumn) {
                    $groups[$column->getLabel()][$subColumnIndex] = $subColumn;
                }
            } else {
                $groups[''][$columnIndex] = $column;
            }
            
        }
        
        return $groups;
    }
}
