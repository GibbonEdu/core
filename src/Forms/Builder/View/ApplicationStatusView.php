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
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class ApplicationStatusView extends AbstractFormView
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
        return __('Application Status');
    }

    public function getDescription() : string
    {
        return __('Set the status of the application to "Accepted".');
    }

    public function configure(Form $form)
    {

    }

    public function display(Form $form, FormDataInterface $data)
    {
        // if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName());

        $list[__('Status')] = $data->getStatus();
        $list[__('Date')] = Format::dateTimeReadable($data->getResult('statusDate'));

        $col->addContent(Format::listDetails($list));

        if ($data->getStatus() == 'Accepted') {
            $col->addContent(Format::alert(str_replace('ICHK', $this->session->get('organisationNameShort'), __('Applicant has been successfully accepted into ICHK.')), 'success'));
        }

        // echo ' <i><u>'.__('You may wish to now do the following:').'</u></i><br/>';
        // echo '<ol>';
        // echo '<li>'.__('Enrol the student in the relevant academic year.').'</li>';
        // echo '<li>'.__('Create a note of the student\'s scholarship information outside of Gibbon.').'</li>';
        // echo '<li>'.__('Create a timetable for the student.').'</li>';
        // echo '<li>'.__('Inform the student and parents of their Gibbon login details (if this was not done automatically).').'</li>';
        // echo '</ol>';
        // echo '</div>';

    }
}
