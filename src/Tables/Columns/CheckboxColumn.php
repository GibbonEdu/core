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

use Gibbon\Forms\Input\Checkbox;

/**
 * CheckboxColumn
 *
 * @version v16
 * @since   v16
 */
class CheckboxColumn extends Column
{
    protected $key;
    /**
     * Creates a pre-defined column for bulk-action checkboxes.
     */
    public function __construct($id, $key = null)
    {
        parent::__construct($id);
        $this->sortable(false)->width('6%');
        $this->key = !empty($key)? $key : $id;

        $this->modifyCells(function($data, $cell) {
            return $cell->addClass('bulkCheckbox');
        });
    }

    /**
     * Overrides the label with a checkall checkbox.
     * @return string
     */
    public function getLabel()
    {
        return (new Checkbox('checkall'))
            ->setClass('floatNone checkall')
            ->wrap('<div class="textCenter">', '</div>')
            ->getOutput();
    }

    /**
     * Renders a bulk-action checkbox, grabbing the value by key from $data.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = array())
    {
        $value = isset($data[$this->key])? $data[$this->key] : '';

        return (new Checkbox($this->getID().'[]'))
            ->setID($this->getID().$value)
            ->setValue($value)
            ->getOutput();
    }
}
