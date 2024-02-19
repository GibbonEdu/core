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

if (isActionAccessible($guid, $connection2, '/modules/Finance/fees_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $urlParams = compact('gibbonSchoolYearID');

    $page->breadcrumbs
        ->add(__('Manage Fees'),'fees_manage.php', $urlParams)
        ->add(__('Edit Fee'));

    $gibbonFinanceFeeID = $_GET['gibbonFinanceFeeID'] ?? '';
    $search = $_GET['search'] ?? '';
    if ($gibbonFinanceFeeID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceFeeID' => $gibbonFinanceFeeID);
            $sql = 'SELECT gibbonFinanceFee.*, gibbonSchoolYear.name AS schoolYear
                FROM gibbonFinanceFee
                    JOIN gibbonSchoolYear ON (gibbonFinanceFee.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonFinanceFee.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonFinanceFeeID=:gibbonFinanceFeeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                 $params = [
                    "search" => $search,
                    "gibbonSchoolYearID" => $gibbonSchoolYearID
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'fees_manage.php')->withQueryParams($params));
            }

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/fees_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonFinanceFeeID', $gibbonFinanceFeeID);

            $row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
                $row->addTextField('schoolYear')->maxLength(20)->required()->readonly()->setValue($values['schoolYear']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->maxLength(100)->required();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->maxLength(6)->required();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextArea('description');

            $data = array();
            $sql = "SELECT gibbonFinanceFeeCategoryID AS value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('gibbonFinanceFeeCategoryID', __('Category'));
                $row->addSelect('gibbonFinanceFeeCategoryID')->fromQuery($pdo, $sql, $data)->fromArray(array('1' => __('Other')))->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('fee', __('Fee'))
                    ->description(__('Numeric value of the fee.'));
                $row->addCurrency('fee')->required();

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
?>
