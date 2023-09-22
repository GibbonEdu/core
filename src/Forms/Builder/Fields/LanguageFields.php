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

class LanguageFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'headingLanguageSelection' => [
                'label'       => __('Language Selection'),
                'description' => __('This is example text. Edit it to suit your school context.'),
                'type'        => 'heading',
            ],
            'languageChoice' => [
                'label'       => __('Language Choice'),
                'description' => __('Please choose preferred additional language to study.'),
                'type'        => 'select',
            ],
            'languageChoiceExperience' => [
                'label'       => __('Language Choice Experience'),
                'description' => __('Has the applicant studied the selected language before? If so, please describe the level and type of experience.'),
                'columns'     => 2,
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Set values for applicants to specify which language they wish to learn.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'languageBlurb':
                $row->addHeading(__($field['label']))->append(__($field['description']));
                break;

            case 'languageChoice':
                $row->addLabel('languageChoice', __($field['label']))->description(__($field['description']));
                $row->addSelect('languageChoice')->fromString($field['options'] ?? '')->required($required)->selected($default)->placeholder();
                break;

            case 'languageChoiceExperience':

                $form->toggleVisibilityByClass('languageChoice')->onSelect('languageChoice')->whenNot(['', 'Please select...']);
                
                $column = $row->addClass('languageChoice')->addColumn();
                $column->addLabel('languageChoiceExperience', __($field['label']))->description(__($field['description']));
                $column->addTextArea('languageChoiceExperience')->required($required)->setValue($default)->setRows(5)->setClass('w-full flex-1');
                break;
        }

        return $row;
    }
}
