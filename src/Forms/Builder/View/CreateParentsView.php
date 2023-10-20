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

class CreateParentsView extends AbstractFormView
{
    public function getHeading() : string
    {
        return 'Acceptance Options';
    }

    public function getName() : string
    {
        return __('Create Parents');
    }

    public function getDescription() : string
    {
        // __('Create user accounts for the parents.'parent1)
        return __("Create a Gibbon user account for one or more parents and connect them to the student's family.");
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('createParents', $this->getName())->description($this->getDescription());
            $row->addYesNo('createParents')->selected('N')->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        
        // PARENT 1
        $col->addSubheading(__('Parent 1'));

        if ($data->hasResult('gibbonPersonIDParent1') || $data->hasData('gibbonPersonIDParent1')) {
            $list = [
                'gibbonPersonID' => $data->getAny('gibbonPersonIDParent1'),
                __('Name') => Format::name($data->get('parent1title'), $data->get('parent1preferredName'), $data->get('parent1surname'), 'Parent'),
                __('Email') => $data->getAny('parent1email'),
            ];

            if ($data->hasResult('parent1created')) {
                $list += [
                    __('Username') => $data->getResult('parent1username'),
                    __('Password') => $data->getResult('parent1password'),
                ];
            } else {
                $col->addContent(__('Parent 1 already exists in Gibbon, and so does not need a new account.'));
            }

            if (!$data->hasResult('parent1adultLinked')) {
                $col->addContent(Format::alert(__('Parent 1 could not be linked to family!'), 'warning'));
            }
            
            $col->addContent(Format::listDetails($list, 'ul'));
        } else {
            $col->addContent(Format::alert(__('Parent 1 could not be created!'), 'warning'));
        }

        if (!$data->hasAll(['parent2surname', 'parent2preferredName'])) return;

        // PARENT 2
        $col->addSubheading(__('Parent 2'));

        if ($data->hasResult('gibbonPersonIDParent2')) {
            $list = [
                'gibbonPersonID' => $data->getAny('gibbonPersonIDParent2'),
                __('Name') => Format::name($data->get('parent2title'), $data->get('parent2preferredName'), $data->get('parent2surname'), 'Parent'),
                __('Email') => $data->getAny('parent2email'),
            ];

            if ($data->hasResult('parent2created')) {
                $list += [
                    __('Username') => $data->getResult('parent2username'),
                    __('Password') => $data->getResult('parent2password'),
                ];
            }

            if (!$data->hasResult('parent2adultLinked')) {
                $col->addContent(Format::alert(__('Parent 2 could not be linked to family!'), 'warning'));
            }
            
            $col->addContent(Format::listDetails($list, 'ul'));
        } else {
            $col->addContent(Format::alert(__('Parent 2 could not be created!'), 'warning'));
        }
    }
}
