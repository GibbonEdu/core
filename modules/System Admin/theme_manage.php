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

use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\ThemeGateway;

include './modules/System Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Themes'));

    $returns = array(
        'warning0' => __("Uninstall was successful. You will still need to remove the theme's files yourself."),
        'success1' => __('Install was successful.'),
        'success2' => __('Uninstall was successful.'),
        'error3'   => __('Your request failed because your manifest file was invalid.'),
        'error4'   => __('Your request failed because a theme with the same name is already installed.'),
    );

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    echo "<div class='message'>";
    echo sprintf(__('To install a theme, upload the theme folder to %1$s on your server and then refresh this page. After refresh, the theme should appear in the list below: use the install button in the Actions column to set it up.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/themes/</u></b>');
    echo '</div>';    
    
    echo '<h2>';
    echo __('Installed');
    echo '</h2>';        
    
    // Get list of themes in /themes directory
    $themeFolders = glob($_SESSION[$guid]['absolutePath'].'/themes/*', GLOB_ONLYDIR);
    $themeGateway = $container->get(ThemeGateway::class);

    // CRITERIA
    $criteria = $themeGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromPOST();

    $themes = $themeGateway->queryThemes($criteria);
    $themeNames = $themeGateway->getAllThemeNames();
    $orphans = array();

    // Build a set of theme data, flagging orphaned themes that do not appear to be in the themes folder.
    // Also checks for available updates by comparing version numbers
    $themes->transform(function (&$theme) use ($guid, &$orphans, &$themeFolders, &$themeGateway) {
        if (array_search($_SESSION[$guid]['absolutePath'].'/themes/'.$theme['name'], $themeFolders) === false) {
            $theme['orphaned'] = true;
            $orphans[] = $theme;
            return;
        }
        
        $manifest = getThemeManifest($theme['name'], $guid);
        if ($manifest && $manifest['manifestOK']) {
            if (version_compare($manifest['version'], $theme['version'], '>')) {
                $data = array('version' => $manifest['version'], 'author' => $manifest['author'], 'description' => $manifest['description'], 'url' => $manifest['url']);
                $themeGateway->update($theme['gibbonThemeID'], $data);
                $theme['version'] = $manifest['version'];
            }
        }            
    });

    // Build a set of uninstalled themes by checking the $themes DataSet.
    // Validates the manifest file and grabs the theme details from there.
    $uninstalledThemes = array_reduce($themeFolders, function($group, $themePath) use ($guid, &$themeNames) {
        $themeName = substr($themePath, strlen($_SESSION[$guid]['absolutePath'].'/themes/'));
        if (!in_array($themeName, $themeNames)) {
            $theme = getThemeManifest($themeName, $guid);
            
            if (!$theme || !$theme['manifestOK']) {
                $theme['name'] = $themeName;
                $theme['description'] = __('Theme error due to incorrect manifest file or folder name.');
            }
            $group[] = $theme;
        }

        return $group;
    }, array());    
       
    // INSTALLED THEMES
    $form = Form::create('theme_manage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/theme_manageProcess.php');
    
    $form->setClass('fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->setClass('w-full blank');

    // DATA TABLE
    $table = $form->addRow()->addDataTable('themeManage', $criteria)->withData($themes);
    
    $table->modifyRows(function ($theme, $row) {
        if (!empty($theme['orphaned'])) {
            return '';
        }
        return $row;
    });

    $table->addColumn('name', __('Name'))->width('20%');
    $table->addColumn('version', __('Version'))->width('10%');
    $table->addColumn('description', __('Description'))->width('40%');
    $table->addColumn('author', __('Author'))
        ->format(Format::using('link', ['url', 'author']));
            
    $table->addColumn('active', __('Active'))
        ->width('10%')
        ->notSortable()
        ->format(function($themes) use ($form) {
            $checked = ($themes['active'] == 'Y')? $themes['gibbonThemeID'] : '';
                
            return $form->getFactory()
                ->createRadio('gibbonThemeID')
                ->addClass('inline right')
                ->fromArray(array($themes['gibbonThemeID'] => ''))
                ->checked($checked)
                ->getOutput();
        });

    $table->addActionColumn()
        ->addParam('gibbonThemeID')
        ->format(function ($themes, $actions) use ($guid) {
            
            if (($themes['active'] != 'Y') and ($themes['name'] != 'Default')) {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/System Admin/theme_manage_uninstall.php');
            }
        });     
            
    $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth standardForm');
    $table->addRow()->addSubmit();
    
    echo $form->getOutput();
    
    // UNINSTALLED THEMES
    if (!empty($uninstalledThemes)) {
        echo '<h2>';
        echo __('Not Installed');
        echo '</h2>';

        $tableInstallThemes = DataTable::create('themeInstall');
        
        $tableInstallThemes->modifyRows(function ($theme, $row) {
            $row->addClass($theme['manifestOK'] == false ? 'error' : 'warning');
            return $row;
        });

        $tableInstallThemes->addColumn('name', __('Name'))->width('20%');
        $tableInstallThemes->addColumn('version', __('Version'))->width('10%');
        $tableInstallThemes->addColumn('description', __('Description'))->width('40%');
        $tableInstallThemes->addColumn('author', __('Author'))
            ->format(Format::using('link', ['url', 'author']));
        
        $tableInstallThemes->addActionColumn()
            ->addParam('name')
            ->format(function ($row, $actions) {
                if ($row['manifestOK']) {
                    $actions->addAction('install', __('Install'))
                        ->setIcon('page_new')
                        ->directLink()
                        ->setURL('/modules/System Admin/theme_manage_installProcess.php');
                }
            });

        echo $tableInstallThemes->render(new DataSet($uninstalledThemes));
    }

    // ORPHANED THEMES
    if ($orphans) {
        echo '<h2>';
        echo __('Orphaned Themes');
        echo '</h2>';
        echo '<p>';
        echo __('These themes are installed in the database, but are missing from within the file system.');
        echo '</p>';

        $tableOrphans = DataTable::create('themeOrphans');

        $tableOrphans->addColumn('name', __('Name'));

        $tableOrphans->addActionColumn()
            ->addParam('gibbonThemeID')
            ->format(function ($row, $actions) {
                
                $actions->addAction('uninstall', __('Remove Record'))
                    ->setIcon('garbage')
                    ->addParam('orphaned', 'true')
                    ->setURL('/modules/System Admin/theme_manage_uninstall.php');
            });

        echo $tableOrphans->render(new DataSet($orphans));
    }     
}
