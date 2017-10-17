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

use Gibbon\Forms\RowDependancyInterface;

/**
 * Label
 *
 * @version v14
 * @since   v14
 */
class Label extends Element implements RowDependancyInterface
{
    protected $row;

    protected $label;
    protected $description;
    protected $for = '';

    /**
     * Create a label element with a for attribute linking to an input.
     * @param  string  $for
     * @param  string  $label
     */
    public function __construct($for, $label)
    {
        $this->label = $label;
        $this->for = $for;
    }

    /**
     * Method for RowDependancyInterface to automatically set a reference to the parent Row object.
     * @param  object  $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }

    /**
     * Overload the getName method to prepend a label- prefix.
     * @return  string
     */
    public function getName()
    {
        return 'label'.$this->for;
    }

    /**
     * Overload the getID method to prepend a label prefix.
     * @return  string
     */
    public function getID()
    {
        return 'label'.$this->for;
    }

    /**
     * Set the smaller description text to be output with the label.
     * @param   string  $value
     * @return  self
     */
    public function description($value = '')
    {
        $this->description = (!empty($this->description))? $this->description.'<br>'.$value : $value;
        return $this;
    }

    /**
     * Get the required status of the input this label is linked to.
     * @return  bool
     */
    protected function getRequired()
    {
        if (empty($this->for) || empty($this->row)) {
            return false;
        }

        $element = $this->row->getElement($this->for);

        return (!empty($element) && method_exists($element, 'getRequired'))? $element->getRequired() : false;
    }

    /**
     * Get the readonly status of the input this label is linked to.
     * @return  bool
     */
    protected function getReadOnly()
    {
        if (empty($this->for)) {
            return false;
        }

        $element = $this->row->getElement($this->for);

        return (!empty($element) && method_exists($element, 'getReadonly'))? $element->getReadonly() : false;
    }

    /**
     * Get the HTML output of the label element.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        if (!empty($this->label)) {
            $output .= '<label for="'.$this->for.'"><b>'.$this->label.' '.( ($this->getRequired())? '*' : '').'</b></label><br/>';
        }

        if ($this->getReadonly()) {
            if (!empty($this->description)) {
                $this->description .= ' ';
            }

            $this->description .= __('This value cannot be changed.');
        }

        if (!empty($this->description)) {
            $output .= '<span class="emphasis small">';

            $output .= (!empty($this->prepended))? $this->prepended.' ' : '';
            $output .= $this->description;
            $output .= (!empty($this->appended))? ' '.$this->appended : '';

            $output .= '</span><br/>';
        }

        $output .= $this->content;

        return $output;
    }
}
