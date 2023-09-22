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
        // __('Link student and parents to the family.')
        // __('Link student to family (who are already in Gibbon).')
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
        $col->addSubheading(__('Family Details'));

        if ($data->hasResult('gibbonFamilyID') || $data->hasData('gibbonFamilyID')) {
            $list = [
                'gibbonFamilyID'    => $data->getAny('gibbonFamilyID'),
                __('Family Name')   => $data->getAny('familyName'),
                __('Address Name')  => $data->getAny('nameAddress'),
            ];

            if ($data->hasResult('familyCreated')) {
                $col->addContent(__('A new family was created for {familyName}.', ['familyName' => $data->getResult('familyName')]));

                if (!$data->hasResult('gibbonFamilyChildID')) {
                    $col->addContent(Format::alert(__('Student could not be linked to family!'), 'warning'));
                }
            } else {
                $list[__('Roles')] = __('System has tried to assign parents "Parent" role access if they did not already have it.');

                if (!$data->hasResult('gibbonFamilyChildID')) {
                    $col->addContent(Format::alert(__('Student could not be linked to family!'), 'warning'));
                } else {
                    $col->addContent(__('Student was linked to the existing family: {familyName}.', ['familyName' => $data->getAny('familyName')]));
                }
            }

            $col->addContent(Format::listDetails($list, 'ul'));

        } else {
            $col->addContent(Format::alert(__('Family could not be created!'), 'warning'));
        }
    }
}
