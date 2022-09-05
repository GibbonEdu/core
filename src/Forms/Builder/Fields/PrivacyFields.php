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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class PrivacyFields extends AbstractFieldGroup
{
    protected $settingGateway;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->settingGateway = $settingGateway;
        $privacyBlurb = $this->settingGateway->getSettingByScope('User Admin', 'privacyBlurb');

        $this->fields = [
            'headingPrivacyStatement' => [
                'label'       => __('Privacy Statement'),
                'description' => $privacyBlurb ?? __('This is example text. Edit it to suit your school context.'),
                'type'        => 'subheading',
            ],
            'privacyOptions' => [
                'label'       => __('Privacy Options'),
            ],
        ];
    }

    public function getDescription() : string
    {
        return '';
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'privacyBlurb':
                $row->addSubheading(__($field['label']))->append(__($field['description']));
                break;

            case 'privacyOptions':
                $privacyOptions = $this->settingGateway->getSettingByScope('User Admin', 'privacyOptions');
                $options = array_map('trim', explode(',', $privacyOptions));

                $row->addLabel('privacyOptions[]', __($field['label']))->description(__($field['description']));
                $row->addCheckbox('privacyOptions[]')->fromArray($options)->addClass('md:max-w-lg')->required($required);
                break;
        }

        return $row;
    }
}
