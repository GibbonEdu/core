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

use Gibbon\Forms\OutputableInterface;

/**
 * Trigger
 *
 * @version v14
 * @since   v14
 */
class Trigger implements OutputableInterface
{
    protected $elementType;
    protected $elementValue = array();

    protected $targetSelector;
    protected $sourceSelector;
    protected $sourceValueSelector;

    protected $negate;

    /**
     * Create a trigger to toggle visibility of the specified CSS/jQuery selector.
     * @param  string  $selector
     */
    public function __construct($selector)
    {
        $this->targetSelector = $selector;
    }

    /**
     * Link this trigger to a select input by name.
     * @param   string  $name
     * @return  self
     */
    public function onSelect($name)
    {
        $this->elementType = 'select';
        $this->sourceSelector = 'select[name="'.$name.'"]';
        $this->sourceValueSelector = $this->sourceSelector;

        return $this;
    }

    /**
     * Link this trigger to a checkbox input by name.
     * @param   string  $name
     * @return  self
     */
    public function onCheckbox($name)
    {
        $this->elementType = 'checkbox';
        $this->sourceSelector = 'input[type="checkbox"][name^="'.$name.'"]';
        $this->sourceValueSelector = $this->sourceSelector.':checked';

        return $this;
    }

    /**
     * Link this trigger to a radio input by name.
     * @param   string  $name
     * @return  self
     */
    public function onRadio($name)
    {
        $this->elementType = 'radio';
        $this->sourceSelector = 'input[type="radio"][name="'.$name.'"]';
        $this->sourceValueSelector = $this->sourceSelector.':checked';

        return $this;
    }

    /**
     * Link this trigger to a text input by name.
     * @param   string  $name
     * @return  self
     */
    public function onInput($name)
    {
        $this->elementType = 'text';
        $this->sourceSelector = 'input[type="text"][name="'.$name.'"]';
        $this->sourceValueSelector = $this->sourceSelector;

        return $this;
    }

    /**
     * Set which value the trigger should respond to.
     * @param   string  $value
     * @return  self
     */
    public function when($value)
    {
        if ($this->elementType == 'checkbox') {
            $this->sourceValueSelector .= '[value="'.$value.'"]';
        }

        $this->elementValue = (is_array($value))? $value : array($value);
        return $this;
    }

    /**
     * Set the trigger to respond to all values except the specified one.
     * @param   string  $value
     * @return  self
     */
    public function whenNot($value)
    {
        $this->negate = true;
        return $this->when($value);
    }

    /**
     * Get the javascript output of the trigger.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';
        
        // Build a set of value comparisons for the source input
        $comparisons = array();
        foreach ($this->elementValue as $value) {
            $comparisons[] = "$('{$this->sourceValueSelector}').val() == '{$value}'";
        }

        // Join into a string, and negate the comparison for use with whenNot()
        $comparisons = implode('||', $comparisons);
        $comparisons = ($this->negate)? "!($comparisons)" : "($comparisons)";

        // Change target visibility if source value equals trigger value
        // Handles LiveValidation by also disabling/enabling inputs
        // The change() call activates any nested triggers
        $output .= "$(document).on('change showhide', '{$this->sourceSelector}', function(event){ \n";
            $output .= "if ($('{$this->sourceSelector}').prop('disabled') == false && {$comparisons}) { \n";
                $output .= "$('{$this->targetSelector}').slideDown('fast'); \n";
                $output .= "$('{$this->targetSelector} :input:not(button)').each(function(index, element){ if ($(this).is(':visible, .tinymce, .finderInput')) { $(this).prop('disabled', element.disabledState !== undefined ? element.disabledState : false); } });";
            $output .= "} else { \n";
                $output .= "$('{$this->targetSelector}').hide(); \n";
                $output .= "$('{$this->targetSelector} :input:not(button)').prop('disabled', true).change(); \n";
            $output .= "} \n";
        $output .= "}); \n";

        // Save the initial disabled state for all inputs targeted by this trigger
        $output .= "$('{$this->targetSelector} :input').each(function(index, element){ if (element.disabledState === undefined) element.disabledState = $(this).prop('disabled') ?? false; });";

        // Hide all initial targets if the source value does not equal the trigger value
        $output .= "if ( !({$comparisons}) ) { \n";
        $output .= "$('{$this->targetSelector}').hide(); \n";
        $output .= "$('{$this->targetSelector} :input:not(button)').each(function(index, element){ $(element).prop('disabled', true).change(); });";
        $output .= "}\n\n";

        return $output;
    }
}
