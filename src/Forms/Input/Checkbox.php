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
use Gibbon\View\Component;

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
        // If the checkbox has multiple values, ensure it is handled as an array
        if ($this->getOptionCount() > 0) {
            $this->setName(stripos($this->getName(), '[') === false ? $this->getName().'[]' : $this->getName());
        } else {
            $this->options = [$this->getValue() => $this->description];
        }

        $identifier = preg_replace('/[^a-zA-Z0-9]/', '', $this->getID());
        $hasMultiple = $this->getOptionCount() > 1;
        $options = [];

        if (!empty($this->options) && is_array($this->options)) {
            $optionGroups = !is_array(current($this->options))?  ['' => $this->options] : $this->options;
            
            foreach ($optionGroups as $group => $optionsList) {

                foreach ($optionsList as $value => $label) {
                    $options[$group][$value] = [
                        'value'    => $value,
                        'label'    => $label,
                        'checked'  => $this->getIsChecked($value) ? 'checked' : '',
                        'disabled' => $this->getIsDisabled($value) ? 'disabled' : '',
                    ];
                }
            }
        }

        return Component::render(Checkbox::class, $this->getAttributeArray() + [
            'identifier'     => $identifier,
            'options'        => $options,
            'totalOptions'   => $this->getOptionCount(),
            'checkedOptions' => count($this->checked),
            'attributes'     => $this->getAttributeString('', 'id,name,class,value,type'),
            'hasMultiple'    => $hasMultiple,
            'labelClass'     => $this->labelClass,
            'checkall'       => $this->checkall,
            'inline'         => $this->inline,
            'align'          => $this->align,
            'count'          => 0,
        ]);
    }
}
