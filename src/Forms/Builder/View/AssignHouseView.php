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

namespace Gibbon\Forms\Builder\View;

use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\View\FormViewInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class AssignHouseView implements FormViewInterface
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

        $row = $form->addRow()->setClass('createStudent');
            $row->addLabel('autoHouseAssign', $this->getName())->description($this->getDescription());
            $row->addYesNo('autoHouseAssign')->required()->selected('N');
    }

    public function display(Form $form, FormDataInterface $formData)
    {
        if (!$formData->exists('assignHouseResult')) return;

        $row = $form->addRow();

        if ($formData->get('assignHouseResult')) {
            $row->addContent(sprintf(__('The student has automatically been assigned to %1$s house.'), $formData->get('assignHouseResult')));
        } else {
            $row->addContent(__('The student could not automatically be added to a house, you may wish to manually add them to a house.'));
        }
    }
}
