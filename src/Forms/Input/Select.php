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
use Gibbon\Contracts\Database\Connection;

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
    protected $hasSelected = false;

    protected $chainedToID;
    protected $chainedToValues;

    /**
     * Sets the selected element(s) of the select input.
     * @param   mixed  $value
     * @return  self
     */
    public function selected($value)
    {
        $this->selected = $value;

        return $this;
    }

    /**
     * Adds an initial entry to the select input. Required elements default to 'Please select...'
     * @param   string  $value
     * @return  self
     */
    public function placeholder($value = '')
    {
        $this->placeholder = $value;

        return $this;
    }

    /**
     * Set the selected element(s) to include all available options.
     * @param   bool    $value
     * @return  self
     */
    public function selectAll()
    {
        if ($this->getAttribute('multiple') == true) {
            $this->selected = array_keys($this->options);
        }

        return $this;
    }

    /**
     * Sets the select input attribute to handle multiple selections.
     * @param   bool    $value
     * @return  self
     */
    public function selectMultiple($value = true)
    {
        $this->setAttribute('multiple', $value);

        return $this;
    }

    /**
     * Add extra help text to multi-select inputs.
     * @return string
     */
    public function getLabelContext($label)
    {
        if ($this->getAttribute('multiple') == true) {
            return __('Use Control, Command and/or Shift to select multiple.');
        }
    }

    /**
     * Provide an ID of another select input to chain the values in this input to the selected element of the first input.
     * Chained values are paired with the options array, and correlate to the available options in the first select input.
     * @param   string  $id
     * @param   array   $values
     * @return  self
     */
    public function chainedTo($id, $values)
    {
        if (count($values) != count($this->options)) {
            throw new \InvalidArgumentException(sprintf('Element %s: chainedTo expects the number of values to match the number of options, %s found.', $this->name, count($values)));
        }

        $this->chainedToID = $id;
        $this->chainedToValues = $values;

        return $this;
    }

    /**
     * Build an internal options array from an SQL query with required value and name fields
     * @param   Connection  $pdo
     * @param   string                 $sql
     * @param   array                  $data
     * @return  self
     */
    public function fromQueryChained(Connection $pdo, $sql, $data = array(), $chainedToID = false, $groupBy = false)
    {
        $results = $pdo->executeQuery($data, $sql);
        $this->fromResults($results, $groupBy);

        $results = $pdo->executeQuery($data, $sql);

        if ($results && $results->rowCount() > 0) {
            $chainedOptions = array_reduce($results->fetchAll(), function($group, $item) {
                $group[$item['value']] = isset($item['chainedTo'])? $item['chainedTo'] : '';
                return $group;
            }, array());

            $this->chainedTo($chainedToID, $chainedOptions);
        }

        return $this;
    }

    /**
     * Return true if the value passed in is in the array of selected options.
     * @param   string  $value
     * @return  bool
     */
    protected function isOptionSelected($value)
    {
        if ($value === '') return false;

        if ($this->hasSelected) return false;

        if (is_array($this->selected)) {
            return in_array($value, $this->selected);
        } else {
            $selected = ($value == $this->selected);
            if ($selected && $this->getAttribute('multiple') == false) $this->hasSelected = true;
            return $selected;
        }
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        if (!empty($this->getAttribute('multiple'))) {
            if (empty($this->getAttribute('size'))) {
                $this->setAttribute('size', $this->getOptionCount());
            }

            if (stripos($this->getName(), '[]') === false) {
                $this->setName($this->getName().'[]');
            }
        }

        $output .= '<select '.$this->getAttributeString().'>';

        if ($this->getRequired() && $this->placeholder === '') {
            $this->placeholder(__('Please select...'));
        }

        if (isset($this->placeholder) && $this->getAttribute('multiple') == false) {
            // Add a placeholder only if the first option is not already blank
            if (count($this->getOptions()) == 0 || key($this->getOptions()) != '') {
                $output .= '<option value="'.$this->placeholder.'">'.$this->placeholder.'</option>';
            }

            if ($this->getRequired() && !empty($this->placeholder)) {
                $this->addValidation('Validate.Exclusion', 'within: [\''.$this->placeholder.'\'], failureMessage: "'.__('Select something!').'"');
            }
        }

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $value => $label) {
                if (is_array($label)) {
                    $output .= '<optgroup label="-- '.$value.' --">';
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
