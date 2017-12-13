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

use Gibbon\Forms\Prefab\DeleteForm;

$orphaned = '';
if (isset($_GET['orphaned'])) {
    if ($_GET['orphaned'] == 'true') {
        $orphaned = 'true';
    }
}

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_uninstall.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/module_manage.php'>".__($guid, 'Manage Modules')."</a> > </div><div class='trailEnd'>".__($guid, 'Uninstall Module').'</div>';
    echo '</div>';

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'fail0') {
            $deleteReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
        } elseif ($deleteReturn == 'fail1') {
            $deleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
        } elseif ($deleteReturn == 'fail2') {
            $deleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
        } elseif ($deleteReturn == 'fail3') {
            $deleteReturnMessage = __($guid, 'Uninstall encountered a partial fail: the module may or may not still work.');
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonModuleID = $_GET['gibbonModuleID'];
    if ($gibbonModuleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonModuleID' => $gibbonModuleID);
            $sql = 'SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch(); 
            
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/module_manage_uninstallProcess.php?gibbonModuleID=$gibbonModuleID&orphaned=$orphaned", false, false);

            $manifestFile = $_SESSION[$guid]['absolutePath'].'/modules/'.$values['name'].'/manifest.php';
            if (file_exists($manifestFile)) {
                include $manifestFile;
            } else {
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
                        $group[$type.'-'.$name] = '<b>'.$type.'</b>: '.$name;
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
