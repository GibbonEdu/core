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

class CreateStudentView implements FormViewInterface
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

        $form->toggleVisibilityByClass('createStudent')->onSelect('createStudent')->when('Y');

        $row = $form->addRow()->setClass('createStudent');
            $row->addLabel('studentDefaultEmail', __('Student Default Email'))->description(__('Set default email for students on acceptance, using [username] to insert username.'));
            $row->addEmail('studentDefaultEmail');
    
        $row = $form->addRow()->setClass('createStudent');
            $row->addLabel('studentDefaultWebsite', __('Student Default Website'))->description(__('Set default website for students on acceptance, using [username] to insert username.'));
            $row->addURL('studentDefaultWebsite');
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists('createStudentResult')) return;

        $row = $form->addRow();

        if ($data->hasResult('gibbonPersonIDStudent')) {
            $row->addContent('gibbonPersonID: '.$data->getResult('gibbonPersonIDStudent'));
        } else {
            $row->addContent(__('Student could not be created!'));
        }
    }
}
