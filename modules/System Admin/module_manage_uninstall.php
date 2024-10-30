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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Services\Format;

$orphaned = $_GET['orphaned'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_uninstall.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Modules'), 'module_manage.php')
        ->add(__('Uninstall Module'));

    $deleteReturn = $_GET['deleteReturn'] ?? '';

    $deleteReturnMessage = '';
    
    if (!empty($deleteReturn)) {
        
        switch ($deleteReturn){
            case 'fail0':
                $deleteReturnMessage = __('Your request failed because you do not have access to this action.');
                break;
            case 'fail1':
                $deleteReturnMessage = __('Your request failed because your inputs were invalid.');
                break;
            case 'fail2':
                $deleteReturnMessage = __('Your request failed because your inputs were invalid.');
                break;
            case 'fail3':
                $deleteReturnMessage = __('Uninstall encountered a partial fail: the module may or may not still work.');
                break;
        }
        $page->addError($deleteReturnMessage);
    }

    // Check if module specified
    $gibbonModuleID = $_GET['gibbonModuleID'] ?? '';
    
    if (empty($gibbonModuleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $moduleGateway = $container->get(ModuleGateway::class);
        $module = $moduleGateway->getByID($gibbonModuleID);
        
        if (empty($module)) {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            // Let's go!
            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/module_manage_uninstallProcess.php?gibbonModuleID=$gibbonModuleID&orphaned=$orphaned", false, false);
            
            $manifestFile = $session->get('absolutePath').'/modules/'.$module['name'].'/manifest.php';
            if (file_exists($manifestFile)) {
                include $manifestFile;
            } else if (empty($orphaned)) {
                $form->addRow()->addAlert(__('An error has occurred.').' '.__('Module error due to incorrect manifest file or folder name.'), 'error');
            }

            if (!empty($moduleTables)) {
                $moduleTables = array_map('trim', $moduleTables);
                $moduleTables = array_reduce($moduleTables, function($group, $moduleTable) {
                    $tokens = preg_split('/ +/', $moduleTable);

                    if ($tokens === false || empty($tokens[0]) || empty($tokens[1]) || empty($tokens[2])) return $group;
                    if (strtoupper($tokens[0]) == 'CREATE' && (strtoupper($tokens[1]) == 'TABLE' || strtoupper($tokens[1]) == 'VIEW')) {
                        $type = ucfirst(strtolower($tokens[1]));
                        $name = str_replace('`', '', $tokens[2]);
                        $group[$type.'-'.$name] = Format::bold(__($type)).': '.$name;
                    }
        
                    return $group;
                }, array());

                $row = $form->addRow();
                    $row->addLabel('remove', __('Remove Data'))->description(__('Would you like to remove the following tables and views from your database?'));
                    $row->addCheckbox('remove')->fromArray($moduleTables)->checkAll()->addCheckAllNone();
            }

            $form->addRow()->addConfirmSubmit();
            
            echo $form->getOutput();
        }
    }
}
