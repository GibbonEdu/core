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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Contracts\Services\Payment;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use Gibbon\Forms\Builder\View\PaySubmissionFeeView;

class PaySubmissionFee extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['Payment Gateway'];

    private $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getViewClass() : string
    {
        return PaySubmissionFeeView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return !empty($builder->getConfig('formSubmissionFee'));
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $submissionFee = $builder->getConfig('formSubmissionFee');

        if (!is_numeric($submissionFee) || $submissionFee <= 0) return;

        $formData->setResult('redirect', 'modules/Admissions/applicationFormFeeProcess.php');
        $formData->setResult('redirectParams', [
            'formFee'      => $submissionFee,
            'formFeeType'  => 'formSubmissionFee',
            'gibbonFormID' => $builder->getDetail('gibbonFormID'),
            'pageNumber'   => $builder->getPageNumber(),
            'accessID'     => $_REQUEST['accessID'] ?? '',
            'identifier'   => $_REQUEST['identifier'] ?? '',
            'source'       => 'submission',
        ]);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $formData->setResult('redirect', null);
        $formData->setResult('redirectParams', null);
    }

    public function verify(FormBuilderInterface $builder, FormDataInterface $formData = null)
    {
        if (!$this->payment->isEnabled()) {
            throw new MissingFieldException('Payment Gateway');
        }
    }
}
