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

namespace Gibbon\Module\Admissions;

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Finance\PaymentGateway;

/**
 * ApplicationBuilder
 *
 * @version v24
 * @since   v24
 */
class ApplicationBuilder extends FormBuilder
{
    protected $officeFields = ['gibbonSchoolYearIDEntry', 'gibbonYearGroupIDEntry', 'dateStart', 'username', 'studentID', 'priority', 'dayType', 'officeNotes', 'PaySubmissionFeeComplete', 'PayProcessingFeeComplete'];


    public function isOfficeOnlyField(string $fieldName) : bool
    {
        return in_array($fieldName, $this->officeFields);
    }

    public function isFieldHidden(array $field) : bool
    {
        return $this->includeHidden != ($field['hidden'] == 'Y') && !$this->isOfficeOnlyField($field['fieldName']);
    }

    public function acquireOfficeOnly()
    {
        $data = [];

        foreach ($this->fields as $fieldName => $field) {
            if ($field['hidden'] != 'Y') continue;

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldValue = $fieldGroup->getFieldDataFromPOST($fieldName, $field);

            if (!is_null($fieldValue) || (!empty($field['type']) && $field['type'] == 'checkbox')) {
                $data[$fieldName] = $fieldValue;
            }
        }

        foreach ($this->officeFields as $fieldName) {
            if (isset($data[$fieldName])) continue;

            $data[$fieldName] = $_POST[$fieldName] ?? null;
        }

        return $data;
    }

