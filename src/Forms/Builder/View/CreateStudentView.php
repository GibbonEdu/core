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

class CreateStudentView extends AbstractFormView
{

    public function getHeading() : string
    {
        return 'Acceptance Options';
    }

    public function getName() : string
    {
        return __('Create Student');
    }

    public function getDescription() : string
    {
        return __('Create a Gibbon user account for the student.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('createStudent', $this->getName())->description($this->getDescription());
            $row->addYesNo('createStudent')->selected('N')->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading(__('Student Details'));

        if ($data->hasResult('gibbonPersonIDStudent')) {
            $list = [
                'gibbonPersonID'      => $data->getResult('gibbonPersonIDStudent'),
                __('Name')            => Format::name('', $data->get('preferredName'), $data->get('surname'), 'Student'),
                __('Email')           => $data->getAny('email'),
                __('Email Alternate') => $data->getAny('emailAlternate'),
                __('Username')        => $data->getAny('username'),
                __('Password')        => $data->getResult('password'),
            ];

            $col->addContent(Format::listDetails($list));
        } else {
            $col->addContent(Format::alert(__('Student could not be created!'), 'warning'));
        }
    }
}
