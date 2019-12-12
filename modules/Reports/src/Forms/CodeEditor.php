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

namespace Gibbon\Module\Reports\Forms;

use Gibbon\Forms\Input\Input;

/**
 * CodeEditor
 *
 * @version v19
 * @since   v19
 */
class CodeEditor extends Input
{
    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $text = $this->getAttribute('value');
        $this->setAttribute('value', '');

        $output = '<textarea '.$this->getAttributeString().' style="display: none;">';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</textarea>';

        $output .= '<div id="editor" class="w-full" style="height: 400px;">';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</div>';

        $output .= '<script src="./modules/Reports/js/ace/ace.js" type="text/javascript" charset="utf-8"></script>';
        $output .= '<script>
            var editor = ace.edit("editor");
            editor.getSession().setMode("ace/mode/twig");
            editor.getSession().setUseWrapMode(true);
            editor.getSession().on("change", function(e) {
                $("#'.$this->getID().'").val(editor.getSession().getValue());
            });
        </script>';

        return $output;
    }
}
