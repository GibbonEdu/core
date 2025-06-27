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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemCheck.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('System Check'));

    $versionDB = $container->get(SettingGateway::class)->getSettingByScope('System', 'version');

    $trueIcon =  icon('solid', 'check', 'size-6 ml-2 fill-current text-green-600');
    $falseIcon = icon('solid', 'cross', 'size-6 ml-2 fill-current text-red-700');

    $versionTitle = __('%s Version');
    $versionMessage = __('%s requires %s version %s or higher');

    $phpVersion = phpversion();
    $apacheVersion = function_exists('apache_get_version')? apache_get_version() : false;
    $mysqlVersion = $pdo->selectOne("SELECT VERSION()");
    $mysqlCollation = $pdo->selectOne("SELECT COLLATION('gibbon')");
    $backgroundProcessing = function_exists('exec') && @exec('echo EXEC') == 'EXEC';

    $phpRequirement = $gibbon->getSystemRequirement('php');
    $mysqlRequirement = $gibbon->getSystemRequirement('mysql');
    $apacheRequirement = $gibbon->getSystemRequirement('apache');
    $extensions = $gibbon->getSystemRequirement('extensions');
    $settings = $gibbon->getSystemRequirement('settings');

    // File Check
    $fileCount = 0;
    $publicWriteCount = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($session->get("absolutePath"))) as $filename)
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) != 'php') continue;
        if (strpos(pathinfo($filename, PATHINFO_DIRNAME), '/uploads') !== false) continue;
        if (fileperms($filename) & 0x0002) $publicWriteCount++;
        $fileCount++;
    }

    // Uploads folder check, make a request using a Guzzle HTTP get request
    $statusCheck = checkUploadsFolderStatus($session->get('absoluteURL'));
    if (!$statusCheck) {
        echo Format::alert(__('The system check has detected that your uploads folder may be publicly accessible. This suggests a serious issue in your server configuration that should be addressed immediately. Please visit our {documentation} page for instructions to fix this issue.', [
            'documentation' => Format::link('https://docs.gibbonedu.org/introduction/post-installation', __('Post-Install and Server Config')),
        ]), 'error');
    }

    $form = Form::create('systemCheck', "");

    $form->addRow()->addHeading('System Requirements', __('System Requirements'));

    $row = $form->addRow();
        $row->addLabel('phpVersionLabel', sprintf($versionTitle, 'PHP'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'PHP', $phpRequirement));
        $row->addTextField('phpVersion')->setValue($phpVersion)->readonly()
            ->append((version_compare($phpVersion, $phpRequirement, '>='))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('mysqlVersionLabel', sprintf($versionTitle, 'MySQL'))->description(sprintf($versionMessage, __('Gibbon').' v'.$version, 'MySQL', $mysqlRequirement));
        $row->addTextField('mysqlVersion')->setValue($mysqlVersion)->readonly()
            ->append((version_compare($mysqlVersion, $mysqlRequirement, '>='))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('mysqlCollationLabel', __('MySQL Collation'))->description(sprintf( __('Database collation should be set to %s'), 'utf8_general_ci or utf8mb3_general_ci'));
        $row->addTextField('mysqlCollation')->setValue($mysqlCollation)->readonly()
            ->append(($mysqlCollation == 'utf8_general_ci' || $mysqlCollation == 'utf8mb3_general_ci')? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('pdoSupportLabel', __('MySQL PDO Support'));
        $row->addTextField('pdoSupport')->setValue((@extension_loaded('pdo_mysql'))? __('Installed') : __('Not Installed'))->readonly()
            ->append((@extension_loaded('pdo') && extension_loaded('pdo_mysql'))? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('backgroundProcessingLabel', __('Background Processing'))->description(__('Requires PHP exec() function access'));
        $row->addTextField('backgroundProcessing')->setValue($backgroundProcessing ? __('Enabled') : __('Not Available'))->readonly();
        $row->addContent($backgroundProcessing? $trueIcon : $falseIcon);

    // APACHE MODULES
    if ($apacheVersion !== false) {
        $form->addRow()->addHeading('Apache Modules', __('Apache Modules'));

        $apacheModules = @apache_get_modules();
        foreach ($apacheRequirement as $moduleName) {
            $active = @in_array($moduleName, $apacheModules);
            $row = $form->addRow();
                $row->addLabel('moduleLabel', $moduleName);
                $row->addTextField('module')->setValue(($active)? __('Enabled') : __('N/A'))->readonly()
                    ->append(($active)? $trueIcon : $falseIcon);
        }
    }

    // PHP EXTENSIONS
    if (!empty($extensions) && is_array($extensions)) {
        $form->addRow()
            ->addHeading('PHP Extensions', __('PHP Extensions'))
            ->append(__('Gibbon requires you to enable the PHP extensions in the following list. The process to do so depends on your server setup.'));

        foreach ($extensions as $extension) {
            $installed = @extension_loaded($extension);
            $row = $form->addRow();
                $row->addLabel('extensionLabel', $extension);
                $row->addTextField('extension')->setValue(($installed)? __('Installed') : __('Not Installed'))->readonly()
                    ->append(($installed)? $trueIcon : $falseIcon);
        }
    }

    // PHP SETTINGS
    if (!empty($settings) && is_array($settings)) {
        $form->addRow()
            ->addHeading('PHP Settings', __('PHP Settings'))
            ->append(sprintf(__('Configuration values can be set in your system %s file. On shared host, use %s to set php settings.'), '<code>php.ini</code>', '.htaccess'));

        foreach ($settings as $settingDetails) {
            if (!is_array($settingDetails) || count($settingDetails) != 3) continue;
            [$setting, $operator, $compare] = $settingDetails;
            $value = @ini_get($setting);

            if ($setting == 'session.gc_maxlifetime') $compare = $session->get('sessionDuration');

            $isValid = ($operator == '==' && $value == $compare)
                || ($operator == '>=' && $value >= $compare)
                || ($operator == '<=' && $value <= $compare)
                || ($operator == '>' && $value > $compare)
                || ($operator == '<' && $value < $compare);

            $row = $form->addRow();
                $row->addLabel('settingLabel', '<b>'.$setting.'</b> <small>'.$operator.' '.$compare.'</small>');
                $row->addTextField('setting')->setValue($value)->readonly()
                    ->append($isValid? $trueIcon : $falseIcon);
        }
    }

    // FILE PERMS
    $form->addRow()->addHeading('File Permissions', __('File Permissions'));

    $row = $form->addRow();
        $row->addLabel('systemWriteLabel', __('System not publicly writeable'));
        $row->addTextArea('systemWrite')->setValue(sprintf(__('%s files checked (%s publicly writeable)'), $fileCount, $publicWriteCount))->setRows(1)->addClass(' max-w-1/2 text-left')->readonly()
            ->append($publicWriteCount == 0? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('systemWriteLabel', __('Uploads folder not publicly accessible'));
        $row->addTextArea('systemWrite')->setValue($session->get('absoluteURL').'/uploads')->setRows(1)->addClass(' max-w-1/2 text-left')->readonly()
            ->append($statusCheck? $trueIcon : $falseIcon);

    $row = $form->addRow();
        $row->addLabel('uploadsFolderLabel', __('Uploads folder server writeable'));
        $row->addTextField('uploadsFolder')->setValue($session->get('absoluteURL').'/uploads')->readonly()
            ->append(is_writable($session->get('absolutePath').'/uploads')? $trueIcon : $falseIcon);

    echo $form->getOutput();
}
