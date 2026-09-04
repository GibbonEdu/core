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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Finance\InvoiceeGateway;
use Gibbon\Domain\DataUpdater\FinanceUpdateGateway;
use Gibbon\Domain\Finance\FinanceFeeCategoryGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $page->breadcrumbs->add(__('Update Finance Data'));

        if ($highestAction == 'Update Finance Data_any') {
            echo '<p>';
            echo __('This page allows a user to request selected finance data updates for any user. If a user does not appear in the list, please visit the Manage Invoicees page to create any missing students.');
            echo '</p>';
        } else {
            echo '<p>';
            echo sprintf(__('This page allows any adult with data access permission to request selected finance data updates for any children in their family. If any of your children do not appear in this list, please contact %1$s.'), "<a href='mailto:".$session->get('organisationAdminstratorEmail')."'>".$session->get('organisationAdministratorName').'</a>');
            echo '</p>';
        }

        $customResponces = [];
        $error3 = __('Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
            $error3 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
        }
        $customResponces['error3'] = $error3;

        $success0 = __('Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
            $success0 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
        }
        $customResponces['success0'] = $success0;

        $page->return->addReturns($customResponces);

        echo '<h2>';
        echo __('Choose User');
        echo '</h2>';

		$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'] ?? null;
        $invoiceeGateway = $container->get(InvoiceeGateway::class);

        $form = Form::create('selectInvoicee', $session->get('absoluteURL').'/index.php', 'get');
        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/data_finance.php');

        if ($highestAction == 'Update Finance Data_any') {
            $result = $invoiceeGateway->selectInvoiceesDetails();
        } else {
            $result = $invoiceeGateway->selectInvoiceeByAdultID($session->get('gibbonPersonID'));
		}
		$resultSet = ($result && $result->rowCount() > 0)? $result->fetchAll() : [];

		$invoicees = array_reduce($resultSet, function($carry, $person) use ($highestAction) {
			$id = $person['gibbonFinanceInvoiceeID'];
			$carry[$id] = Format::name('', htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Student', true);
			if ($highestAction == 'Update Finance Data_any') {
				$carry[$id] .= ' ('.$person['username'].')';
			}
			return $carry;
		}, []);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeID', __('Invoicee'))->description(__('Individual for whom invoices are generated.'));
            $row->addSelect('gibbonFinanceInvoiceeID')
                ->fromArray($invoicees)
                ->required()
                ->selected($gibbonFinanceInvoiceeID)
                ->placeholder();

        $row = $form->addRow();
            $row->addSubmit();

		echo $form->getOutput();

        if ($gibbonFinanceInvoiceeID != '') {
            echo '<h2>';
            echo __('Update Data');
            echo '</h2>';

            // Check access to person
            $checkCount = 0;
            if ($highestAction == 'Update Finance Data_any') {                    
                $resultSelect = $invoiceeGateway->selectInvoiceeByID($gibbonFinanceInvoiceeID);
                $checkCount = $resultSelect->rowCount();
            } else {
                $resultCheck = $container->get(FamilyGateway::class)->selectFamilyIDAndNameByAdultID($session->get('gibbonPersonID'));

                while ($rowCheck = $resultCheck->fetch()) {
                    $resultCheck2 = $invoiceeGateway->selectInvoiceeByFamilyID($rowCheck['gibbonFamilyID']);

                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonFinanceInvoiceeID == $rowCheck2['gibbonFinanceInvoiceeID']) {
                            ++$checkCount;
                        }
                    }
                }
            }

            if ($checkCount < 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                // Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;

                $result = $container->get(FinanceUpdateGateway::class)->selectBy(['gibbonFinanceInvoiceeID'=> $gibbonFinanceInvoiceeID, 'gibbonPersonIDUpdater' => $session->get('gibbonPersonID'), 'status' => 'Pending']);

                if ($result->rowCount() > 1) {
                    $page->addError(__('Your request failed due to a database error.'));
                } elseif ($result->rowCount() == 1) {
                    $existing = true;
                    echo "<div class='warning'>";
                    echo __('You have already submitted a form, which is awaiting processing by an administrator. If you wish to make changes, please edit the data below, but remember your data will not appear in the system until it has been processed.');
                    echo '</div>';
                    $proceed = true;
                    $values = $result->fetch();
                } else {
                    // Get user's data
                    $result = $container->get(InvoiceeGateway::class)->getByID($gibbonFinanceInvoiceeID);

                    if (empty($result)) {
                        $page->addError(__('The specified record cannot be found.'));
                    } else {
                        $proceed = true;
                        $values = $result;
                    }
                }

                if ($proceed == true) {
                    // Let's go!
					$required = ($highestAction != 'Update Finance Data_any');

					$form = Form::create('updateFinance', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_financeProcess.php?gibbonFinanceInvoiceeID='.$gibbonFinanceInvoiceeID);

                    $form->addHiddenValue('address', $session->get('address'));
					$form->addHiddenValue('existing', isset($values['gibbonFinanceInvoiceeUpdateID']) ? $values['gibbonFinanceInvoiceeUpdateID'] : 'N');

					$form->addRow()->addHeading('Invoice To', __('Invoice To'));

					$form->addRow()->addContent(__('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.'))->wrap('<p>', '</p>');

					$row = $form->addRow();
						$row->addLabel('invoiceTo', __('Send Invoices To'));
						$row->addRadio('invoiceTo')
							->fromArray(['Family' => __('Family'), 'Company' => __('Company')])
							->inline();

					$form->toggleVisibilityByClass('paymentCompany')->onRadio('invoiceTo')->when('Company');

					// COMPANY DETAILS
					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyName', __('Company Name'));
						$row->addTextField('companyName')->setRequired($required)->maxLength(100);

					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyContact', __('Company Contact Person'));
						$row->addTextField('companyContact')->setRequired($required)->maxLength(100);

					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyAddress', __('Company Address'));
						$row->addTextField('companyAddress')->setRequired($required)->maxLength(255);

					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyEmail', __('Company Emails'))->description(__('Comma-separated list of email address'));
						$row->addTextField('companyEmail')->setRequired($required);

					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyCCFamily', __('CC Family?'))->description(__('Should the family be sent a copy of billing emails?'));
						$row->addYesNo('companyCCFamily')->selected('N');

					$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyPhone', __('Company Phone'));
						$row->addTextField('companyPhone')->maxLength(20);

					// COMPANY FEE CATEGORIES
                    $categories = $container->get(FinanceFeeCategoryGateway::class)->selectActiveFeeCategories();

					if (!$categories || $categories->rowCount() == 0) {
						$form->addHiddenValue('companyAll', 'Y');
					} else {
						$row = $form->addRow()->addClass('paymentCompany');
						$row->addLabel('companyAll', __('Company All?'))->description(__('Should all items be billed to the specified company, or just some?'));
						$row->addRadio('companyAll')->fromArray(array('Y' => __('All'), 'N' => __('Selected')))->checked('Y')->inline();

						$form->toggleVisibilityByClass('paymentCompanyCategories')->onRadio('companyAll')->when('N');

						$row = $form->addRow()->addClass('paymentCompanyCategories');
						$row->addLabel('gibbonFinanceFeeCategoryIDList[]', __('Company Fee Categories'))
							->description(__('If the specified company is not paying all fees, which categories are they paying?'));
						$row->addCheckbox('gibbonFinanceFeeCategoryIDList[]')
							->fromResults($categories)
							->fromArray(['0001' => __('Other')])
							->loadFromCSV($values);
					}

					$row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadAllValuesFrom($values);

					echo $form->getOutput();
                }
            }
        }
    }
}
