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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class MiscellaneousFields extends AbstractFieldGroup
{
    protected $settingGateway;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->settingGateway = $settingGateway;
        $this->fields = [
            'headingMiscellaneous' => [
                'label'       => __('Miscellaneous'),
                'type'        => 'heading',
            ],
            'howDidYouHear' => [
                'label'       => __('How Did You Hear About Us?'),
                'prefill'     => 'Y',
                'acquire'     => ['howDidYouHearMore' => 'varchar'],
                'translate' => 'Y',
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
        $default = $field['defaultValue'] ?? null;
        $accepted = $formBuilder->getConfig('status') == 'Accepted';
        
        if ($field['fieldName'] == 'howDidYouHear' && ($formBuilder->hasConfig('gibbonPersonID') || $formBuilder->hasConfig('gibbonFamilyID'))) {
            return new Row($form->getFactory(), 'howDidYouHear');
        }

        $row = $form->addRow();

        switch ($field['fieldName']) {
            case 'howDidYouHear':
                $howDidYouHear = $this->settingGateway->getSettingByScope('Application Form', 'howDidYouHear');
                $howDidYouHearList = array_map('trim', explode(',', $howDidYouHear));

                $row->addLabel('howDidYouHear', __('How Did You Hear About Us?'));

                if (empty($howDidYouHear)) {
                    $row->addTextField('howDidYouHear')->required()->maxLength(30);
                } else {
                    $row->addSelect('howDidYouHear')->fromArray($howDidYouHearList)->required()->placeholder()->selected($default);
                    $form->toggleVisibilityByClass('tellUsMore')->onSelect('howDidYouHear')->whenNot('');

                    $innerRow = $form->addRow()->addClass('tellUsMore');
                        $innerRow->addLabel('howDidYouHearMore', __('Tell Us More'))->description(__('The name of a person or link to a website, etc.'));
                        $innerRow->addTextField('howDidYouHearMore')->maxLength(255)->setClass('w-64');
                }
                
        }

        return $row;
    }
}
