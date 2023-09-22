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

class EnrolStudentView extends AbstractFormView
{

    public function getHeading() : string
    {
        return 'Student Enrolment';
    }

    public function getName() : string
    {
        return __('Enrol Student');
    }

    public function getDescription() : string
    {
        return __('Enrol the student in the selected school year.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('enrolStudent', $this->getName())->description($this->getDescription());
            $row->addYesNo('enrolStudent')->selected('N')->required();

        $form->toggleVisibilityByClass('enrolStudent')->onSelect('enrolStudent')->when('Y');

        $row = $form->addRow()->addClass('enrolStudent');
            $row->addLabel('enableLimitedYearsOfEntry', __('Enable Limited Years of Entry'))->description(__('If yes, applicants choices for Year of Entry can be limited to specific school years.'));
            $row->addYesNo('enableLimitedYearsOfEntry')->selected('N')->required();
    
        $form->toggleVisibilityByClass('yearsOfEntry')->onSelect('enableLimitedYearsOfEntry')->when('Y');
    
        $row = $form->addRow()->addClass('yearsOfEntry');
            $row->addLabel('availableYearsOfEntry', __('Available Years of Entry'))->description(__('Which school years should be available to apply to?'));
            $row->addSelectSchoolYear('availableYearsOfEntry', 'Active')
                ->setSize(3)
                ->selectMultiple()
                ->selectAll()
                ->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName());

        if ($data->hasResult('gibbonStudentEnrolmentID')) {
            $col->addContent(Format::alert(__('The student has successfully been enrolled in the specified school year, year group and form group.'), 'success'));

            if ($data->hasResult('autoEnrolCoursesResult')) {
                $col->addContent(Format::alert(__('The student has automatically been enrolled in courses for their Form Group.'), 'success'));
            } else {
                $col->addContent(Format::alert(__('Student could not be automatically enrolled in courses, so this will have to be done manually at a later date.'), 'warning'));
            }
        } else {
            $col->addContent(Format::alert(__('Student could not be enrolled, so this will have to be done manually at a later date.'), 'warning'));
        }
    }
}
