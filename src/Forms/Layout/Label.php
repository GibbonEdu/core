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

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\RowDependancyInterface;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Layout\Column;

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
        $this->addClass('font-medium my-0 text-base/6 sm:text-sm/6 text-gray-800');
    }

    /**
     * Method for RowDependancyInterface to automatically set a reference to the parent Row object.
     * @param  object  $row
     */
    public function setRow($row)
    {
        $this->row = $row;
        if (!$row instanceof Column) $this->addClass('');
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
        
        $this->addClass('-mt-1');
        $output .= '<label '.$this->getAttributeString().'>';
        $output .= $this->label;
        
        

        if ($this->getReadonly()) {
            if (!empty($this->description)) {
                $this->description .= ' ';
            }

            $this->setTitle(__('This value cannot be changed.'));
            $output .= '<svg class="inline h-3 w-3 ml-2 text-gray-400 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" ><path fill-rule="evenodd" d="M8 1a3.5 3.5 0 0 0-3.5 3.5V7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7V4.5A3.5 3.5 0 0 0 8 1Zm2 6V4.5a2 2 0 1 0-4 0V7h4Z" clip-rule="evenodd" /></svg>';

        } elseif ($this->getRequired()) {
            // $output .= '<svg class="inline h-3 w-3 ml-2 text-gray-400 " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>';
            $output .= ' <span class="text-sm text-red-600 font-light">*</span>';
            // $output .= ' <span class="text-xxs text-gray-400 font-light italic">'.__('Optional').'</span>';
        }

        if ($context = $this->getLabelContext()) {
            $this->description($context);
        }

        if (!empty($this->description)) {
            $output .= '<div class="mt-1 sm:mt-2 text-sm sm:text-xs text-gray-600 font-light">';
            $output .= $this->getDescription();
            $output .= '</div>';
        }

        $output .= '</label>';

        $output .= $this->content;

        return $output;
    }
}
