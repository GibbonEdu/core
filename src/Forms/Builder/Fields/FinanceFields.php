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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Domain\Finance\FinanceFeeCategoryGateway;
use Gibbon\Forms\Builder\FormBuilderInterface;

class FinanceFields extends AbstractFieldGroup
{
    protected $feeCategoryGateway;

    public function __construct(FinanceFeeCategoryGateway $feeCategoryGateway)
    {
        $this->feeCategoryGateway = $feeCategoryGateway;
        $this->fields = [
            'headingPayment' => [
                'label'       => __('Payment'),
                'description' => __('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.'),
                'type'        => 'heading',
            ],
            'payment' => [
                'label'       => __('Send Future Invoices To'),
                'required' => 'X',
                'prefill'  => 'Y',
                'translate' => 'Y',
            ],
            'companyName' => [
                'label'       => __('Company Name'),
                'required' => 'X',
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
            'companyContact' => [
                'label'       => __('Company Contact Person'),
                'required' => 'X',
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
            'companyAddress' => [
                'label'       => __('Company Address'),
                'required' => 'X',
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
            'companyEmail' => [
                'label'       => __('Company Emails'),
                'description' => __('Comma-separated list of email address'),
                'required' => 'X',
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
            'companyCCFamily' => [
                'label'       => __('CC Family?'),
                'description' => __('Should the family be sent a copy of billing emails?'),
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
                'type'        => 'radio',
            ],
            'companyPhone' => [
                'label'       => __('Company Phone'),
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
            'companyAll' => [
                'label'       => __('Company All?'),
                'description' => __('Should all items be billed to the specified company, or just some?'),
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
                'type'        => 'radio',
            ],
            'gibbonFinanceFeeCategoryIDList' => [
                'label'       => __('Company Fee Categories'),
                'description' => __('If the specified company is not paying all fees, which categories are they paying?'),
                'prefill'  => 'Y',
                'conditional' => ['payment' => 'Company'],
            ],
        ];
    }

    public function getDescription() : string
    {
        return '';
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row
    {
        $required = $this->getRequired($formBuilder, $field);
        $default = $field['defaultValue'] ?? null;

        $row = $form->addRow();

        switch ($field['fieldName']) {

            case 'payment':
                $form->toggleVisibilityByClass('paymentCompany')->onRadio('payment')->when('Company');
                
                $row->addLabel('payment', __($field['label']))->description(__($field['description']));
                $row->addRadio('payment')
                    ->fromArray(array('Family' => __('Family'), 'Company' => __('Company')))
                    ->checked($default ?? 'Family')
                    ->inline()
                    ->required($required);
                break;
            
    
            // COMPANY DETAILS
            case 'companyName':
                $row->addClass('paymentCompany');
                $row->addLabel('companyName', __($field['label']))->description(__($field['description']));
                $row->addTextField('companyName')->required($required)->setValue($default)->maxLength(100);
                break;

            case 'companyContact':
                $row->addClass('paymentCompany');
                $row->addLabel('companyContact', __($field['label']))->description(__($field['description']));
                $row->addTextField('companyContact')->required($required)->setValue($default)->maxLength(100);
                break;

            case 'companyAddress':
                $row->addClass('paymentCompany');
                $row->addLabel('companyAddress', __($field['label']))->description(__($field['description']));
                $row->addTextField('companyAddress')->required($required)->setValue($default)->maxLength(255);
                break;

            case 'companyEmail':
                $row->addClass('paymentCompany');
                $row->addLabel('companyEmail', __($field['label']))->description(__($field['description']));
                $row->addTextField('companyEmail')->required($required)->setValue($default);
                break;

            case 'companyCCFamily':
                $row->addClass('paymentCompany');
                $row->addLabel('companyCCFamily', __($field['label']))->description(__($field['description']));
                $row->addYesNo('companyCCFamily')->required($required)->selected($default);
                break;

            case 'companyPhone':
                $row->addClass('paymentCompany');
                $row->addLabel('companyPhone', __($field['label']))->description(__($field['description']));
                $row->addTextField('companyPhone')->required($required)->setValue($default)->maxLength(20);
                break;

            case 'companyAll':
                // COMPANY FEE CATEGORIES
                $categories = $this->feeCategoryGateway->selectActiveFeeCategories()->fetchKeyPair();
        
                if (empty($categories)) {
                    $form->addHiddenValue('companyAll', 'Y');
                } else {
                    $col = $row->addClass('paymentCompany')->addColumn()->setClass('flex flex-row justify-between');
                        $col->addLabel('companyAll', __($field['label']))->description(__($field['description']));
                        $col->addRadio('companyAll')->fromArray(['Y' => __('All'), 'N' => __('Selected')])->checked($default ?? 'Y')->inline();
                }
                break;

            case 'gibbonFinanceFeeCategoryIDList':
                $categories = $this->feeCategoryGateway->selectActiveFeeCategories()->fetchKeyPair();
                if (!empty($categories)) {
                    $form->toggleVisibilityByClass('paymentCompanyCategories')->onRadio('companyAll')->when('N');
        
                    $existingFeeCategoryIDList = $formBuilder->getConfig('gibbonFinanceFeeCategoryIDList', '');
                    $existingFeeCategoryIDList = is_array($existingFeeCategoryIDList) ? implode(',', $existingFeeCategoryIDList) : $existingFeeCategoryIDList;
                    $existingFeeCategoryIDList = !empty($existingFeeCategoryIDList) ? $existingFeeCategoryIDList : $default;

                    $col = $row->addClass('paymentCompanyCategories')->addColumn()->setClass('flex flex-row justify-between');
                        $col->addLabel('gibbonFinanceFeeCategoryIDList', __($field['label']))->description(__($field['description']));
                        $col->addCheckbox('gibbonFinanceFeeCategoryIDList')
                            ->fromArray($categories)
                            ->fromArray(['0001' => __('Other')])
                            ->loadFromCSV($existingFeeCategoryIDList);  
                }
                break;
        }

        return $row;
    }

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, &$data = [])
    {
        $fieldValue = $data[$fieldName] ?? '';

        if ($fieldName == 'gibbonFinanceFeeCategoryIDList' && !empty($fieldValue)) {
            $categories = $this->feeCategoryGateway->selectActiveFeeCategories()->fetchKeyPair();
            $selected = is_array($fieldValue) ? $fieldValue : explode(',', $fieldValue);

            return implode(', ', array_intersect_key($categories, array_flip($selected)));
        }

        return parent::displayFieldValue($formBuilder, $fieldName, $field, $data);
    }
}
