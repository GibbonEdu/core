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

/**
 * TextArea
 *
 * @version v14
 * @since   v14
 */
class TextArea extends Input
{
    protected $maxLength;
    protected $autosize = false;

    /**
     * Create a textarea with a default height of 6 rows.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setRows(6);
        parent::__construct($name);
    }

    /**
     * Set the textarea rows attribute to control the height of the input box.
     * @param  int  $count
     * @return self
     */
    public function setRows($count)
    {
        $this->setAttribute('rows', $count);

        return $this;
    }

    /**
     * Set the textarea cols attribute to control the width of the input box.
     * @param  int  $count
     * @return self
     */
    public function setCols($count)
    {
        $this->setAttribute('cols', $count);

        return $this;
    }

    /**
     * Set a max character count for this textarea.
     * @param   string  $value
     * @return  self
     */
    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    /**
     * Enables the jQuery autosize function for this textarea.
     * @param   string  $value
     * @return  self
     */
    public function autosize($autosize = true)
    {
        $this->autosize = $autosize;
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $text = $this->getAttribute('value');
        $this->setAttribute('value', '');

        $output = '<textarea '.$this->getAttributeString().'>';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</textarea>';

        if ($this->autosize) {
            $output .= '<script type="text/javascript">autosize($("#'.$this->getID().'"));</script>';
        }

        return $output;
    }
}
