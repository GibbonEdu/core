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

use Gibbon\Domain\Finance\InvoiceeGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateInvoiceeView;

class CreateInvoicee extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['payment', 'companyName', 'companyEmail'];

    protected $invoiceeGateway;

    public function __construct(InvoiceeGateway $invoiceeGateway)
    {
        $this->invoiceeGateway = $invoiceeGateway;
    }

    public function getViewClass() : string
    {
        return CreateInvoiceeView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createInvoicee') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->hasAll(['gibbonPersonIDStudent', 'payment'])) {
            return;
        }

        $categoryList = $formData->get('companyAll') == 'N' ? $formData->get('gibbonFinanceFeeCategoryIDList') : '';

        // Create a new finance invoicee
        $gibbonFinanceInvoiceeID = $this->invoiceeGateway->insert([
            'gibbonPersonID'  => $formData->get('gibbonPersonIDStudent'),
            'invoiceTo'       => $formData->get('invoiceTo', 'Family'),
            'companyName'     => $formData->get('companyName'),
            'companyContact'  => $formData->get('companyContact'),
            'companyAddress'  => $formData->get('companyAddress'),
            'companyEmail'    => $formData->get('companyEmail'),
            'companyCCFamily' => $formData->get('companyCCFamily'),
            'companyPhone'    => $formData->get('companyPhone'),
            'companyAll'      => $formData->get('companyAll'),
            'gibbonFinanceFeeCategoryIDList ' => is_array($categoryList)? implode(',', $categoryList) : $categoryList,
        ]);

        $formData->set('gibbonFinanceInvoiceeID', $gibbonFinanceInvoiceeID);
        $this->setResult($gibbonFinanceInvoiceeID);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonFinanceInvoiceeID')) return;

        $this->invoiceeGateway->delete($formData->get('gibbonFinanceInvoiceeID'));
        
        $formData->set('gibbonFinanceInvoiceeID', null);
    }
}
