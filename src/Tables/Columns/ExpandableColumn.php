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

        $table->addMetaData('allowHTML', [$id]);

        $table->modifyRows(function ($data, $row, $columnCount) {
            return $row->setAttribute('x-data', $this->expanded ? '{expanded: true}' : '{expanded: false}');
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
    public function getOutput(&$data = [], $joinDetails = true)
    {
        if ($content = parent::getOutput($data, $joinDetails)) {
            return '<button type="button" class="expander w-8 h-8 bg-transparent" @click="expanded = !expanded" :class="{\'expanded\': expanded}"></button>'.$this->getExpandedContent($data);
        } else {
            return '';
        }
    }

    /**
     * Output the content of the expanded row. Can be set by the column ID, or with the column's formatter callable.
     *
     * @param array $data
     * @return string
     */
    public function getExpandedContent(&$data = [])
    {
        $output = '';

        if ($content = parent::getOutput($data)) {
            $output .= '<div x-show="expanded" x-collapse @click.outside="expanded = false" class="absolute left-0 w-full bg-white p-6 border rounded-md shadow-md z-40">';
            $output .= $content;
            $output .= '</div>';
        }
        return $output;
    }
}
