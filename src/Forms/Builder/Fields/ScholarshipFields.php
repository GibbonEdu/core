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

class ScholarshipFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'headingScholarships' => [
                'label'       => __('Scholarships'),
                'description' => __('Information to display before the scholarship options'),
                'type'        => 'heading',
            ],
            'scholarshipInterest' => [
                'label'       => __('Interest'),
                'description' => __('Indicate if you are interested in a scholarship.'),
                'type'        => 'radio',
            ],
            'scholarshipRequired' => [
                'label'       => __('Required?'),
                'description' => __('Is a scholarship required for you to take up a place at the school?'),
                'type'        => 'radio',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Should the Scholarship Options section be turned on?');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? 'N';

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'scholarshipInterest':
                $row->addLabel('scholarshipInterest', __($field['label']))->description(__($field['description']));
                $row->addYesNoRadio('scholarshipInterest')->inline()->required($required)->checked($default);
                break;

            case 'scholarshipRequired':

                $row->addLabel('scholarshipRequired', __($field['label']))->description(__($field['description']));
                $row->addYesNoRadio('scholarshipRequired')->inline()->required($required)->checked($default);
                break;
        }

        return $row;
    }
}
