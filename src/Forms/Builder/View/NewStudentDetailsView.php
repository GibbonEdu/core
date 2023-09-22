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
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class NewStudentDetailsView extends AbstractFormView
{
    protected $session;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function getHeading() : string
    {
        return 'Acceptance Options';
    }

    public function getName() : string
    {
        return __('Student Email & Website');
    }

    public function getDescription() : string
    {
        return __('Automatically set new student email address and notify the system administrator.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow()->setHeading($this->getHeading())->addClass('createStudent');
            $row->addLabel('newStudentDetails', $this->getName())->description($this->getDescription());
            $row->addYesNo('newStudentDetails')->selected('N')->required();

        $form->toggleVisibilityByClass('newStudentDetails')->onSelect('newStudentDetails')->when('Y');

        $row = $form->addRow()->setClass('newStudentDetails');
            $row->addLabel('studentDefaultEmail', __('Student Default Email'))->description(__('Set default email for students on acceptance, using [username] to insert username.'));
            $row->addEmail('studentDefaultEmail');
    
        $row = $form->addRow()->setClass('newStudentDetails');
            $row->addLabel('studentDefaultWebsite', __('Student Default Website'))->description(__('Set default website for students on acceptance, using [username] to insert username.'));
            $row->addURL('studentDefaultWebsite');
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName());

        if ($data->hasResult($this->getResultName())) {
            $col->addContent(Format::alert(sprintf(__('A request to create a student email address and/or website address was successfully sent to %1$s.'), $this->session->get('organisationAdministratorName')), 'success'));
        } else {
            $col->addContent(Format::alert(sprintf(__('A request to create a student email address and/or website address failed. Please contact %1$s to request these manually.'), $this->session->get('organisationAdministratorName')), 'warning'));
        }
    }
}
