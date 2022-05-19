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
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class CreateFamilyView extends AbstractFormView
{
    public function getHeading() : string
    {
        return 'Acceptance Options';
    }

    public function getName() : string
    {
        return __('Create Family');
    }

    public function getDescription() : string
    {
        return __('Create a new family or add students to an existing family.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('createFamily', $this->getName())->description($this->getDescription());
            $row->addYesNo('createFamily')->selected('N')->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName(), 'h4');

        if ($data->hasResult('gibbonFamilyID')) {
            $col->addContent(__('Family Details'));
        } else {
            $col->addContent(Format::alert(__('Family could not be created!'), 'warning'));
        }
    }
}
