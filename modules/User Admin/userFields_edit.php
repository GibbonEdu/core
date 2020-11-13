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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Custom Fields'), 'userFields.php')
        ->add(__('Edit Custom Field'));  

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonPersonFieldID = $_GET['gibbonPersonFieldID'];
    if ($gibbonPersonFieldID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonPersonFieldID' => $gibbonPersonFieldID);
            $sql = 'SELECT * FROM gibbonPersonField WHERE gibbonPersonFieldID=:gibbonPersonFieldID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/userFields_editProcess.php?gibbonPersonFieldID='.$gibbonPersonFieldID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->maxLength(50)->required();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextField('description')->maxLength(255)->required();

            $types = array(
                'varchar' => __('Short Text (max 255 characters)'),
                'text'    => __('Long Text'),
                'date'    => __('Date'),
                'url'     => __('Link'),
                'select'  => __('Dropdown')
            );
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromArray($types)->required()->placeholder();

            $form->toggleVisibilityByClass('optionsRow')->onSelect('type')->when(array('varchar', 'text', 'select'));

            $row = $form->addRow()->addClass('optionsRow');
                $row->addLabel('options', __('Options'))
                    ->description(__('Short Text: number of characters, up to 255.'))
                    ->description(__('Long Text: number of rows for field.'))
                    ->description(__('Dropdown: comma separated list of options.'));
                $row->addTextArea('options')->setRows(3)->required();

            $row = $form->addRow();
                $row->addLabel('required', __('Required'))->description(__('Is this field compulsory?'));
                $row->addYesNo('required')->required();

            $activePersonOptions = array(
                'activePersonStudent' => __('Student'),
                'activePersonStaff'   => __('Staff'),
                'activePersonParent'  => __('Parent'),
                'activePersonOther'   => __('Other'),
            );
            $checked = array_intersect_key($values, $activePersonOptions);
            $checked = array_filter($checked);

            $row = $form->addRow();
                $row->addLabel('roleCategories', __('Role Categories'));
                $row->addCheckbox('roleCategories')->fromArray($activePersonOptions)->checked(array_keys($checked));

            $row = $form->addRow();
                $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
                $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

            $row = $form->addRow();
                $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
                $row->addSelect('activeApplicationForm')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

            $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
            if ($enablePublicRegistration == 'Y') {
                $row = $form->addRow();
                    $row->addLabel('activePublicRegistration', __('Include In Public Registration Form?'));
                    $row->addSelect('activePublicRegistration')->fromArray(array('1' => __('Yes'), '0' => __('No')))->selected('0')->required();
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
?>
