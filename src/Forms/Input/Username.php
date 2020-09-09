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
 * Username
 *
 * @version v17
 * @since   v17
 */
class Username extends TextField
{
    /**
     * Adds a button to the field that uses JS to generate and insert a username into the form.
     * @param Form $form
     * @return self
     */
    public function addGenerateUsernameButton($form)
    {
        if ($this->getReadonly()) {
            return $this;
        }

        $alertText = __('The following fields are required to generate a username:')."\n\n";
        $alertText .= __('Primary Role').', '.__('Preferred Name').', '.__('First Name').', '.__('Surname')."\n";

        $button = $form->getFactory()->createButton(__('Generate'));
        $button->addClass('generateUsername -ml-px rounded-r-sm')
            ->addData('alert', $alertText)
            ->setTabIndex(-1);

        $this->append($button->getOutput());

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $this->maxLength(20)
            ->uniqueField('./publicRegistrationCheck.php', ['currentUsername' => $this->getValue()])
            ->addValidation('Validate.Format', 'pattern: /^[a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\u3040-\u309F\u3400-\u4DBF\u4E00-\u9FFF\u2B740–\u2B81F0-9_\-\.]*$/u, failureMessage: "'.__('Must be alphanumeric').'"');

        return parent::getElement();
    }
}
