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

use Gibbon\View\Component;

/**
 * Toggle
 *
 * @version v28
 * @since   v28
 */
class Toggle extends Input
{
    protected $onValue = '1';
    protected $offValue = '0';

    protected $toggleType = 'OnOff';
    protected $toggleSize = 'md';

    protected $onLabel;
    protected $offLabel;

    /**
     * Create a toggle input with a default value.
     * @param  string  $name
     */
    public function __construct($name, $default = '0')
    {
        $this->setName($name);
        $this->setID($name);
        $this->setValue($default);

        $this->onLabel = __('On');
        $this->offLabel = __('Off');
    }

    /**
     * Set the input's value.
     * @param  string  $value
     * @return $this
     */
    public function setValue($value = '')
    {
        $value = $value == $this->onValue ? $this->onValue : $this->offValue;
        
        $this->setAttribute('value', $value);
        return $this;
    }
    
    /**
     * Set the toggle's size
     * @param  string  $value
     * @return $this
     */
    public function setSize($value = '')
    {
        $this->toggleSize = $value;

        return $this;
    }

    /**
     * Sets the state of the toggle input.
     * @param   mixed  $value
     * @return  self
     */
    public function selected($value)
    {
        return $this->setValue($value);
    }

    /**
     * Sets the state of the toggle input.
     * @param   mixed  $value
     * @return  self
     */
    public function checked($value)
    {
        return $this->setValue($value);
    }

    /**
     * Helper class for radio element replacement.
     * @param   mixed  $value
     * @return  self
     */
    public function inline($value = true)
    {
        return $this;
    }

    /**
     * Helper class for radio element replacement.
     * @param   mixed  $value
     * @return  self
     */
    public function placeholder($value = '')
    {
        return $this;
    }

    /**
     * Sets the labels used for on/off the toggle states.
     * @param   string  $value
     * @return  self
     */
    public function setYesNo()
    {
        $this->toggleType = 'YesNo';
        $this->setToggle('Y', __('Yes'), 'N', __('No'));
        $this->setValue('Y');

        return $this;
    }

    /**
     * Sets the labels used for on/off the toggle states.
     * @param   string  $value
     * @return  self
     */
    public function setActiveInactive()
    {
        $this->toggleType = 'ActiveInactive';
        $this->setToggle('Y', __('Active'), 'N', __('Inactive'));
        $this->setValue('Y');

        return $this;
    }

    /**
     * Sets the labels used for on/off the toggle states.
     * @param   string  $value
     * @return  self
     */
    public function setToggle($onValue, $onLabel, $offValue, $offLabel, $toggleType = null)
    {
        $this->toggleType = $toggleType ?? $this->toggleType;
        $this->onValue = $onValue;
        $this->offValue = $offValue;
        $this->onLabel = $onLabel;
        $this->offLabel = $offLabel;
        $this->setValue($offValue);

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(Toggle::class, $this->getAttributeArray() + [
            'toggleType' => $this->toggleType,
            'toggleSize' => $this->toggleSize,
            'onValue'    => $this->onValue,
            'offValue'   => $this->offValue,
            'onLabel'    => $this->onLabel,
            'offLabel'   => $this->offLabel,
        ]);
    }
}
