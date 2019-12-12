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

use Gibbon\Forms\Input\TextArea;

/**
 * Comment Editor
 *
 * @version v19
 * @since   v19
 */
class CommentEditor extends TextArea
{
    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $maxlength = $this->getAttribute('maxlength') ?? 0;
        $currentLength = strlen($this->getValue());

        $this->setRows(min(10, max(4, $maxlength / 150)));
        $this->addClass('characterCount w-full p-1 text-sm font-sans leading-tight');
        $this->addData('maxlength', $maxlength);

        $output .= "<div class='characterInfo inline-block text-xxs text-gray-600 -mt-6 float-right h-6'>";
        $output .= "<span class='commentStatusName -mt-1 mr-2 tag warning text-xxs align-middle hidden'>".__('Name not found')."</span>";
        $output .= "<span class='commentStatusPronoun -mt-1 mr-2 tag warning text-xxs align-middle hidden'>".__('Check pronouns')."</span>";
        if ($maxlength > 0) {
            $output .= "<span class='inline-block leading-loose align-middle'><span class='currentLength'>";
            $output .= $currentLength;
            $output .= "</span> / {$maxlength} characters</span>";
        }
        $output .= '<img class="inline-block ml-2 w-4 h-4 align-text-bottom" title="'.__("Paste & Replace: Use the {name} placeholder to insert a student's name when pasting comments. Pronouns will automatically be swapped based on the student's gender.").
        '" src="./themes/Default/img/help.png" >';
        $output .= "</div>";
        
        $output .= parent::getElement();

        

        return $output;
    }
}
