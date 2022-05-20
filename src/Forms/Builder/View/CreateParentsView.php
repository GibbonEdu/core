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
                __('Email') => $data->getAny('email'),
            ];

            if ($data->hasResult('parent1created')) {
                $list += [
                    __('Username') => $data->getResult('username'),
                    __('Password') => $data->getResult('password'),
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
                __('Email') => $data->getAny('email'),
            ];

            if ($data->hasResult('parent2created')) {
                $list += [
                    __('Username') => $data->getResult('username'),
                    __('Password') => $data->getResult('password'),
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
