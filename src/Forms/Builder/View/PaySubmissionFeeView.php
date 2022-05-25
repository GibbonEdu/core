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
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Services\Format;

class PaySubmissionFeeView extends AbstractFormView
{

    public function getHeading() : string
    {
        return 'Payment Options';
    }

    public function getName() : string
    {
        return __('Application Submission Fee');
    }

    public function getDescription() : string
    {
        return __('The cost of applying to the school. Paid when submitting the application form.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow()->setHeading($this->getHeading());
            $row->addLabel('formSubmissionFee', $this->getName())->description($this->getDescription());
            $row->addCurrency('formSubmissionFee');
    }

    public function display(Form $form, FormDataInterface $formData)
    {
        if (!$formData->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName(), 'h4');

        if ($formData->hasResult($this->getResultName())) {
            $col->addContent(Format::alert(sprintf(__('The student has automatically been assigned to %1$s house.'), $formData->getResult($this->getResultName())), 'success'));
        } else {
            $col->addContent(Format::alert(__('The student could not automatically be added to a house, you may wish to manually add them to a house.'), 'warning'));
        }
    }
}
