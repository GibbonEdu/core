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
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\ModuleGateway;

include './modules/System Admin/moduleFunctions.php';

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

    if (!empty($_SESSION[$guid]['moduleInstallError'])) {
        echo "<div class='error'>";
        echo __($guid, 'The following SQL statements caused errors:').' '.$_SESSION[$guid]['moduleInstallError'];
        echo '</div>';
        $_SESSION[$guid]['moduleInstallError'] = null;
    }

    echo "<div class='message'>";
    echo sprintf(__($guid, 'To install a module, upload the module folder to %1$s on your server and then refresh this page. After refresh, the module should appear in the list below: use the install button in the Actions column to set it up.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/modules/</u></b>');
    echo '</div>';

    echo '<h2>';
    echo __('Installed');
    echo '</h2>';

    //Get list of modules in /modules directory
    $moduleFolders = glob($_SESSION[$guid]['absolutePath'].'/modules/*', GLOB_ONLYDIR);

    // QUERY
    $moduleGateway = $container->get(ModuleGateway::class);
    $criteria = $moduleGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromArray($_POST);

    $modules = $moduleGateway->queryModules($criteria);
    $moduleNames = $moduleGateway->getAllModuleNames();
    $orphans = array();

    // Build a set of module data, flagging orphaned modules that do not appear to be in the modules folder.
    // Also checks for available updates by comparing version numbers for Additional modules.
    $modules->transform(function (&$module) use ($guid, $version, &$orphans, &$moduleFolders) {
        if (array_search($_SESSION[$guid]['absolutePath'].'/modules/'.$module['name'], $moduleFolders) === false) {
            $module['orphaned'] = true;
            $orphans[] = $module;
            return;
        }

        $module['status'] = __('Installed');
        $module['name'] = $module['type'] == 'Core' ? __($module['name']) : __($module['name'], $module['name']);
        $module['versionDisplay'] = $module['type'] == 'Core' ? 'v'.$version : 'v'.$module['version'];
        
        if ($module['type'] == 'Additional') {
            $versionFromFile = getModuleVersion($module['name'], $guid);
            if (version_compare($versionFromFile, $module['version'], '>')) {
                $module['status'] = '<b>'.__('Update').' '.__('Available').'</b><br/>';
                $module['update'] = true;
            }
        }
    });

    // Build a set of uninstalled modules by checking the $modules DataSet.
    // Validates the manifest file and grabs the module details from there.
    $uninstalledModules = array_reduce($moduleFolders, function($group, $modulePath) use ($guid, &$moduleNames) {
        $moduleName = substr($modulePath, strlen($_SESSION[$guid]['absolutePath'].'/modules/'));
        if (!in_array($moduleName, $moduleNames)) {
            $module = getModuleManifest($moduleName, $guid);
            $module['status'] = __('Not Installed');
            $module['versionDisplay'] = !empty($module['version']) ? 'v'.$module['version'] : '';

            if (!$module || !$module['manifestOK']) {
                $module['name'] = $moduleName;
                $module['status'] = __('Error');
                $module['description'] = __('Module error due to incorrect manifest file or folder name.');
            }
            $group[] = $module;
        }

        return $group;
    }, array());


    // INSTALLED MODULES
    $table = DataTable::createPaginated('moduleManage', $criteria);

    $table->modifyRows(function ($module, $row) {
        if (!empty($module['orphaned'])) return '';
        if (!empty($module['update'])) $row->addClass('current');
        if ($module['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'type:core'       => __('Type').': '.__('Core'),
        'type:additional' => __('Type').': '.__('Additional'),
        'active:Y' => __('Active').': '.__('Yes'),
        'active:N' => __('Active').': '.__('No'),
    ]);

    $table->addColumn('name', __('Name'));
    $table->addColumn('status', __('Status'))->notSortable();
    $table->addColumn('description', __('Description'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('active', __('Active'))
          ->format(Format::using('yesNo', 'active'));
    $table->addColumn('versionDisplay', __('Version'))->sortable(['version']);
    $table->addColumn('author', __('Author'))
          ->format(Format::using('link', ['url', 'author']));

    $table->addActionColumn()
        ->addParam('gibbonModuleID')
        ->format(function ($row, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/System Admin/module_manage_edit.php');

            if ($row['type'] != 'Core') {
                $actions->addAction('uninstall', __('Uninstall'))
                        ->setIcon('garbage')
                        ->setURL('/modules/System Admin/module_manage_uninstall.php');

                $actions->addAction('update', __('Update'))
                        ->setIcon('delivery2')
                        ->setURL('/modules/System Admin/module_manage_update.php');
            }
        });

    echo $table->render($modules);

    // UNINSTALLED MODULES
    if (!empty($uninstalledModules)) {
        echo '<h2>';
        echo __('Not Installed');
        echo '</h2>';
        
        $table = DataTable::create('moduleInstall');

        $table->modifyRows(function ($module, $row) {
            $row->addClass($module['manifestOK'] == false ? 'error' : 'warning');
            return $row;
        });

        $table->addColumn('name', __('Name'));
        $table->addColumn('status', __('Status'))->notSortable();
        $table->addColumn('description', __('Description'));
        $table->addColumn('versionDisplay', __('Version'));
        $table->addColumn('author', __('Author'))
               ->format(Format::using('link', ['url', 'author']));

        $table->addActionColumn()
            ->addParam('name')
            ->format(function ($row, $actions) {
                if ($row['manifestOK']) {
                    $actions->addAction('install', __('Install'))
                            ->setIcon('page_new')
                            ->isDirect()
                            ->setURL('/modules/System Admin/module_manage_installProcess.php');
                }
            });

        echo $table->render(new DataSet($uninstalledModules));
    }

    // ORPHANED MODULES
    if ($orphans) {
        echo '<h2>';
        echo __('Orphaned Modules');
        echo '</h2>';

        echo '<p>';
        echo __('These modules are installed in the database, but are missing from within the file system.');
        echo '</p>';

        $table = DataTable::create('moduleOrphans');

        $table->addColumn('name', __('Name'));

        $table->addActionColumn()
            ->addParam('gibbonModuleID')
            ->format(function ($row, $actions) {
                if ($row['type'] != 'Core') {
                    $actions->addAction('uninstall', __('Remove Record'))
                        ->setIcon('garbage')
                        ->addParam('orphaned', 'true')
                        ->setURL('/modules/System Admin/module_manage_uninstall.php');
                }
            });

        echo $table->render(new DataSet($orphans));
    }
}