    public function edit(Url $action)
    {
        $form = Form::create('formBuilder'.($this->includeHidden ? 'OfficeOnly' : ''), (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', -1);
        $form->addHiddenValues($this->urlParams);

        // Display the Office-Only fields first
        if ($this->includeHidden) {
            $form->addRow()->addHeading('For Office Use', __('For Office Use'));

            $this->addOfficeOnlyFields($form);

            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'N' && !$this->isOfficeOnlyField($field['fieldName'])) continue;
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($this, $form, $field);
                }
            }

            $this->addUserFields($form);
            $this->addPaymentInfo($form);
        } else {

            // Display all non-hidden fields
            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'Y') continue;
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($this, $form, $field);
                }
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__('Submit'));

        return $form;
    }

    public function getJavascript()
    {
        if (!empty($_GET['return']) && stripos($_GET['return'], 'success') !== false) {
            $output = "$(document).ready(function(){
                alert('".__('Your application was successfully submitted. Please read the information in the green box above the application form for additional information.')."');
            });";
        } else {
            $output = "
            $('input,textarea,select').on('input', function() {
                window.onbeforeunload = function(event) {
                    if (event.explicitOriginalTarget.value=='Submit' || event.explicitOriginalTarget.value=='Next') return;
                    return '".__('There are unsaved changes on this page.')."';
                };
            });
        ";
        }

        return "<script type='text/javascript'>{$output}</script>";
    }

    protected function addOfficeOnlyFields(Form $form)
    {
        if (!empty($this->getConfig('status'))) {
            $statuses = [
                'Incomplete'   => __('Incomplete'),
                'Pending'      => __('Pending'),
                'Waiting List' => __('Waiting List'),
                'Rejected'     => __('Rejected'),
                'Withdrawn'    => __('Withdrawn'),
            ];

            $row = $form->addRow();
            if ($this->getConfig('status') != 'Accepted') {
                $row->addLabel('status', __('Status'))->description(__('Manually set status.'));
                $row->addSelect('status')->fromArray($statuses)->required()->selected($this->getConfig('status'));
            } else {
                $row->addLabel('statusField', __('Status'));
                $row->addTextField('statusField')->required()->readOnly()->setValue($this->getConfig('status'));
            }
        }

        if (!$this->hasField('priority')) {
            $row = $form->addRow();
                $row->addLabel('priority', __('Priority'))->description(__('Higher priority applicants appear first in list of applications.'));
                $row->addSelect('priority')->fromArray(range(9, -9))->selected(0)->required()->readonly($this->getConfig('status') == 'Accepted');
        }

        if (!$this->hasField('dateStart')) {
            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'))->description(__('Student\'s intended first day at school.'));
                $row->addDate('dateStart')->required()->readonly($this->getConfig('status') == 'Accepted');
        }

        
    }

    protected function addUserFields(Form $form)
    {
        if (!$this->hasField('username')) {
            $row = $form->addRow();
                $row->addLabel('username', __('Username'))->description(__('System login name.'));
                $row->addUsername('username')
                    ->readonly($this->getConfig('status') == 'Accepted')
                    ->addGenerateUsernameButton($form)
                    ->uniqueField('./modules/Admissions/applications_manage_edit_usernameAjax.php', ['fieldName' => 'username', 'gibbonAdmissionsApplicationID' => $this->getConfig('foreignTableID')]);
        }

        if (!$this->hasField('studentID')) {
            $row = $form->addRow();
            $row->addLabel('studentID', __('Student ID'));
            $row->addTextField('studentID')
                ->maxLength(10)
                ->uniqueField('./modules/Admissions/applications_manage_edit_studentIDAjax.php', ['gibbonAdmissionsApplicationID' => $this->getConfig('foreignTableID')])
                ->readonly($this->getConfig('status') == 'Accepted');
        }

        if (!$this->hasField('officeNotes')) {
            $col = $form->addRow()->addColumn();
                $col->addLabel('officeNotes', __('Notes'));
                $col->addTextArea('officeNotes')->setRows(4);
        }
    }

    protected function addPaymentInfo(Form $form)
    {
        $formSubmissionFee = $this->getConfig('formSubmissionFee');
        $formProcessingFee =  $this->getConfig('formProcessingFee');

        if (empty($formSubmissionFee) && empty($formProcessingFee)) return;

        $form->addRow()->addHeading('Payment Details', __('Payment Details'));

        $paymentMadeOptions = [
            'N'         => __('No'),
            'Y'         => __('Yes'),
            'Exemption' => __('Exemption'),
        ];
        
        $results = json_decode($this->getConfig('result', ''), true) ?? [];

        $paymentGateway = $this->getContainer()->get(PaymentGateway::class);
    
        if ($formSubmissionFee > 0 and is_numeric($formSubmissionFee)) {
            // PAYMENT MADE
            $row = $form->addRow();
                $row->addLabel('PaySubmissionFeeComplete', __('Payment on Submission'))->description(sprintf(__('Has payment (%1$s %2$s) been made for this application.'), $this->session->get('currency'), $formSubmissionFee));
                $row->addSelect('PaySubmissionFeeComplete')->fromArray($paymentMadeOptions)->required();
    
            // PAYMENT DETAILS
            $submitPayment = $paymentGateway->getByID($this->getConfig('gibbonPaymentIDSubmit'));
            if (!empty($submitPayment)) {
    
                $row = $form->addRow();
                    $column = $row->addColumn()->addClass('right');
                    $column->addContent(__('Payment Token:').' '.$submitPayment['paymentToken']);
                    $column->addContent(__('Payment Payer ID:').' '.$submitPayment['paymentPayerID']);
                    $column->addContent(__('Payment Transaction ID:').' '.$submitPayment['paymentTransactionID']);
                    $column->addContent(__('Payment Amount:').' '.$submitPayment['amount']);
            }
        }
    
        if ($formProcessingFee > 0 and is_numeric($formProcessingFee)) {
            // PAYMENT MADE
            $row = $form->addRow();
                $row->addLabel('PayProcessingFeeComplete', __('Payment for Processing'))->description(sprintf(__('Has payment (%1$s %2$s) been made for this application.'), $this->session->get('currency'), $formProcessingFee));
                $row->addSelect('PayProcessingFeeComplete')->fromArray($paymentMadeOptions)->required();
    
            // PAYMENT DETAILS
            $processPayment = $paymentGateway->getByID($this->getConfig('gibbonPaymentIDProcess'));
            if (!empty($processPayment)) {
                $row = $form->addRow();
                    $column = $row->addColumn()->addClass('right');
                    $column->addContent(__('Payment Token:').' '.$processPayment['paymentToken']);
                    $column->addContent(__('Payment Payer ID:').' '.$processPayment['paymentPayerID']);
                    $column->addContent(__('Payment Transaction ID:').' '.$processPayment['paymentTransactionID']);
                    $column->addContent(__('Payment Amount:').' '.$processPayment['amount']);
            }
        }
    }
}
