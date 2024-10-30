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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_contract_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
    $search = $_GET['search'] ?? '';
    $editID = $_GET['editID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Staff'), 'staff_manage.php')
        ->add(__('Edit Staff'), 'staff_manage_edit.php', ['gibbonStaffID' => $gibbonStaffID])
        ->add(__('Add Contract'));

    $editLink = '';
    if (!empty($editID)) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Staff/staff_manage_edit_contract_edit.php&gibbonStaffContractID='.$editID.'&search='.$search.'&gibbonStaffID='.$gibbonStaffID;
    }
    $page->return->setEditLink($editLink);

    //Check if gibbonStaffID specified
    if ($gibbonStaffID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data = array('gibbonStaffID' => $gibbonStaffID);
        $sql = 'SELECT * FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            $values = $result->fetch();

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/staff_manage_edit_contract_addProcess.php?gibbonStaffID=$gibbonStaffID&search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            
            if ($search != '') {
                $params = [
                    "search" => $search,
                    "gibbonStaffID" => $gibbonStaffID
                ];
                $form->addHeaderAction('back', __('Back'))
                    ->setURL('/modules/Staff/staff_manage_edit.php')
                    ->addParams($params);
            }

            $row = $form->addRow();
                $row->addLabel('person', __('Person'));
                $row->addTextField('person')->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student'))->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('title', __('Title'))->description(__('A name to identify this contract.'));
                $row->addTextField('title')->maxlength(100)->required();

            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addSelect('status')->fromArray(array('Pending' => __('Pending'), 'Active' => __('Active'), 'Expired' => __('Expired')))->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'));
                $row->addDate('dateStart')->required();

            $row = $form->addRow();
                $row->addLabel('dateEnd', __('End Date'));
                $row->addDate('dateEnd');

            $settingGateway = $container->get(SettingGateway::class);

            $scalePositions = $settingGateway->getSettingByScope('Staff', 'salaryScalePositions');
            $scalePositions = ($scalePositions != '' ? explode(',', $scalePositions) : '');
            $row = $form->addRow();
                $row->addLabel('salaryScale', __('Salary Scale'));
                $row->addSelect('salaryScale')->fromArray($scalePositions)->placeholder();

            $periods = array(
                "Week" => __('Week'),
                "Month" => __('Month'),
                "Year" => __('Year'),
                "Contract" => __('Contract')
            );
            $row = $form->addRow();
                $row->addLabel('salaryAmount', __('Salary'));
                    $col = $row->addColumn('salaryAmount')->addClass('right inline');
                    $col->addCurrency('salaryAmount')->setClass('shortWidth');
                    $col->addSelect('salaryPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $responsibilityPosts = $settingGateway->getSettingByScope('Staff', 'responsibilityPosts');
            $responsibilityPosts = ($responsibilityPosts != '' ? explode(',', $responsibilityPosts) : '');
            if (is_array($responsibilityPosts)) {
                $row = $form->addRow();
                    $row->addLabel('responsibility', __('Responsibility Level'));
                    $row->addSelect('responsibility')->fromArray($responsibilityPosts)->placeholder();
            }

            $row = $form->addRow();
                $row->addLabel('responsibilityAmount', __('Responsibility'));
                    $col = $row->addColumn('responsibilityAmount')->addClass('right inline');
                    $col->addCurrency('responsibilityAmount')->setClass('shortWidth');
                    $col->addSelect('responsibilityPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $row = $form->addRow();
                $row->addLabel('housingAmount', __('Housing'));
                    $col = $row->addColumn('housingAmount')->addClass('right inline');
                    $col->addCurrency('housingAmount')->setClass('shortWidth');
                    $col->addSelect('housingPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $row = $form->addRow();
                $row->addLabel('travelAmount', __('Travel'));
                    $col = $row->addColumn('travelAmount')->addClass('right inline');
                    $col->addCurrency('travelAmount')->setClass('shortWidth');
                    $col->addSelect('travelPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $row = $form->addRow();
                $row->addLabel('retirementAmount', __('Retirement'));
                    $col = $row->addColumn('retirementAmount')->addClass('right inline');
                    $col->addCurrency('retirementAmount')->setClass('shortWidth');
                    $col->addSelect('retirementPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $row = $form->addRow();
                $row->addLabel('bonusAmount', __('Bonus/Gratuity'));
                    $col = $row->addColumn('bonusAmount')->addClass('right inline');
                    $col->addCurrency('bonusAmount')->setClass('shortWidth');
                    $col->addSelect('bonusPeriod')->fromArray($periods)->setClass('shortWidth')->placeholder();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('education', __('Education Benefits'));
                $column->addTextArea('education')->setRows(5)->setClass('fullWidth');

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('notes', __('Notes'));
                $column->addTextArea('notes')->setRows(5)->setClass('fullWidth');

            $fileUploader = new Gibbon\FileUploader($pdo, $session);
            $row = $form->addRow();
                $row->addLabel('file1', __('Contract File'));
                $row->addFileUpload('file1')->accepts($fileUploader->getFileExtensions('Document'));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
