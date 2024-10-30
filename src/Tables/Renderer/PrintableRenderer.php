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
        if ($table->getMetaData('hideHeaderActions') == true) return;
        
        $table->setHeader([]);
        $table->addHeaderAction('print', __('Print'))
            ->setURL('#')
            ->onClick('javascript:window.print(); return false;')
            ->displayLabel();

        $orientation = $_GET['orientation'] ?? 'P';
        $table->addHeaderAction('orientation', $orientation == 'P' ? __('Landscape') : __('Portrait'))
            ->setURL('/report.php')
            ->addParams($_GET)
            ->addParam('orientation', $orientation == 'P' ? 'L' : 'P')
            ->setIcon('refresh')
            ->displayLabel()
            ->directLink()
            ->prepend(' | ');

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
