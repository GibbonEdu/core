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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemCheck.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('System Check'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $versionDB = getSettingByScope($connection2, 'System', 'version');

    $trueIcon = "<img title='" . __('Yes'). "' src='".$_SESSION[$guid]["absoluteURL"]."/themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/iconTick.png' style='width:20px;height:20px;margin-right:10px' />";
    $falseIcon = "<img title='" . __('No'). "' src='".$_SESSION[$guid]["absoluteURL"]."/themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/iconCross.png' style='width:20px;height:20px;margin-right:10px' />";

    $versionTitle = __('%s Version');
    $versionMessage = __('%s requires %s version %s or higher');

    $phpVersion = phpversion();
    $apacheVersion = function_exists('apache_get_version')? apache_get_version() : false;
    $mysqlVersion = $pdo->selectOne("SELECT VERSION()");
    $mysqlCollation = $pdo->selectOne("SELECT COLLATION('gibbon')");
    $backgroundProcessing = @exec('echo EXEC') == 'EXEC';

    $phpRequirement = $gibbon->getSystemRequirement('php');
    $mysqlRequirement = $gibbon->getSystemRequirement('mysql');
    $apacheRequirement = $gibbon->getSystemRequirement('apache');
    $extensions = $gibbon->getSystemRequirement('extensions');
    $settings = $gibbon->getSystemRequirement('settings');

    // File Check
    $fileCount = 0;
    $publicWriteCount = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SESSION[$guid]["absolutePath"])) as $filename)
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) != 'php') continue;
        if (strpos(pathinfo($filename, PATHINFO_DIRNAME), '/uploads') !== false) continue;
        if (fileperms($filename) & 0x0002) $publicWriteCount++;
        $fileCount++;
    }

    $form = Form::createTable('systemCheck', "")->setClass('smallIntBorder w-full');

    $form->addRow()->addHeading(__('System Requirements'));

    $row = $form->addRow();
        $row->addLabel('phpVersionLabel', sprintf($versionTitle, 'PHP'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'PHP', $phpRequirement));
        $row->addTextField('phpVersion')->setValue($phpVersion)->readonly();
        $row->addContent((version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('mysqlVersionLabel', sprintf($versionTitle, 'MySQL'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'MySQL', $mysqlRequirement));
        $row->addTextField('mysqlVersion')->setValue($mysqlVersion)->readonly();
        $row->addContent((version_compare($mysqlVersion, $mysqlRequirement, '>='))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('mysqlCollationLabel', __('MySQL Collation'))->description(sprintf( __('Database collation should be set to %s'), 'utf8_general_ci'));
        $row->addTextField('mysqlCollation')->setValue($mysqlCollation)->readonly();
        $row->addContent(($mysqlCollation == 'utf8_general_ci')? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('pdoSupportLabel', __('MySQL PDO Support'));
        $row->addTextField('pdoSupport')->setValue((@extension_loaded('pdo_mysql'))? __('Installed') : __('Not Installed'))->readonly();
        $row->addContent((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('backgroundProcessingLabel', __('Background Processing'))->description(__('Requires PHP exec() function access'));
        $row->addTextField('backgroundProcessing')->setValue($backgroundProcessing ? __('Enabled') : __('Not Available'))->readonly();
        $row->addContent((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

    // APACHE MODULES
    if ($apacheVersion !== false) {
        $form->addRow()->addHeading(__('Apache Modules'));

        $apacheModules = @apache_get_modules();
        foreach ($apacheRequirement as $moduleName) {
            $active = @in_array($moduleName, $apacheModules);
            $row = $form->addRow();
                $row->addLabel('moduleLabel', $moduleName);
                $row->addTextField('module')->setValue(($active)? __('Enabled') : __('N/A'))->readonly();
                $row->addContent(($active)? $trueIcon : $falseIcon);
        }
    }

    // PHP EXTENSIONS
    if (!empty($extensions) && is_array($extensions)) {
        $form->addRow()
            ->addHeading(__('PHP Extensions'))
            ->append(__('Gibbon requires you to enable the PHP extensions in the following list. The process to do so depends on your server setup.'));

        foreach ($extensions as $extension) {
            $installed = @extension_loaded($extension);
            $row = $form->addRow();
                $row->addLabel('extensionLabel', $extension);
                $row->addTextField('extension')->setValue(($installed)? __('Installed') : __('Not Installed'))->readonly();
                $row->addContent(($installed)? $trueIcon : $falseIcon);
        }
    }

    // PHP SETTINGS
    if (!empty($settings) && is_array($settings)) {
        $form->addRow()
            ->addHeading(__('PHP Settings'))
            ->append(sprintf(__('Configuration values can be set in your system %s file. On shared host, use %s to set php settings.'), '<code>php.ini</code>', '.htaccess'));

        foreach ($settings as $settingDetails) {
            if (!is_array($settingDetails) || count($settingDetails) != 3) continue;
            list($setting, $operator, $compare) = $settingDetails;
            $value = @ini_get($setting);

            if ($setting == 'session.gc_maxlifetime') $compare = $gibbon->session->get('sessionDuration');

            $isValid = ($operator == '==' && $value == $compare)
                || ($operator == '>=' && $value >= $compare)
                || ($operator == '<=' && $value <= $compare)
                || ($operator == '>' && $value > $compare)
                || ($operator == '<' && $value < $compare);

            $row = $form->addRow();
                $row->addLabel('settingLabel', '<b>'.$setting.'</b> <small>'.$operator.' '.$compare.'</small>');
                $row->addTextField('setting')->setValue($value)->readonly();
                $row->addContent($isValid? $trueIcon : $falseIcon);
        }
    }

    // FILE PERMS
    $form->addRow()->addHeading(__('File Permissions'));

    $row = $form->addRow();
        $row->addLabel('systemWriteLabel', __('System not publicly writeable'));
        $row->addTextArea('systemWrite')->setValue(sprintf(__('%s files checked (%s publicly writeable)'), $fileCount, $publicWriteCount))->setRows(1)->addClass('w-64 max-w-1/2 text-left')->readonly();
        $row->addContent($publicWriteCount == 0? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('uploadsFolderLabel', __('Uploads folder server writeable'));
        $row->addTextField('uploadsFolder')->setValue($_SESSION[$guid]['absoluteURL'].'/uploads')->readonly();
        $row->addContent(is_writable($_SESSION[$guid]['absolutePath'].'/uploads')? $trueIcon : $falseIcon);

    echo $form->getOutput();
}
