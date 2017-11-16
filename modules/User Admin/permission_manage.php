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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/permission_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Permissions').'</div>';
    echo '</div>';

    $returns = array();
    $returns['error3'] = sprintf(__($guid, 'Your PHP environment cannot handle all of the fields in this form (the current limit is %1$s). Ask your web host or system administrator to increase the value of the max_input_vars in php.ini.'), ini_get('max_input_vars'));
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    echo '<h2>';
    echo __($guid, 'Filter');
    echo '</h2>';

    $gibbonModuleID = isset($_GET['gibbonModuleID'])? $_GET['gibbonModuleID'] : '';
    $gibbonRoleID = isset($_GET['gibbonRoleID'])? $_GET['gibbonRoleID'] : '';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/permission_manage.php');

    $sql = "SELECT gibbonModuleID as value, name FROM gibbonModule WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonModuleID', __('Module'));
        $row->addSelect('gibbonModuleID')->fromQuery($pdo, $sql)->selected($gibbonModuleID)->placeholder();

    $sql = "SELECT gibbonRoleID as value, name FROM gibbonRole ORDER BY type, nameShort";
    $row = $form->addRow();
        $row->addLabel('gibbonRoleID', __('Role'));
        $row->addSelect('gibbonRoleID')->fromQuery($pdo, $sql)->selected($gibbonRoleID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    try {
        if (!empty($gibbonModuleID)) {
            $dataModules = array('gibbonModuleID' => $gibbonModuleID);
            $sqlModules = "SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID AND active='Y'";
        } else {
            $dataModules = array();
            $sqlModules = "SELECT * FROM gibbonModule WHERE active='Y' ORDER BY name";
        }
        
        $resultModules = $connection2->prepare($sqlModules);
        $resultModules->execute($dataModules);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        if (!empty($gibbonRoleID)) {
            $dataRoles = array('gibbonRoleID' => $gibbonRoleID);
            $sqlRoles = 'SELECT gibbonRoleID, nameShort, category, name FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
        } else {
            $dataRoles = array();
            $sqlRoles = 'SELECT gibbonRoleID, nameShort, category, name FROM gibbonRole ORDER BY type, nameShort';
        }
        $resultRoles = $connection2->prepare($sqlRoles);
        $resultRoles->execute($dataRoles);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $dataPermissions = array();
        $sqlPermissions = 'SELECT gibbonRoleID, gibbonActionID FROM gibbonPermission';
        $resultPermissions = $connection2->prepare($sqlPermissions);
        $resultPermissions->execute($dataPermissions);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultRoles->rowCount() < 1 or $resultModules->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('Your request failed due to a database error.');
        echo '</div>';
    } else {
        //Fill role and permission arrays
        $roleArray = ($resultRoles->rowCount() > 0)? $resultRoles->fetchAll() : array();
        $permissionsArray = ($resultPermissions->rowCount() > 0)? $resultPermissions->fetchAll() : array();
        $totalCount = 0;

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/permission_manageProcess.php');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonModuleID', $gibbonModuleID);
        $form->addHiddenValue('gibbonRoleID', $gibbonRoleID);
        
        // To render the form as multiple tables
        $form->getRenderer()->setWrapper('form', 'div');
        $form->getRenderer()->setWrapper('row', 'div');
        $form->getRenderer()->setWrapper('cell', 'div');

        while ($rowModules = $resultModules->fetch()) {
            $form->addRow()->addHeading($rowModules['name']);
            $table = $form->addRow()->addTable()->setClass('mini rowHighlight columnHighlight fullWidth');

            try {
                $dataActions = array('gibbonModuleID' => $rowModules['gibbonModuleID']);
                $sqlActions = 'SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID ORDER BY name';
                $resultActions = $connection2->prepare($sqlActions);
                $resultActions->execute($dataActions);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultActions->rowCount() > 0) {
                $row = $table->addHeaderRow();
                $row->addContent(__('Action'))->wrap('<div style="width: 350px;">', '</div>');

                // Add headings for each Role
                foreach ($roleArray as $role) {
                    $row->addContent(__($role['nameShort']))->wrap('<span title="'.htmlPrep(__($role['name'])).'">', '</span>');
                }

                while ($rowActions = $resultActions->fetch()) {
                    $row = $table->addRow();

                    // Add names and hover-over descriptions for each Action
                    if ($rowModules['type'] == 'Core') {
                        $row->addContent($rowActions['name'])->wrap('<span title="'.htmlPrep(__($rowActions['description'])).'">', '</span>');
                    } else {
                        $row->addContent($rowActions['name'], $rowModules['name'])->wrap('<span title="'.htmlPrep(__($rowActions['description'], $rowModules['name'])).'">', '</span>');
                    }

                    foreach ($roleArray as $role) {
                        $checked = false;

                        // Check to see if the current action is turned on
                        foreach ($permissionsArray as $permission) {
                            if ($permission['gibbonRoleID'] == $role['gibbonRoleID'] && $permission['gibbonActionID'] == $rowActions['gibbonActionID']) {
                                $checked = true;
                            }
                        }

                        $readonly = ($rowActions['categoryPermission'.$role['category']] == 'N');
                        $checked = !$readonly && $checked;

                        $name = 'permission['.$rowActions['gibbonActionID'].']['.$role['gibbonRoleID'].']';
                        $row->addCheckbox($name)->setDisabled($readonly)->checked($checked)->setClass('');

                        ++$totalCount;
                    }
                }
            }
        }

        $form->addHiddenValue('totalCount', $totalCount);

        $max_input_vars = ini_get('max_input_vars');
        $total_vars = $totalCount + 10;
        $total_vars_rounded = (ceil($total_vars / 1000) * 1000) + 1000;

        if ($total_vars > $max_input_vars) {
            $row = $form->addRow();
            $row->addAlert('php.ini max_input_vars='.$max_input_vars.'<br />')
                ->append(__('Number of inputs on this page').'='.$total_vars.'<br/>')
                ->append(sprintf(__('This form is very large and data will be truncated unless you edit php.ini. Add the line <i>max_input_vars=%1$s</i> to your php.ini file on your server.'), $total_vars_rounded));
        } else {
            $row = $form->addRow();
            $row->addSubmit();
        }

        echo $form->getOutput();
    }
}
