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

    /**
     * Create a label element with a for attribute linking to an input.
     * @param  string  $for
     * @param  string  $label
     */
    public function __construct($for, $label)
    {
        $this->label = $label;
        $this->setAttribute('for', $for);
        $this->addClass('font-medium');
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
        return 'label'.$this->getAttribute('for');
    }

    /**
     * Overload the getID method to prepend a label prefix.
     * @return  string
     */
    public function getID()
    {
        return 'label'.$this->getAttribute('for');
    }

    /**
     * Get the label text.
     * @return string
     */
    public function getLabelText()
    {
        return $this->label;
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
     * Gets the current label description.
     * @return string
     */
    public function getDescription()
    {
        $output = '';

        $output .= (!empty($this->prepended))? $this->prepended.' ' : '';
        $output .= $this->description;
        $output .= (!empty($this->appended))? ' '.$this->appended : '';

        return $output;
    }

    /**
     * Get the required status of the input this label is linked to.
     * @return  bool
     */
    protected function getRequired()
    {
        if ($element = $this->getLinkedElement())
        {
            return method_exists($element, 'getRequired')? $element->getRequired() : false;
        }

        return false;
    }

    /**
     * Get the readonly status of the input this label is linked to.
     * @return  bool
     */
    protected function getReadOnly()
    {
        if ($element = $this->getLinkedElement())
        {
            return method_exists($element, 'getReadonly')? $element->getReadonly() : false;
        }

        return false;
    }

    /**
     * Allows an element to define a string that is appended to the current label description.
     * @return bool|string
     */
    protected function getLabelContext()
    {
        if ($element = $this->getLinkedElement()) {
            return method_exists($element, 'getLabelContext')? $element->getLabelContext($this) : false;
        }

        return false;
    }

    protected function getLinkedElement()
    {
        if (empty($this->getAttribute('for')) || empty($this->row)) {
            return false;
        }

        return $this->row->getElement($this->getAttribute('for'));
    }

    /**
     * Get the HTML output of the label element.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        $this->addClass('inline-block mt-4 sm:my-1 sm:max-w-xs text-sm text-gray-700');

        
        $output .= '<label '.$this->getAttributeString().'>';
        $output .= $this->label;
        $output .= $this->getRequired()? ' <span class="text-sm text-gray-500 font-light">*</span>' : '';
        

        if ($this->getReadonly()) {
            if (!empty($this->description)) {
                $this->description .= ' ';
            }

            $this->description .= __('This value cannot be changed.');
        }

        if ($context = $this->getLabelContext()) {
            $this->description($context);
        }

        if (!empty($this->description)) {
            $output .= '<br/><span class="text-xxs text-gray-600 font-normal mt-1 sm:mt-0">';
            $output .= $this->getDescription();
            $output .= '</span>';
        }

        $output .= '</label>';

        $output .= $this->content;

        return $output;
    }
}
