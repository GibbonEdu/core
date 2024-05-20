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

use Gibbon\Forms\Input\TextArea;

/**
 * Comment Editor
 *
 * @version v20
 * @since   v20
 */
class CommentEditor extends TextArea
{
    protected $checkName = false;
    protected $checkPronouns = false;

    public function __construct($name)
    {
        parent::__construct($name);
    }

    /**
     * Pass in a student preferred name to enable name checking.
     *
     * @return self
     */
    public function checkName($preferredName)
    {
        $this->addData('name', $preferredName);
        $this->checkName = true;

        return $this;
    }

    /**
     * Pass in a student gender to enable pronoun checking.
     *
     * @return self
     */
    public function checkPronouns($gender)
    {
        $this->addData('gender', $gender);
        $this->checkPronouns = true;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $maxlength = $this->getAttribute('maxlength') ?? 0;
        $currentLength = !empty($this->getValue()) ? strlen($this->getValue()) : 0;

        $this->setRows(5);
        $this->addClass('commentEditor characterCount w-full p-1 text-sm font-sans leading-tight');
        $this->addData('maxlength', $maxlength);
        $this->setAttribute('style', 'min-height: 100px;');

        $output .= "<div class='characterInfo inline-block text-xxs text-gray-600 -mt-6 float-right h-6'>";

        $title = __('Paste & Replace:');
        if ($this->checkName) {
            $title .= ' '.__("Use the {name} placeholder to insert a student's name when pasting comments.");
            $output .= "<span class='commentStatusName -mt-1 mr-2 tag warning text-xxs align-middle hidden'>".__('Name not found')."</span>";
        }

        if ($this->checkPronouns) {
            $title .= ' '.__("Pronouns will automatically be swapped based on the student's gender.");
            $output .= "<span class='commentStatusPronoun -mt-1 mr-2 tag warning text-xxs align-middle hidden'>".__('Check pronouns')."</span>";
        }

        if ($maxlength > 0) {
            $output .= "<span class='inline-block leading-loose align-middle pr-px'><span class='currentLength'>";
            $output .= $currentLength;
            $output .= "</span> / {$maxlength} characters</span>";
        }

        if ($this->checkName || $this->checkPronouns) {
            $output .= '<img class="inline-block ml-2 w-4 h-4 align-text-bottom" title="'.$title.'" src="./themes/Default/img/help.png" >';
        }

        $output .= "</div>";
        
        $output .= parent::getElement();

        $output .= '<script type="text/javascript">
             $("#'.$this->getID().'").gibbonCommentEditor('.json_encode(['autosize' => true]).');
             
        </script>';

        return $output;
    }
}
