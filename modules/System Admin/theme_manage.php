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

include './modules/System Admin/moduleFunctions.php';

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Themes').'</div>';
    echo '</div>';

    $returns = array(
        'warning0' => __("Uninstall was successful. You will still need to remove the theme's files yourself."),
        'success0' => __('Uninstall was successful.'),
        'success1' => __('Install was successful.'),
        'error3'   => __('Your request failed because your manifest file was invalid.'),
        'error4'   => __('Your request failed because a theme with the same name is already installed.'),
    );

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    // Get themes from database, and store in an array
    $sql = "SELECT name as groupBy, gibbonTheme.*, 'Orphaned' AS status FROM gibbonTheme ORDER BY name";
    $result = $pdo->executeQuery(array(), $sql);
    $themesSQL = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

    // Get list of themes in /themes directory
    $themesFS = glob($_SESSION[$guid]['absolutePath'].'/themes/*', GLOB_ONLYDIR);

    // Build a theme data set from SQL and FileSystem info
    $themes = array_map(function($themeFilename) use (&$themesSQL, &$pdo, $guid, $version) {
        $themeName = substr($themeFilename, strlen($_SESSION[$guid]['absolutePath'].'/themes/'));
        $manifestData = getThemeManifest($themeName, $guid);

        if (isset($themesSQL[$themeName])) {
            $theme = &$themesSQL[$themeName];
            $theme['status'] = __('Installed');
            $theme['installed'] = true;

            if ($theme['name'] == 'Default') {
                $theme['version'] = $version;
            } else if (version_compare($manifestData['version'], $theme['version'], '>')) {
                // Update the database to match the manifest version
                $data = array('version' => $manifestData['version'], 'gibbonThemeID' => $theme['gibbonThemeID']);
                $sql = "UPDATE gibbonTheme SET version=:version WHERE gibbonThemeID=:gibbonThemeID";
                $result = $pdo->executeQuery($data, $sql);
                $theme['version'] = $manifestData['version'];
            }
        } else {
            $theme = &$manifestData;
            $theme['status'] = $theme['manifestOK']? __('Not Installed') : __('Theme Error');
            $theme['installed'] = false;
        }

        return $theme;
    }, $themesFS);

    echo "<div class='warning'>";
    echo sprintf(__($guid, 'To install a theme, upload the theme folder to %1$s on your server and then refresh this page. After refresh, the theme should appear in the list below: use the install button in the Actions column to set it up.'), '<b><u>'.$_SESSION[$guid]['absolutePath'].'/themes/</u></b>');
    echo '</div>';

    if (count($themes) == 0) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $form = Form::create('themeManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/theme_manageProcess.php');
        
        $form->setClass('fullWidth');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        
        $row = $form->addRow()->setClass('heading head');
            $row->addContent(__('Name'));
            $row->addContent(__('Status'));
            $row->addContent(__('Version'));
            $row->addContent(__('Description'));
            $row->addContent(__('Author'));
            $row->addContent(__('Active'));
            $row->addContent(__('Action'));

        foreach ($themes as $theme) {
            $rowClass = !$theme['installed']? (!$theme['manifestOK']? 'error' : 'warning') : '';

            $row = $form->addRow()->addClass($rowClass);
                $row->addContent($theme['name']);
                $row->addContent($theme['status']);
                $row->addContent('v'.$theme['version']);
                $row->addContent($theme['description']);
                $row->addWebLink($theme['author'])->setURL($theme['url']);

            if ($theme['installed']) {
                $row->addRadio('gibbonThemeID')
                    ->fromArray(array($theme['gibbonThemeID'] => ''))
                    ->checked($theme['active'] == 'Y'? $theme['gibbonThemeID'] : '')
                    ->setClass('');

                if ($theme['name'] != 'Default') {
                    $row->addWebLink('<img title="'.__('Remove Record').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/>')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/theme_manage_uninstall.php&width=650&height=135')
                        ->setClass('thickbox')
                        ->addParam('gibbonThemeID', $theme['gibbonThemeID']);
                } else {
                    $row->addContent('');
                }
            } else {
                $row->addContent('');
                if ($theme['manifestOK']) {
                    $row->addWebLink('<img title="'.__('Install').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/page_new.png"/>')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/theme_manage_installProcess.php')
                        ->addParam('name', urlencode($theme['themeName']));
                } else {
                    $row->addContent('');
                }
            }
        }

        $form->addRow()->addSubmit();
        
        echo $form->getOutput();
    }

    // Find and display orphaned themes
    $themesOrphaned = array_filter($themesSQL, function($item) {
        return $item['status'] == 'Orphaned';
    });

    if (count($themesOrphaned) > 0) {
        echo "<h2 style='margin-top: 40px'>";
        echo __($guid, 'Orphaned Themes');
        echo '</h2>';
        echo '<p>';
        echo __($guid, 'These themes are installed in the database, but are missing from within the file system.');
        echo '</p>';

        echo "<table cellspacing='0' class='colorOddEven fullWidth'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo "<th style='width: 50px'>";
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        foreach ($themesOrphaned as $themeName => $theme) {
            echo '<tr>';
            echo '<td>';
            echo $theme['name'];
            echo '</td>';
            echo '<td>';
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/theme_manage_uninstall.php&gibbonThemeID='.$theme['gibbonThemeID']."&orphaned=true&width=650&height=135'><img title='".__($guid, 'Remove Record')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

