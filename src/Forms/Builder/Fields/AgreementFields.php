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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class AgreementFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'headingAgreement' => [
                'label'       => __('Agreement'),
                'description' => __('This is example text. Edit it to suit your school context.'),
                'type'        => 'heading',
            ],
            'agreement' => [
                'label'       => __('Do you agree to the above?'),
                'type'        => 'checkbox',
                'required'    => 'X',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Adds a checkbox that must be checked to confirm agreement with a statement before the form can be submitted.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;
        
        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'agreement':
                $row->addLabel($field['fieldName'], __($field['label']))->description(__($field['description']));
                $row->addCheckbox($field['fieldName'])->description(__('Yes'))->setValue('on')->required($required)->checked($default == 'Y');
                break;
        }

        return $row;
    }
}
