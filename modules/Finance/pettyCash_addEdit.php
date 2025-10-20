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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\PettyCashGateway;

if (isActionAccessible($guid, $connection2, '/modules/Finance/pettyCash_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $params = [
        'mode'                     => $_REQUEST['mode'] ?? '',
        'gibbonFinancePettyCashID' => $_REQUEST['gibbonFinancePettyCashID'] ?? '',
        'gibbonSchoolYearID'       => $_REQUEST['gibbonSchoolYearID'] ?? '',
    ];

    $page->breadcrumbs
        ->add(__('Petty Cash'), 'pettyCash.php')
        ->add($params['mode'] == 'add' ? __('Add Transaction') : __('Edit Transaction'));
     
    $page->return->addReturns([
    ]);

    if ($params['mode'] == 'add' && isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Finance/pettyCash_addEdit.php&mode=edit&gibbonFinancePettyCashID='.$_GET['editID'].'&gibbonSchoolYearID='.$params['gibbonSchoolYearID']);
    }

    $pettyCashGateway = $container->get(PettyCashGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $pettyCashReasons = $settingGateway->getSettingByScope('Finance', 'pettyCashReasons');
    $pettyCashDefaultAction = $settingGateway->getSettingByScope('Finance', 'pettyCashDefaultAction');

    $values = $pettyCashGateway->getByID($params['gibbonFinancePettyCashID']);

    // FORM
    $form = Form::create('pettyCash', $session->get('absoluteURL').'/modules/'.$session->get('module').'/pettyCash_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValues($params);
    
    if ($params['mode'] == 'add') {
        $form->removeMeta()->addMeta()->addDefaultContent('addProcess');
    } elseif ($params['mode'] == 'edit') {
        $form->addHiddenValue('status', $values['status']);
    }
    
    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'));
        $row->addSelectUsers('gibbonPersonID', $params['gibbonSchoolYearID'], ['includeStudents' => true])
            ->required()
            ->placeholder()
            ->selected($params['mode'] == 'edit' ? $values['gibbonPersonID'] : '')
            ->readOnly($params['mode'] == 'edit');

    $row = $form->addRow();
        $row->addLabel('amount', __('Amount'));
        $row->addCurrency('amount')
            ->required();

    $row = $form->addRow();
        $row->addLabel('reason', __('Reason'));
        $row->addSelect('reason')
            ->fromString($pettyCashReasons)
            ->required()
            ->placeholder();

    $form->toggleVisibilityByClass('otherReason')->onSelect('reason')->when('Other');
    $row = $form->addRow()->addClass('otherReason');
        $row->addLabel('notes', __('Notes'));
        $row->addTextArea('notes')->setRows(2);
    

    $actions = [
        'None'   => __('None'),
        'Repay'  => __('Needs Repaid'),
        'Refund' => __('Needs Refunded'),
    ];

    if ($params['mode'] == 'add' || $values['status'] == 'Pending') {
        $row = $form->addRow();
            $row->addLabel('actionRequired', __('Action Required'))->description(__('Does this amount need to be repaid by the person, or refunded to them by the school?'));
            $row->addSelect('actionRequired')
                ->required()
                ->placeholder()
                ->fromArray($actions)
                ->selected($pettyCashDefaultAction)
                ->readOnly($params['mode'] == 'edit');
    } else {
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addTextField('status')->readOnly();
    }

    $row = $form->addRow();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
