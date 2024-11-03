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
use Gibbon\Data\PasswordPolicy;

/**
 * Password
 *
 * @version v14
 * @since   v14
 */
class Password extends TextField
{
    protected $policy;

    /**
     * Attach the validation requirements for the system-wide password policy.
     * @param PasswordPolicy $policy
     * @return self
     */
    public function addPasswordPolicy(PasswordPolicy $policy)
    {
        $this->policy = $policy;

        if ($patternString = $policy->getValidationPattern()) {
            $this->addValidation('Validate.Format', 'pattern: /'.$patternString.'/, failureMessage: "'.__('Does not meet password policy.').'"');
        }

        return $this;
    }

    /**
     * Adds a button to the field that uses JS to generate and insert a password into the form.
     * @param Form $form
     * @return self
     */
    public function addGeneratePasswordButton($form, $sourceField = 'passwordNew', $confirmField = 'passwordConfirm')
    {
        $button = $form->getFactory()->createButton(__('Generate'));
        $button->addClass('generatePassword')
            ->groupAlign('right')
            ->addData('source', $sourceField)
            ->addData('confirm', $confirmField)
            ->addData('alert', __('Copy this password if required:'))
            ->setTabIndex(-1);

        $this->groupAlign('left')->append($button->getOutput());

        return $this;
    }

    /**
     * Adds the validation to indicate this password field is a confirmation for another field.
     * @return self
     */
    public function addConfirmation($fieldName)
    {
        $this->addValidation('Validate.Confirmation', ['match' => $fieldName]);

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(Password::class, $this->getAttributeArray() + [
            'group'  => $this->group,
            'policy' => !empty($this->policy) ? $this->policy->describe() : [],
            'policyPattern' => !empty($this->policy) ? $this->policy->getValidationPattern() : '',
        ]);
    }
}
