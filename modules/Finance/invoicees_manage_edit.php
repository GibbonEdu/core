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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $search = $_GET['search'] ?? '';
    $allUsers = $_GET['allUsers'] ?? '';
    $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'] ?? '';

    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Invoicees'), 'invoicees_manage.php')
        ->add(__('Edit Invoicee'));

    if ($search != '' or $allUsers == 'on') {
        $params = [
            "search" => $search,
            "allUsers" => $allUsers,
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'invoicees_manage.php')->withQueryParams($params));
    }

    //Check if invoicee is specified
    if ($gibbonFinanceInvoiceeID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
            $sql = 'SELECT surname, preferredName, status, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            // DISPLAY INVOICEE DATA
            $table = DataTable::createDetails('personal');
                $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', 'true']));
                $table->addColumn('status', __('Status'))->translatable();
            echo $table->render([$values]);

            $form = Form::create('updateFinance', $session->get('absoluteURL').'/modules/'.$session->get('module')."/invoicees_manage_editProcess.php?gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&search=".$search.'&allUsers='.$allUsers);

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('existing', isset($values['gibbonFinanceInvoiceeUpdateID'])? $values['gibbonFinanceInvoiceeUpdateID'] : 'N');

            $form->addRow()->addHeading('Invoice To', __('Invoice To'));

            $form->addRow()->addContent(__('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.'))->wrap('<p>', '</p>');

            $row = $form->addRow();
                $row->addLabel('invoiceTo', __('Send Invoices To'));
                $row->addRadio('invoiceTo')
                    ->fromArray(array('Family' => __('Family'), 'Company' => __('Company')))
                    ->inline();

            $form->toggleVisibilityByClass('paymentCompany')->onRadio('invoiceTo')->when('Company');

            // COMPANY DETAILS
            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyName', __('Company Name'));
                $row->addTextField('companyName')->required()->maxLength(100);

            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyContact', __('Company Contact Person'));
                $row->addTextField('companyContact')->required()->maxLength(100);

            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyAddress', __('Company Address'));
                $row->addTextField('companyAddress')->required()->maxLength(255);

            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyEmail', __('Company Emails'))->description(__('Comma-separated list of email address'));
                $row->addTextField('companyEmail')->required();

            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyCCFamily', __('CC Family?'))->description(__('Should the family be sent a copy of billing emails?'));
                $row->addYesNo('companyCCFamily')->selected('N');

            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyPhone', __('Company Phone'));
                $row->addTextField('companyPhone')->maxLength(20);

            // COMPANY FEE CATEGORIES
            $sqlFees = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
            $resultFees = $pdo->executeQuery(array(), $sqlFees);

            $form->loadAllValuesFrom($values);

            if (!$resultFees || $resultFees->rowCount() == 0) {
                $form->addHiddenValue('companyAll', 'Y');
            } else {
                $checked = (empty($values['companyAll']) || $values['companyAll'] == 'Y') ? 'Y' : 'N';
                $row = $form->addRow()->addClass('paymentCompany');
                    $row->addLabel('companyAll', __('Company All?'))->description(__('Should all items be billed to the specified company, or just some?'));
                    $row->addRadio('companyAll')->fromArray(array('Y' => __('All'), 'N' => __('Selected')))->checked($checked)->inline();

                $form->toggleVisibilityByClass('paymentCompanyCategories')->onRadio('companyAll')->when('N');

                $row = $form->addRow()->addClass('paymentCompanyCategories');
                $row->addLabel('gibbonFinanceFeeCategoryIDList[]', __('Company Fee Categories'))
                    ->description(__('If the specified company is not paying all fees, which categories are they paying?'));
                $row->addCheckbox('gibbonFinanceFeeCategoryIDList[]')
                    ->fromResults($resultFees)
                    ->fromArray(array('0001' => __('Other')))
                    ->loadFromCSV($values);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();


            echo $form->getOutput();
        }
    }
}
