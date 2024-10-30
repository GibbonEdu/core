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
    protected $checked;

    /**
     * Creates a pre-defined column for bulk-action checkboxes.
     */
    public function __construct($id, $key = null)
    {
        parent::__construct($id);
        
        $this->sortable(false)->width('6%');
        $this->context('action');
        $this->key = !empty($key)? $key : $id;

        $this->modifyCells(function ($data, $cell) {
            return $cell->addClass('bulkCheckbox textCenter');
        });
    }

    public function checked($value = true)
    {
        $this->checked = $value;
        return $this;
    }

    /**
     * Overrides the label with a checkall checkbox.
     * @return string
     */
    public function getLabel()
    {
        return (new Checkbox('checkall'))
            ->setClass('floatNone checkall')
            ->checked(!is_callable($this->checked) ? $this->checked : false)
            ->wrap('<div class="text-center">', '</div>')
            ->alignCenter()
            ->getOutput();
    }

    /**
     * Renders a bulk-action checkbox, grabbing the value by key from $data.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = [], $joinDetails = true)
    {
        $value = isset($data[$this->key])? $data[$this->key] : '';

        $contents = $this->hasFormatter() ? call_user_func($this->formatter, $data) : '';

        return !empty($contents)
            ? $contents 
            : ((new Checkbox($this->getID().'[]'))->wrap('<label for="'.$this->getID().$value.'" class="-m-4 p-4">', '</label>'))
            ->setID($this->getID().$value)
            ->setValue($value)
            ->checked(is_callable($this->checked) ? call_user_func($this->checked, $data) : ($this->checked ? $value : false) )
            ->alignCenter()
            ->getOutput();
    }
}
