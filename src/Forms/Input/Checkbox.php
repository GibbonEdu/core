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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * Checkbox
 *
 * @version v14
 * @since   v14
 */
class Checkbox extends Input
{
    use MultipleOptionsTrait;

    protected $description;
    protected $checked = array();
    protected $disabled = array();
    protected $checkall = false;
    protected $inline = false;
    protected $align = 'right';
    protected $labelClass = '';
    protected $selectableGroups = false;

    /**
     * Create a checkpox input with a default value of on when checked.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setName($name);
        $this->setID($name);
        $this->setValue('on');
    }

    /**
     * Sets an inline label next to the checkbox input.
     * @param   string  $value
     * @return  self
     */
    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Sets the css class for the inline checkbox label.
     * @param   string  $value
     * @return  self
     */
    public function setLabelClass($value = '')
    {
        $this->labelClass = $value;
        return $this;
    }

    /**
     * Sets the css class for the inline checkbox label.
     * @param   string  $value
     * @return  self
     */
    public function getLabelClass()
    {
        return $this->labelClass;
    }

    /**
     * Set a value or array of values that are currently checked.
     * @param   string  $values
     * @return  self
     */
    public function checked($values)
    {
        if ($values === 1 || $values === true) $values = 'on';

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $this->checked[trim($key)] = (!is_array($value))? trim($value) : $value;
            }
        } else {
            $this->checked = [trim($values ?? '')];
        }

        return $this;
    }

    /**
     * Set a value or array of values that are currently disabled.
     * @param   string  $disabled
     * @return  self
     */
    public function disabled($disabled = [])
    {
        $this->disabled = is_array($disabled) ? $disabled : [$disabled];

        return $this;
    }

    /**
     * Set the checked element(s) to include all available options.
     * @return  self
     */
    public function checkAll()
    {
        if (!empty($this->options)) {
            $this->checked = array_keys($this->options);
        }

        return $this;
    }

    /**
     * Adds a checkall box to the top of the checkbox list, pass a label in otherwise defaults to All / None.
     * @param   string  $label
     * @return  self
     */
    public function addCheckAllNone($label = '')
    {
        if (empty($label)) $label = __('All').' / '.__('None');

        $this->checkall = $label;
        return $this;
    }

    /**
     * Aligns the list options to the right edge.
     * @return  self
     */
    public function selectableGroups($value = true)
    {
        $this->selectableGroups = $value;
        return $this;
    }

    /**
     * Sets multiple checkbox elements to display horizontally.
     * @param   bool    $value
     * @return  self
     */
    public function inline($value = true)
    {
        $this->inline = $value;
        $this->addClass('right');
        return $this;
    }

    /**
     * Aligns the list options to the right edge.
     * @return  self
     */
    public function alignRight()
    {
        $this->align = 'right';
        return $this;
    }

    /**
     * Aligns the list options to the left edge.
     * @return  self
     */
    public function alignLeft()
    {
        $this->align = 'left';
        return $this;
    }

    /**
     * Aligns the list options to the center.
     * @return  self
     */
    public function alignCenter()
    {
        $this->align = 'center';
        $this->addClass('text-center');
        return $this;
    }

    /**
     * Return true if the passed value matches the current checkbox element value(s).
     * @param   mixed  $value
     * @return  bool
     */
    protected function getIsChecked($value)
    {
        if (empty($value) || empty($this->checked)) {
            return false;
        }

        return in_array($value, $this->checked);
    }

    /**
     * Return true if the passed value has been disabled.
     * @param   mixed  $value
     * @return  bool
     */
    protected function getIsDisabled($value)
    {
        if (empty($value) || empty($this->disabled)) {
            return false;
        }

        return in_array($value, $this->disabled);
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        if (!empty($this->getOptions())) {
            // Multiple checkboxes - ensure the form values are returned as an array
            $name = (stripos($this->getName(), '[') === false)? $this->getName().'[]' : $this->getName();
        } else {
            // Single checkbox - build an options array
            $this->options = array($this->getValue() => $this->description);
            $name = $this->getName();
        }

        if (!empty($this->options) && is_array($this->options)) {
            $identifier = preg_replace('/[^a-zA-Z0-9]/', '', $this->getID());
            $hasMultiple = count($this->options, COUNT_RECURSIVE) > 1;

            if ($hasMultiple) {
                $output .= '<fieldset id="'.$this->getID().'" class="'.($this->inline && $this->align == 'left' ? 'flex text-left items-center' : '').'" style="border: 0px;">';
            }
            
            if (!empty($this->checkall)) {
                $checked = (count($this->options) == count($this->checked))? 'checked' : '';
                $output .= '<div class="flex mt-1 '.($this->align == 'right' ? 'justify-end text-right' : '').'">';
                $output .= '<label for="checkall'.$identifier.'" class="mr-1">'.$this->checkall.'</label> ';
                $output .= '<input id="checkall'.$identifier.'" class="checkall" type="checkbox" '.$checked.'><br/>';
                $output .= '</div>';
            }

            $this->addClass('flex-none');

            $count = 0;

            $optionGroups = !is_array(current($this->options))
                    ? ['' => $this->options]
                    : $this->options;

            foreach ($optionGroups as $group => $options) {
            
                if (!empty($group)) {
                    $output .= '<label class="flex justify-between font-bold pb-1 border-b border-gray-400 '.($this->selectableGroups ? 'mt-4' : '').'"><span class="flex-1">'.$group.'</span>';
                    if ($this->selectableGroups) {
                        $groupName = 'heading'.preg_replace('/[^a-zA-Z0-9]/', '', $group);
                        $output .= '<input type="checkbox" name="'.$name.'" value="'.$groupName.'" class="text-right"><br/>';
                    }
                    $output .= '</label>';
                }
                foreach ($options as $value => $label) {
                    if ($hasMultiple) {
                        $this->setID($identifier.$count);
                    }
                    $this->setName($name);
                    $this->setAttribute('checked', $this->getIsChecked($value));
                    $this->setAttribute('disabled', $this->getIsDisabled($value));

                    if ($value != 'on') $this->setValue($value);

                    if ($this->inline) {
                        $output .= '<input type="checkbox" '.$this->getAttributeString().'>&nbsp;';
                        $output .= '<label class="'.$this->getLabelClass().'" for="'.$this->getID().'">'.$label.'</label>&nbsp;&nbsp;';
                    } elseif ($this->align == 'center') {
                        $output .= '<input type="checkbox" '.$this->getAttributeString().'>';
                        $output .= '<label class="'.$this->getLabelClass().'" for="'.$this->getID().'">'.$label.'</label>';
                    } elseif ($this->align == 'left') {
                        $output .= '<div class="flex text-left '.($hasMultiple ? 'my-2' : 'items-center my-px').'">';
                        $output .= '<input type="checkbox" '.$this->getAttributeString().'>';
                        $output .= '<label class="leading-compact ml-2 '.$this->getLabelClass().'" for="'.$this->getID().'">'.$label.'</label><br/>';
                        $output .= '</div>';
                    } else {
                        $output .= '<div class="flex justify-end text-right '.($hasMultiple ? 'my-2' : 'items-center my-px').'">';
                        $output .= '<label class="leading-compact mr-1 '.$this->getLabelClass().'" for="'.$this->getID().'">'.$label.'</label> ';
                        $output .= '<input type="checkbox" '.$this->getAttributeString().'><br/>';
                        $output .= '</div>';
                    }

                    $count++;
                }
            }

            if ($hasMultiple) {
                $output .= '</fieldset>';
            }
        }

        return $output;
    }
}
