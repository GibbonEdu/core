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

/**
 * Color
 *
 * @version v21
 * @since   v21
 */
class Color extends Input
{
    /**
     * Create an HTML color input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setAttribute('maxlength', 7);
        $this->addValidation(
            'Validate.Format',
            'pattern: /#[0-9a-fA-F]{6}/, failureMessage: "'.__('Must be a valid hex colour').'"'
        );
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $value = !empty($this->getValue()) ? $this->getValue() : '#ffffff';

        $output = '<div class="flex">';
        $output .= '<input type="color" data-for="'.$this->getID().'" class="colorPicker mr-2 w-16" style="padding: 4px; min-width: 36px; height:36px; border-width: 1px;" value="'.$value.'">';
        $output .= '<input type="text" '.$this->getAttributeString().'>';
        $output .= '</div>';

        return $output;
    }
}
