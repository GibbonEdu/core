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
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\FormFactoryInterface;

/**
 * Displays a collapable element that can have content inside it.
 *
 * @version v19
 * @since   v19
 */
class Details extends Row implements OutputableInterface, ValidatableInterface
{
    protected $summaryText = 'Expand';
    protected $summaryClass = 'px-1 text-sm leading-normal hover:text-blue-600 cursor-pointer';

    /**
     * Construct a details element with access to a specific factory.
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->setClass('p-1');
        parent::__construct($factory, $id);
    }

    /**
     * Define the summary text and css class.
     *
     * @param string $summaryText
     * @param string $summaryClass
     * @return self
     */
    public function summary($summaryText, $summaryClass = null)
    {
        $this->summaryText = $summaryText;
        $this->summaryClass = $summaryClass ?? $this->summaryClass;

        return $this;
    }

    /**
     * Toggle whether the details is opened by default.
     *
     * @param bool $value
     * @return self
     */
    public function opened($value = true)
    {
        return $this->setAttribute('open', $value);
    }

    /**
     * Iterate over each element in the collection and concatenate the output.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        $output .= '<details '.$this->getAttributeString().'>';
        $output .= '<summary class="'.$this->summaryClass.'">'.$this->summaryText.'</summary>';

        foreach ($this->getElements() as $element) {
            $output .= $element->getOutput();
        }
        $output .= '</details>';

        return $output;
    }

    /**
     * Dead-end stub for interface: columns cannot validate.
     * @param   string  $name
     * @return  self
     */
    public function addValidation($name)
    {
        return $this;
    }

    /**
     * Iterate over each element in the collection and get the combined validation output.
     * @return  string
     */
    public function getValidationOutput()
    {
        $output = '';

        foreach ($this->getElements() as $element) {
            if ($element instanceof ValidatableInterface) {
                $output .= $element->getValidationOutput();
            }
        }

        return $output;
    }
}
