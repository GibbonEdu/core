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
use Gibbon\Domain\System\ModuleGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_update.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Modules'), 'module_manage.php')
        ->add(__('Update Module'));

    $return = $_GET['return'] ?? '';
    
    $returns = array();
    $returns['warning1'] = __('Some aspects of your request failed, but others were successful. The elements that failed are shown below:');
    
    if (!empty($return)) {
        returnProcess($guid, $return, null, $returns);
    }
    
    if (!empty($gibbon->session->get('moduleUpdateError'))) {
        $page->addError(__('The following SQL statements caused errors:').' '.$gibbon->session->get('moduleUpdateError'));
        }
    $gibbon->session->set('moduleUpdateError', '');

    // Check if module specified
    $gibbonModuleID = $_GET['gibbonModuleID'] ?? '';
    
    if (empty($gibbonModuleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $moduleGateway = $container->get(ModuleGateway::class);
        $module = $moduleGateway->getByID($gibbonModuleID);
        
        if (empty($module)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            // Let's go!
            $versionDB = $module['version'];

            if (file_exists($gibbon->session->get('absolutePath').'/modules/'.$module['name'].'/version.php')) {
                include $gibbon->session->get('absolutePath').'/modules/'.$module['name'].'/version.php';
            }
            @$versionCode = $moduleVersion;

            if (version_compare($versionDB, $versionCode, '>') or empty($versionCode)) {
                // Error
                $page->addError(__('An error has occurred determining the version of the system you are using.'));
            } elseif (version_compare($versionDB, $versionCode, '=')) {
                    // Instructions on how to update
                    $page->addMessage(__('You seem to be all up to date, good work!'));
                echo '<h3>';
                echo __('Update Instructions');
                echo '</h3>';
                echo '<ol>';
                    echo '<li>'.sprintf(__('You are currently using %1$s v%2$s.'),  htmlPrep($module['name']), $versionCode).'</i></li>';
                echo '<li>'.sprintf(__('Check %1$s for a newer version of this module.'), "<a target='_blank' href='https://gibbonedu.org/extend'>gibbonedu.org</a>").'</li>';
                echo '<li>'.__('Download the latest version, and unzip it on your computer.').'</li>';
                echo '<li>'.__('Use an FTP client to upload the new files to your server\'s modules folder.').'</li>';
                echo '<li>'.__('Reload this page and follow the instructions to update your database to the latest version.').'</li>';
                echo '</ol>';
            } elseif (version_compare($versionDB, $versionCode, '<')) {
                // Time to update
                $page->addMessage(sprintf(__('This page allows you to semi-automatically update the %1$s module to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.'), htmlPrep($module['name'])));
                $form = Form::create('action', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/module_manage_updateProcess.php?&gibbonModuleID='.$module['gibbonModuleID']);
                
                $form->setTitle(__('Database Update'))
                    ->setDescription(sprintf(__('It seems that you have updated your %1$s module code to a new version, and are ready to update your database from v%2$s to v%3$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), htmlPrep($module['name']), $versionDB, $versionCode).'</b>');
                
                $form->addHiddenValue('versionDB', $versionDB);
                $form->addHiddenValue('versionCode', $versionCode);
                $form->addHiddenValue('address', $gibbon->session->get('address'));

                $form->addRow()->addSubmit();
                echo $form->getOutput(); 
            }
        }
    }
}
