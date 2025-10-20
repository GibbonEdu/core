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

use Gibbon\Forms\Input\Input;
use Gibbon\View\Component;

/**
 * CodeEditor
 *
 * @version v20
 * @since   v20
 */
class CodeEditor extends Input
{
    protected $mode = 'mysql';
    protected $height = '400';
    protected $autocomplete = null;

    public function setMode($mode)
    {
        $this->mode = $mode;
        
        return $this;
    }

    public function autocomplete($wordList)
    {
        $this->autocomplete = is_array($wordList) ? $wordList : explode(',', $wordList);
        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;

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

        return Component::render(CodeEditor::class, $this->getAttributeArray() + [
            'text'         => $text,
            'mode'         => $this->mode,
            'height'       => $this->height,
            'autocomplete' => $this->autocomplete,
        ]);
    }
}
