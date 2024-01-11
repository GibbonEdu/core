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
use Gibbon\Services\Format;
use Gibbon\Database\Updater;
use Gibbon\Tables\DataTable;
use Gibbon\Database\Migrations\EngineUpdate;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Update'));

    $return = $_GET['return'] ?? '';
    $page->return->addReturns([
        'warning1' => __('Some aspects of your request failed, but others were successful. The elements that failed are shown below:'),
        'error3' => __('Your request failed because your inputs were invalid, or no update was required.'),
    ]);

    // Get and display SQL errors
    if ($session->has('systemUpdateError')) {
        echo Format::alert(__('The following SQL statements caused errors:').'<br/>'.implode('<br/>', $session->get('systemUpdateError')));
        $session->forget('systemUpdateError');
    }

    $updater = $container->get(Updater::class);
    $updateRequired = $updater->isUpdateRequired();

    $table = DataTable::createDetails('version');
    $table->setDescription(__('This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.'));

    $table->addColumn('versionCode', __('Codebase Version'));
    $table->addColumn('versionDB', __('Database Version'));
    if ($updater->isCuttingEdge()) {
        $table->addColumn('cuttingEdgeCode', __('Cutting Edge Code'));
    }

    echo $table->render([[
        'versionCode'     => 'v'.$updater->versionCode,
        'versionDB'       => 'v'.$updater->versionDB,
        'cuttingEdgeCode' => __('Line').': '.$updater->cuttingEdgeCodeLine,
    ]]);

    if (!$updater->isCuttingEdge()) {
        // Check for new version of Gibbon
        echo getCurrentVersion($guid, $connection2, $version);

        $form = Form::create('action', $session->get('absoluteURL').'/modules/System Admin/updateProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('type', 'regularRelease');

        if ($return == 'success0') {
            $form->addRow()->addContent(__('Your Gibbon core is up to date.'))->addClass('py-16 text-center text-gray-600 text-lg');
        } elseif ($updateRequired === -1) {
            // Error
            $form->setDescription(Format::alert(__('An error has occurred determining the version of the system you are using.'), 'error'));
        } elseif (!$updateRequired) {
            // Instructions on how to update
            $form->setTitle(__('Update Instructions'));
            $form->setDescription(Format::list([
                sprintf(__('You are currently using Gibbon v%1$s.'), $updater->versionCode),
                sprintf(__('Check %1$s for a newer version of Gibbon.'), "<a target='_blank' href='https://gibbonedu.org/download'>the Gibbon download page</a>"),
                __('Download the latest version, and unzip it on your computer.'),
                __('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.'),
                __('Reload this page and follow the instructions to update your database to the latest version.'),
            ], 'ol'));
        } elseif ($updateRequired) {
            // Time to update
            $form->setTitle(__('Database Update'));
            $form->setDescription(sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s to v%2$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $updater->versionDB, $updater->versionCode).'</b>');

            $row = $form->addRow();
                $row->addContent('v'.$updater->versionDB)->addClass('text-xl text-right');
                $row->addContent('<img src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/page_right.png" class="w-6">')->addClass('flex-none w-16');
                $row->addContent('v'.$updater->versionCode)->addClass('text-xl text-left flex-1');

            $form->addRow()->addSubmit();
        }

        echo $form->getOutput();
    } else {
        // Go! Start with warning about cutting edge code
        echo Format::alert(__('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.'), 'warning');

        $form = Form::create('action', $session->get('absoluteURL').'/modules/System Admin/updateProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('type', 'cuttingEdge');

        if ($updater->isComposerUpdateRequired()) {
            echo Format::alert('<b>'.__('Composer Update Required').'</b>: '.__('The updater has detected a change in the composer.lock file. In the command line, navigate to your Gibbon base path and run the {composer} command. Visit the {docsLink} page in the docs for more information about using composer.<br/><br/>Once you have updated composer, click {updateLink} to dismiss this message. ', [
                'composer' => '<code class="bg-gray-800 text-white rounded px-1 py-px">composer install --no-dev</code>',
                'docsLink' => Format::link('https://docs.gibbonedu.org/developers/getting-started/developer-workflow/', __('Developer Workflow')),
                'updateLink' => Format::link('./modules/System Admin/updateComposerProcess.php', __('Update')),
            ]), 'error');
        }

        if ($return == 'success0') {
            $form->addRow()->addContent(__('Your Gibbon core is up to date.'))->addClass('py-16 text-center text-gray-600 text-lg');
        } elseif (!$updateRequired) {
            // Instructions on how to update
            $form->setTitle(__('Update Instructions'));
            $form->setDescription(Format::list([
                sprintf(__('You are currently using Cutting Edge Gibbon v%1$s'), $updater->versionCode),
                sprintf(__('Check %1$s to get the latest commits.'), "<a target='_blank' href='https://github.com/GibbonEdu/core'>our GitHub repo</a>"),
                __('Download the latest commits, and unzip it on your computer.'),
                __('Use an FTP client to upload the new files to your server, making sure not to overwrite any additional modules and themes previously added to the system.'),
                __('Reload this page and follow the instructions to update your database to the latest version.'),
            ], 'ol'));

        } elseif ($updateRequired) {
            // Time to update
            $form->setTitle(__('Database Update'));
            $form->setDescription(sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s line %2$s to v%3$s line %4$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $updater->versionDB, $updater->cuttingEdgeCodeLine, $updater->cuttingEdgeVersion, $updater->cuttingEdgeMaxLine).'</b>');

            $row = $form->addRow();
                $row->addContent('v'.$updater->versionDB)
                    ->addClass('text-xl text-right')
                    ->append('<br/><span class="text-xs">'.__('Line').': '.$updater->cuttingEdgeCodeLine.'</span>');
                $row->addContent('<img src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/page_right.png" class="w-6">')->addClass('flex-none w-16');
                $row->addContent('v'.$updater->versionCode)
                    ->addClass('text-xl text-left flex-1')
                    ->append('<br/><span class="text-xs">'.__('Line').': '.$updater->cuttingEdgeMaxLine.'</span>');

            $form->addRow()->addSubmit();

        }
        echo $form->getOutput();
    }

    // INNODB UPGRADE - only displays if an update is required
    if (version_compare($updater->versionCode, '16.0.00', '>=')) {
        $engineUpdate = $container->get(EngineUpdate::class);

        if ($engineUpdate->canMigrate()) {
            $form = Form::create('innoDB', $session->get('absoluteURL').'/modules/System Admin/updateProcess.php?type=InnoDB');
            $form->addHiddenValue('address', $session->get('address'));

            $form->setTitle(__('Database Engine Migration'));
            $form->setDescription(__('Starting from v16, Gibbon is offering installations the option to migrate from MySQL\'s MyISAM engine to InnoDB, as a way to achieve greater reliability and performance.'));
            $output = '';

            if ($updateRequired) {
                $output .= Format::alert(__('Please run the database update, above, before proceeding with the Database Engine Migration.'), 'warning');
            } else {
                $currentEngine = $engineUpdate->checkEngine();

                if ($currentEngine == 'InnoDB') {
                    $output .= Format::alert(sprintf(__('Your current default database engine is: %1$s'), $currentEngine), 'message');
                } else {
                    $output .= Format::alert(sprintf(__('Your current default database engine is: %1$s.'), $currentEngine).' '.__('It is advised that you change your server config so that your default storage engine is set to InnoDB.'), 'warning');
                }

                $output .= Format::alert(sprintf(__('%1$s of your tables are not set to InnoDB.'), $engineUpdate->tablesTotal - $engineUpdate->tablesInnoDB).' <b>'.__('Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), 'warning');

                $form->addRow()->addSubmit();
            }

            $form->setDescription($form->getDescription().$output);

            echo $form->getOutput();
        }
    }
}
