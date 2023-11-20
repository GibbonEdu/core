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
use Gibbon\Contracts\Services\Payment;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class PaySubmissionFeeView extends AbstractFormView
{
    protected $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

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
        $col->addLabel($this->getResultName(), $this->getName());

        $messages = $this->payment->getReturnMessages();

        if ($formData->hasResult('gibbonPaymentIDSubmit')) {
            $col->addContent(Format::alert($messages[Payment::RETURN_SUCCESS], 'success'));
        } else {
            $return = $formData->getResult($this->getResultName());
            $class = stripos($return, 'success') !== false ? 'success' : (stripos($return, 'warning') !== false ? 'warning' : 'error');
            $col->addContent(Format::alert($messages[$return] ?? $messages[Payment::RETURN_ERROR_CONFIG], $class));
        }
    }
}
