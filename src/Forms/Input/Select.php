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

    protected $selected = null;
    protected $placeholder;
    protected $multiple = false;

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
        $this->multiple = $value;

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

        if (!empty($this->multiple) && $this->multiple) {
            $totalOptions = count($this->getOptions(), COUNT_RECURSIVE);
            $output .= '<select id="'.$this->name.'" name="'.$this->name.'[]" class="'.$this->class.'" multiple size="'.$totalOptions.'">';
        } else {
            $output .= '<select id="'.$this->name.'" name="'.$this->name.'" class="'.$this->class.'">';
        }

        if (isset($this->placeholder)) {
            $output .= '<option value="'.$this->placeholder.'">'.__($this->placeholder).'</option>';

            if ($this->required) {
                $this->addValidation('Validate.Exclusion', 'within: [\''.$this->placeholder.'\'], failureMessage: "'.__('Select something!').'"');
            }
        }

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $value => $label) {
                if (is_array($label)) {
                    $output .= '<optgroup label="'.$value.'">';
                    foreach ($label as $subvalue => $sublabel) {
                        $selected = ($this->isOptionSelected($subvalue))? 'selected' : '';
                        $output .= '<option value="'.$subvalue.'" '.$selected.'>'.__($sublabel).'</option>';
                    }
                    $output .= '</optgroup>';
                } else {
                    $selected = ($this->isOptionSelected($value))? 'selected' : '';
                    $output .= '<option value="'.$value.'" '.$selected.'>'.__($label).'</option>';
                }
            }
        }

        $output .= '</select>';

        return $output;
    }
}
