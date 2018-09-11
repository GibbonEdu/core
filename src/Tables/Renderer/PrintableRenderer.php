<?php
/*
Gibbon, Flexible & Open School System
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

namespace Gibbon\Tables\Renderer;

use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Columns\Column;
use Gibbon\Tables\Columns\ActionColumn;
use Gibbon\Tables\Columns\ExpandableColumn;
use Gibbon\Tables\Renderer\RendererInterface;

/**
 * PrintableRenderer
 *
 * @version v16
 * @since   v16
 */
class PrintableRenderer extends SimpleRenderer implements RendererInterface
{
    /**
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderHeader(DataTable $table, DataSet $dataSet) 
    {
        $table->setHeader([]);
        $table->addHeaderAction('print', __('Print'))
            ->onClick('javascript:window.print(); return false;')
            ->displayLabel();

        return parent::renderHeader($table, $dataSet);
    }

    /**
     * @param DataTable $table
     * @param DataSet $dataSet
     * @return string
     */
    protected function renderFooter(DataTable $table, DataSet $dataSet)
    {
        return '';
    }

    /**
     * @param Column $column
     * @return Element
     */
    protected function createTableHeader(Column $column)
    {
        if ($column instanceof ActionColumn || $column instanceof ExpandableColumn) return null;

        return parent::createTableHeader($column);
    }

    /**
     * @param DataTable $table
     * @param array $data
     * @return Element
     */
    protected function createTableCell(array $data, DataTable $table, Column $column)
    {
        if ($column instanceof ActionColumn || $column instanceof ExpandableColumn) return null;
        
        return parent::createTableCell($data, $table, $column);
    }
}
