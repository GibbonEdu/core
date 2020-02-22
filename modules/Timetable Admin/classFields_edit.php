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
use Gibbon\Domain\Timetable\ClassFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/classFields_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Custom Fields'), 'classFields.php')
        ->add(__('Edit Custom Field'));  

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonClassFieldID = $_GET['gibbonClassFieldID'] ?? '';
    if ($gibbonClassFieldID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $classFieldGateway = $container->get(ClassFieldGateway::class);
        $classField = $classFieldGateway->getById($gibbonClassFieldID);

        if (empty($classField)) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $classField;

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/classFields_editProcess.php?gibbonClassFieldID='.$gibbonClassFieldID);

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

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
?>
