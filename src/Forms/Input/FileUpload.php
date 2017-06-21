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
 * TextField
 *
 * @version v14
 * @since   v14
 */
class FileUpload extends Input
{
    protected $accepts = array();
    protected $absolutePath = '';

    public function accepts($accepts)
    {
        if (is_string($accepts)) {
            $accepts = explode(',', $accepts);
        }

        if (!empty($accepts) && is_array($accepts)) {

            $within = implode(',', array_map(function ($str) {
                return sprintf("'.%s'", trim($str, " .'")); },
            $accepts));

            $this->setAttribute('accept', str_replace("'",'', $within));
            $this->addValidation('Validate.Inclusion', 'within: ['.$within.'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false');
        }
        return $this;
    }

    public function setAttachment($absolutePath, $filePath)
    {
        $this->absolutePath = $absolutePath;
        $this->setValue($filePath);

        return $this;
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->absolutePath) && !empty($this->getValue())) {
            $output .= '<div class="right">';
            $output .= __('Current attachment:').' ';
            $output .= '<a href="'.$this->absolutePath.'/'.$this->getValue().'" target="_blank">'.basename($this->getValue()).'</a><br/><br/>';
            $output .= '</div>';
        }

        $output .= '<input type="file" '.$this->getAttributeString().'>';

        return $output;
    }
}
