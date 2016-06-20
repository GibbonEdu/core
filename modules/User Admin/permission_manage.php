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

    try {
        $dataModules = array();
        $sqlModules = 'SELECT * FROM gibbonModule ORDER BY name';
        $resultModules = $connection2->prepare($sqlModules);
        $resultModules->execute($dataModules);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $dataRoles = array();
        $sqlRoles = 'SELECT * FROM gibbonRole ORDER BY type, nameShort';
        $resultRoles = $connection2->prepare($sqlRoles);
        $resultRoles->execute($dataRoles);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $dataPermissions = array();
        $sqlPermissions = 'SELECT * FROM gibbonPermission';
        $resultPermissions = $connection2->prepare($sqlPermissions);
        $resultPermissions->execute($dataPermissions);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultRoles->rowCount() < 1 or $resultModules->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    } else {
        //Fill role array
        $roleArray = array();
        $count = 0;
        while ($rowRoles = $resultRoles->fetch()) {
            $roleArray["$count"][0] = $rowRoles['gibbonRoleID'];
            $roleArray["$count"][1] = $rowRoles['nameShort'];
            $roleArray["$count"][2] = $rowRoles['category'];
            $roleArray["$count"][3] = $rowRoles['name'];
            ++$count;
        }

        //Fill permission array
        $permissionsArray = array();
        $count = 0;
        while ($rowPermissions = $resultPermissions->fetch()) {
            $permissionsArray["$count"][0] = $rowPermissions['gibbonRoleID'];
            $permissionsArray["$count"][1] = $rowPermissions['gibbonActionID'];
            ++$count;
        }

        $totalCount = 0;
        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/permission_manageProcess.php'>";
        echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
        echo "<table class='mini' cellspacing='0' style='width: 100%'>";
        while ($rowModules = $resultModules->fetch()) {
            echo "<tr class='break'>";
            echo '<td colspan='.($resultRoles->rowCount() + 1).'>';
            echo '<h3>'.__($guid, $rowModules['name']).'</h3>';
            echo '</td>';
            echo '</tr>';

            try {
                $dataActions = array('gibbonModuleID' => $rowModules['gibbonModuleID']);
                $sqlActions = 'SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID ORDER BY name';
                $resultActions = $connection2->prepare($sqlActions);
                $resultActions->execute($dataActions);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultActions->rowCount() > 0) {
                echo "<tr class='head'>";
                echo "<th class='width: 60px!important'>Action</td>";
                for ($i = 0;$i < count($roleArray);++$i) {
                    echo "<th style='padding: 0!important'><span title='".htmlPrep(__($guid, $roleArray[$i][3]))."'>".__($guid, $roleArray[$i][1]).'</span></th>';
                }
                echo '</tr>';
                while ($rowActions = $resultActions->fetch()) {
                    echo '<tr>';
                    echo "<td><span title='".htmlPrep(__($guid, $rowActions['description']))."'>".__($guid, $rowActions['name']).'</span></td>';
                    for ($i = 0;$i < $resultRoles->rowCount();++$i) {
                        echo '<td>';
                        $checked = '';
                        for ($x = 0;$x < count($permissionsArray);++$x) {
                            if ($permissionsArray[$x][0] == $roleArray[$i][0] and $permissionsArray[$x][1] == $rowActions['gibbonActionID']) {
                                $checked = 'checked';
                            }
                        }

                        $readonly = '';
                        if ($roleArray[$i][2] == 'Staff') {
                            if ($rowActions['categoryPermissionStaff'] == 'N') {
                                $readonly = 'disabled';
                                $checked = '';
                            }
                        }
                        if ($roleArray[$i][2] == 'Student') {
                            if ($rowActions['categoryPermissionStudent'] == 'N') {
                                $readonly = 'disabled';
                                $checked = '';
                            }
                        }
                        if ($roleArray[$i][2] == 'Parent') {
                            if ($rowActions['categoryPermissionParent'] == 'N') {
                                $readonly = 'disabled';
                                $checked = '';
                            }
                        }
                        if ($roleArray[$i][2] == 'Other') {
                            if ($rowActions['categoryPermissionOther'] == 'N') {
                                $readonly = 'disabled';
                                $checked = '';
                            }
                        }

                        echo "<input $readonly $checked name='".$rowActions['gibbonActionID'].'-'.$roleArray[$i][0]."' type='checkbox'/>";
                        echo "<input type='hidden' name='$totalCount' value='".$rowActions['gibbonActionID'].'-'.$roleArray[$i][0]."'/>";
                        ++$totalCount;
                        echo '</td>';
                    }
                    echo '</tr>';
                }
            }
        }
        $max_input_vars = ini_get('max_input_vars');
        $total_vars = (($totalCount * 2) + 10);
        $total_vars_rounded = (ceil($total_vars / 1000) * 1000) + 1000;
        if ($total_vars > $max_input_vars) {
            echo '<tr>';
            echo '<td colspan='.($resultRoles->rowCount() + 1).'>';
            echo "<div class='error'>";
            echo 'php.ini max_input_vars='.$max_input_vars.'<br />';
            echo __($guid, 'Number of inputs on this page').'='.$total_vars.'<br/>';
            echo sprintf(__($guid, 'This form is very large and data will be truncated unless you edit php.ini. Add the line <i>max_input_vars=%1$s</i> to your php.ini file on your server.'), $total_vars_rounded);
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        } else {
            echo '<tr>';
            echo "<td style='padding-top: 20px' class='right' colspan=".(count($roleArray) + 1).'>';
            echo "<input type='submit' value='Submit'>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</form>';
    }
}
