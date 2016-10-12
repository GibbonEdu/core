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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Modules').'</div>';
    echo '</div>';

    $returns = array();
    $returns['warning0'] = __($guid, "Uninstall was successful. You will still need to remove the module's files yourself.");
    $returns['error5'] = __($guid, 'Install failed because either the module name was not given or the manifest file was invalid.');
    $returns['error6'] = __($guid, 'Install failed because a module with the same name is already installed.');
    $returns['warning1'] = __($guid, 'Install failed, but module was added to the system and set non-active.');
    $returns['warning2'] = __($guid, 'Install was successful, but module could not be activated.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    if (isset($_SESSION[$guid]['moduleInstallError'])) {
        if ($_SESSION[$guid]['moduleInstallError'] != '') {
            echo "<div class='error'>";
            echo __($guid, 'The following SQL statements caused errors:').' '.$_SESSION[$guid]['moduleInstallError'];
            echo '</div>';
        }
        $_SESSION[$guid]['moduleInstallError'] = null;
    }

    //Get modules from database, and store in an array
    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonModule ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $modulesSQL = array();
    while ($row = $result->fetch()) {
        $modulesSQL[$row['name']][0] = $row;
        $modulesSQL[$row['name']][1] = 'orphaned';
    }

    //Get list of modules in /modules directory
    $modulesFS = glob($_SESSION[$guid]['absolutePath'].'/modules/*', GLOB_ONLYDIR);

    echo "<div class='warning'>";
    echo sprintf(__($guid, 'To install a module, upload the module folder to %1$s on your server and then refresh this page. After refresh, the module should appear in the list below: use the install button in the Actions column to set it up.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/modules/</u></b>');
    echo '</div>';

    if (count($modulesFS) < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo "<th style='width: 200px;'>";
        echo __($guid, 'Description');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Type');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Active');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Version');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Author');
        echo '</th>';
        echo "<th style='width: 140px!important'>";
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        foreach ($modulesFS as $moduleFS) {
            $moduleName = substr($moduleFS, strlen($_SESSION[$guid]['absolutePath'].'/modules/'));
            $modulesSQL[$moduleName][1] = 'present';

            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            $installed = true;
            if (isset($modulesSQL[$moduleName][0]) == false) {
                $installed = false;
                $rowNum = 'warning';
            }

            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo __($guid, $moduleName);
            echo '</td>';
            if ($installed) {
                echo '<td>';
                echo __($guid, 'Installed');
                echo '</td>';
            } else {
                //Check for valid manifest
                $manifestOK = false;
                if (include $_SESSION[$guid]['absolutePath']."/modules/$moduleName/manifest.php") {
                    if ($name != '' and $description != '' and $version != '') {
                        if ($name == $moduleName) {
                            $manifestOK = true;
                        }
                    }
                }
                if ($manifestOK) {
                    echo '<td colspan=6>';
                    echo __($guid, 'Not Installed');
                    echo '</td>';
                } else {
                    echo '<td colspan=7>';
                    echo __($guid, 'Module error due to incorrect manifest file or folder name.');
                    echo '</td>';
                }
            }
            if ($installed) {
                echo '<td>';
                echo __($guid, $modulesSQL[$moduleName][0]['description']);
                echo '</td>';
                echo '<td>';
                echo __($guid, $modulesSQL[$moduleName][0]['type']);
                echo '</td>';
                echo '<td>';
                echo ynExpander($guid, $modulesSQL[$moduleName][0]['active']);
                echo '</td>';
                echo '<td>';
                if ($modulesSQL[$moduleName][0]['type'] == 'Additional') {
                    echo 'v'.$modulesSQL[$moduleName][0]['version'];
                } else {
                    echo 'v'.$version;
                }
                echo '</td>';
                echo '<td>';
                if ($row['url'] != '') {
                    echo "<a href='".$modulesSQL[$moduleName][0]['url']."'>".$modulesSQL[$moduleName][0]['author'].'</a>';
                } else {
                    echo $modulesSQL[$moduleName][0]['author'];
                }
                echo '</td>';
                echo "<td style='width: 120px'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/module_manage_edit.php&gibbonModuleID='.$modulesSQL[$moduleName][0]['gibbonModuleID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                if ($modulesSQL[$moduleName][0]['type'] == 'Additional') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/module_manage_uninstall.php&gibbonModuleID='.$modulesSQL[$moduleName][0]['gibbonModuleID']."'><img title='Uninstall' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/module_manage_update.php&gibbonModuleID='.$modulesSQL[$moduleName][0]['gibbonModuleID']."'><img title='Update' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/delivery2.png'/></a>";
                }
                echo '</td>';
            } else {
                if ($manifestOK) {
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/module_manage_installProcess.php?name='.urlencode($moduleName)."'><img title='".__($guid, 'Install')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</td>';
                }
            }
            echo '</tr>';
        }
        echo '</table>';
    }

    //Find and display orphaned modules
    $orphans = false;
    foreach ($modulesSQL as $moduleSQL) {
        if ($moduleSQL[1] == 'orphaned') {
            $orphans = true;
        }
    }

    if ($orphans) {
        echo "<h2 style='margin-top: 40px'>";
        echo __($guid, 'Orphaned Modules');
        echo '</h2>';
        echo '<p>';
        echo __($guid, 'These modules are installed in the database, but are missing from within the file system.');
        echo '</p>';

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo "<th style='width: 140px!important'>";
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        foreach ($modulesSQL as $moduleSQL) {
            if ($moduleSQL[1] == 'orphaned') {
                $moduleName = $moduleSQL[0]['name'];

                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo __($guid, $moduleName);
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/module_manage_uninstall.php&gibbonModuleID='.$modulesSQL[$moduleName][0]['gibbonModuleID']."&orphaned=true'><img title='Remove Record' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '<tr>';
        echo "<td colspan=7 class='right'>";
        ?>
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					<?php
                echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
}
?>
