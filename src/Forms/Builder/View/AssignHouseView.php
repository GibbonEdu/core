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

namespace Gibbon\Forms\Builder\View;

use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Services\Format;

class AssignHouseView extends AbstractFormView
{

    public function getHeading() : string
    {
        return 'Acceptance Options';
    }

    public function getName() : string
    {
        return __('Auto House Assign');
    }

    public function getDescription() : string
    {
        return __('Automatically place the student in a house.');
    }

    public function configure(Form $form)
    {
        $form->toggleVisibilityByClass('createStudent')->onSelect('createStudent')->when('Y');

        $row = $form->addRow()->setClass('createStudent')->setHeading($this->getHeading());
            $row->addLabel('autoHouseAssign', $this->getName())->description($this->getDescription());
            $row->addYesNo('autoHouseAssign')->required()->selected('N');
    }

    public function display(Form $form, FormDataInterface $formData)
    {
        if (!$formData->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName(), 'h4');

        if ($formData->hasResult($this->getResultName())) {
            $col->addContent(Format::alert(sprintf(__('The student has automatically been assigned to %1$s house.'), $formData->getResult($this->getResultName())), 'success'));
        } else {
            $col->addContent(Format::alert(__('The student could not automatically be added to a house, you may wish to manually add them to a house.'), 'warning'));
        }
    }
}
