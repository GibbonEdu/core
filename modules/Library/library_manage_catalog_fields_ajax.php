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

use Gibbon\Forms\FormFactory;

//Gibbon system-wide include
include '../../gibbon.php';

//Module includes
include $session->get('absolutePath').'/modules/Library/moduleFunctions.php';

//Setup variables
$gibbonLibraryTypeID = $_POST['gibbonLibraryTypeID'] ?? '';
$gibbonLibraryItemID = $_POST['gibbonLibraryItemID'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_add.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $data = array('gibbonLibraryTypeID' => $gibbonLibraryTypeID);
    $sql = "SELECT * FROM gibbonLibraryType
            WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name";
    $result = $pdo->executeQuery($data, $sql);

    $factory = FormFactory::create();
    $table = $factory->createTable('detailsTable')->setClass('fullWidth');

    if ($result->rowCount() != 1) {
        $table->addRow()->addAlert(__('The specified record cannot be found.'), 'error');
    } else {
        $values = $result->fetch();
        $fieldsValues = array();

        // Load any data for an existing library item
        if (!empty($gibbonLibraryItemID)) {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = "SELECT fields FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID";
            $result = $pdo->executeQuery($data, $sql);
            $fieldsValues = ($result->rowCount() == 1)? json_decode($result->fetchColumn(0), true) : array();
        }

        // Transform the library field types to CustomField compatable types
        $fields = array_map(function($item){
            switch($item['type']) {
                case 'Text':        $item['type'] = 'varchar'; break;
                case 'Textarea':    $item['type'] = 'text'; break;
                default:            $item['type'] = strtolower($item['type']); break;
            }
            return $item;
        }, json_decode($values['fields'], true));

        foreach ($fields as $field) {
            $fieldName = 'field'.preg_replace('/ |\(|\)/', '', $field['name']);
            $fieldValue = isset($fieldsValues[$field['name']])? $fieldsValues[$field['name']] : '';

            $row = $table->addRow()->addClass('flex flex-col sm:flex-row justify-between content-center p-0');
                $row->addLabel($fieldName, __($field['name']))->description(__($field['description']))->addClass('flex-grow sm:mb-0 border-transparent border-t-0 sm:border-gray-400 sm:max-w-full');
                $row->addCustomField($fieldName, $field)->setValue($fieldValue)->addClass('w-full max-w-full sm:max-w-xs flex justify-end items-center border-0 sm:border-b');
        }

        // Add Google Books data grabber
        if ($values['name'] == 'Print Publication') {
            echo '<div style="text-align: right">';
            echo '<a class="gbooks" onclick="return false" href="#">'.__('Get Book Data From Google').'</a>';
            echo '</div>';
        }
    }

    echo $table->getOutput();
    echo '<script type="text/javascript">'.$table->getValidationOutput().'</script>';
}
