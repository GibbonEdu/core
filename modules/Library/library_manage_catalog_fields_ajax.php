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

use Gibbon\Forms\FormFactory;

//Gibbon system-wide include
include '../../gibbon.php';

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/Library/moduleFunctions.php';

//Setup variables
$gibbonLibraryTypeID = isset($_POST['gibbonLibraryTypeID'])? $_POST['gibbonLibraryTypeID'] : '';
$gibbonLibraryItemID = isset($_POST['gibbonLibraryItemID'])? $_POST['gibbonLibraryItemID'] : '';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
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
            $fieldsValues = ($result->rowCount() == 1)? unserialize($result->fetchColumn(0)) : array();
        }

        // Transform the library field types to CustomField compatable types
        $fields = array_map(function($item){
            switch($item['type']) {
                case 'Text':        $item['type'] = 'varchar'; break;
                case 'Textarea':    $item['type'] = 'text'; break;
                default:            $item['type'] = strtolower($item['type']); break;
            }
            return $item;
        }, unserialize($values['fields']));

        foreach ($fields as $field) {
            $fieldName = 'field'.preg_replace('/ |\(|\)/', '', $field['name']);
            $fieldValue = isset($fieldsValues[$field['name']])? $fieldsValues[$field['name']] : '';

            $row = $table->addRow();
                $row->addLabel($fieldName, $field['name'])->description($field['description']);
                $row->addCustomField($fieldName, $field)->setValue($fieldValue);
        }

        // Add Google Books data grabber
        if ($values['name'] == 'Print Publication') {
            echo '<script type="text/javascript">';
                echo 'document.onkeypress = stopRKey;';
                echo '$(".gbooks").loadGoogleBookData({
                    "notFound": "'.__('The specified record cannot be found.').'",
                    "dataRequired": "'.__('Please enter an ISBN13 or ISBN10 value before trying to get data from Google Books.').'",
                });';
            echo '</script>';
            echo '<div style="text-align: right">';
            echo '<a class="gbooks" onclick="return false" href="#">'.__('Get Book Data From Google').'</a>';
            echo '</div>';
        }
    }

    echo $table->getOutput();
    echo '<script type="text/javascript">'.$table->getValidationOutput().'</script>';
}
