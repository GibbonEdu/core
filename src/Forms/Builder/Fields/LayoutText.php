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

class LayoutText extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'text' => [
                'label' => '',
                'type'  => 'layout',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Add blocks of text to your form, such as additional information or instructions for users to follow.');
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field): Row
    {
        $row = $form->addRow();
        
        $row->addContent('<p class="mb-0">'.__($field['description']),'</p>');
        
        return $row;
    }
}
