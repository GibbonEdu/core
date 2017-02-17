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

namespace Gibbon\Forms\Layout;

/**
 * Label
 *
 * @version v14
 * @since   v14
 */
class Label extends Content implements RowDependancyInterface
{
    protected $row;

    protected $label;
    protected $description;
    protected $for = '';

    public function __construct($for, $label)
    {
        $this->label = $label;
        $this->for = $for;
    }

    public function setRow(Row $row) {
        $this->row = $row;
    }

    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    protected function getRequired()
    {
        if (empty($this->for) || empty($this->row)) {
            return false;
        }

        $element = $this->row->getElement($this->for);

        return (!empty($element))? $element->getRequired() : false;
    }

    protected function getReadOnly()
    {
        if (empty($this->for)) {
            return false;
        }

        $element = $this->row->getElement($this->for);

        return (!empty($element) && method_exists($element, 'getReadonly'))? $element->getReadonly() : false;
    }

    public function getOutput()
    {
        $output = '';

        if (!empty($this->label)) {
            $output .= '<label for="'.$this->for.'"><b>'.__($this->label).' '.( ($this->getRequired())? '*' : '').'</b></label><br/>';
        }

        if ($this->getReadonly()) {
            if (!empty($this->description)) {
                $this->description .= ' ';
            }

            $this->description .= __('This value cannot be changed.');
        }

        if (!empty($this->description)) {
            $output .= '<span class="emphasis small">'.__($this->description).'</span><br/>';
        }

        $output .= $this->content;

        return $output;
    }
}
