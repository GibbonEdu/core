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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * Select
 *
 * @version v14
 * @since   v14
 */
class Select extends Input
{
    use MultipleOptionsTrait;

    protected $placeholder;
    protected $selected = null;

    protected $chainedToID;
    protected $chainedToValues;

    public function selected($value)
    {
        $this->selected = $value;

        return $this;
    }

    public function placeholder($value = '')
    {
        $this->placeholder = $value;

        return $this;
    }

    public function selectMultiple($value = true)
    {
        $this->setAttribute('multiple', $value);

        return $this;
    }

    public function chainedTo($id, $values)
    {
        if (count($values) != count($this->options)) {
            throw new \InvalidArgumentException(sprintf('Element %s: chainedTo expects the number of values to match the number of options, %s found.', $this->name, count($values)));
        }

        $this->chainedToID = $id;
        $this->chainedToValues = $values;

        return $this;
    }

    protected function isOptionSelected($value)
    {
        if (is_array($this->selected)) {
            return in_array($value, $this->selected);
        } else {
            return ($value == $this->selected);
        }
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->getAttribute('multiple'))) {
            $this->setAttribute('size', $this->getOptionCount());
            $this->setName($this->getName().'[]');
        }

        $output .= '<select '.$this->getAttributeString().'>';

        if (isset($this->placeholder)) {
            $output .= '<option value="'.$this->placeholder.'">'.$this->placeholder.'</option>';

            if ($this->getRequired()) {
                $this->addValidation('Validate.Exclusion', 'within: [\''.$this->placeholder.'\'], failureMessage: "'.__('Select something!').'"');
            }
        }

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $value => $label) {
                if (is_array($label)) {
                    $output .= '<optgroup label="'.$value.'">';
                    foreach ($label as $subvalue => $sublabel) {
                        $selected = ($this->isOptionSelected($subvalue))? 'selected' : '';
                        $output .= '<option value="'.$subvalue.'" '.$selected.'>'.$sublabel.'</option>';
                    }
                    $output .= '</optgroup>';
                } else {
                    $selected = ($this->isOptionSelected($value))? 'selected' : '';
                    $class = (!empty($this->chainedToValues[$value]))? ' class="'.$this->chainedToValues[$value].'" ' : '';
                    $output .= '<option value="'.$value.'" '.$selected.$class.'>'.$label.'</option>';
                }
            }
        }

        $output .= '</select>';

        if (!empty($this->chainedToID)) {
            $output .= '<script type="text/javascript">';
            $output .= '$("#'.$this->getID().'").chainedTo("#'.$this->chainedToID.'");';
            $output .= '</script>';
        }

        return $output;
    }
}
