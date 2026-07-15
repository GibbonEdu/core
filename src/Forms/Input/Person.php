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
 * Person
 *
 * @version v31
 * @since   v18
 */
class Person extends SearchSelect
{
    protected $displayPhoto = true;
    protected $size = 'large';

    public function photo($value, $size = 'large')
    {
        $this->displayPhoto = $value;
        $this->size = $size;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        if (!empty($this->getAttribute('multiple'))) {
            return parent::getElement();
        }

        $this->processOutput();

        $this->setValue($this->selected);

        $options = [];
        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $key => $items) {
                $optLabel = is_array($items) ? $key : '';
                $optGroup = is_array($items) ? $items : [$key => $items];

                foreach ($optGroup as $value => $label) {
                    $options[$optLabel][$value] = [
                        'value' => $value,
                        'label' => $label,
                        'selected' => $this->isOptionSelected($value) ? 'selected' : '',
                        'class' => !empty($this->chainedToValues[$value]) ? $this->chainedToValues[$value] : '',
                    ];
                }
            }
        }

        $selected = is_array($this->selected)? ($this->selected[0] ?? '') : $this->selected;

        return Component::render(Person::class, $this->getAttributeArray() + [
            'outerClass'    => $this->getOuterClass(),
            'groupClass'    => $this->getGroupClass(),
            'placeholder'   => $this->placeholder,
            'chainedToID'   => $this->chainedToID,
            'options'       => $options,
            'selected'      => $selected,
            'selectedLabel' => $options[$selected]['label'] ?? '',
            'validation'    => '',
        ]);
    }
}
