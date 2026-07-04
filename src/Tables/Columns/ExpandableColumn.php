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

namespace Gibbon\Tables\Columns;

use Gibbon\Tables\DataTable;
use Gibbon\Forms\Input\Checkbox;

/**
 * ExpandableColumn
 *
 * @version v16
 * @since   v16
 */
class ExpandableColumn extends Column
{
    protected $expanded = false;

    /**
     * Creates a pre-defined column for expanding rows with extra data.
     */
    public function __construct($id, DataTable $table)
    {
        parent::__construct($id);
        $this->sortable(false)->width('5%');
        $this->context('action');

        $table->modifyRows(function ($data, $row, $columnCount) {
            return $row->append($this->getExpandedContent($data, $columnCount));
        });

        $this->modifyCells(function ($data, $cell) {
            return $cell->addClass('expandable');
        });
    }

    /**
     * Set the default expanded state.
     * @return self
     */
    public function setExpanded($expanded)
    {
        $this->expanded = $expanded;
        return $this;
    }

    /**
     * Overrides the label.
     * @return string
     */
    public function getLabel()
    {
        return '';
    }

    /**
     * Expander arrow.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = array())
    {
        if ($content = parent::getOutput($data)) {
            return '<a onclick="return false;" class="expander"></a>';
        } else {
            return '';
        }
    }

    /**
     * Output the content of the expanded row. Can be set by the column ID, or with the column's formatter callable.
     *
     * @param array $data
     * @param int $columnCount
     * @return string
     */
    public function getExpandedContent(&$data = array(), $columnCount)
    {
        $output = '';

        if ($content = parent::getOutput($data)) {
            $output .= '<tr style="'.($this->expanded ? '' : 'display:none').';"><td colspan="'.$columnCount.'">';
            $output .= $content;
            $output .= '</td></tr>';
        }
        return $output;
    }
}
