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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Filesystem\VersionCatalog;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Update'));

    // Include the file-update stage in the error message when present
    $errorFiles = __('Your request failed because the file update could not be completed. Check the version catalog, package checksum, disk space, file permissions, and that the PHP Zip extension is enabled.');
    if ($session->has('fileUpdateStage')) {
        $errorFiles .= ' ('.$session->get('fileUpdateStage').')';
        $session->forget('fileUpdateStage');
    }
    $page->return->addReturns([
        'warning1'           => __('Some aspects of your request failed, but others were successful. The elements that failed are shown below:'),
        'error3'             => __('Your request failed because your inputs were invalid, or no update was required.'),
        'errorFiles'         => $errorFiles,
        'errorFileDirectory' => __('Your request failed because the file update directory is missing or not writeable. Check the file permissions on your server.'),
        'errorFileBusy'      => __('Your request failed because a file update is already in progress. Please wait a few minutes and try again.'),
    ]);

    // Get and display SQL errors
    if ($session->has('systemUpdateError')) {
        echo Format::alert(__('The following SQL statements caused errors:').'<br/>'.implode('<br/>', $session->get('systemUpdateError')));
        $session->forget('systemUpdateError');
    }

    // Compare codebase version (version.php) with database version
    $updater = $container->get(Updater::class);
    $versionCatalog = $container->get(VersionCatalog::class);

    $updateRequired = $updater->isUpdateRequired();
    $cuttingEdge = $updater->isCuttingEdge();
    $updatePath = '';
    $nextRelease = false;
    $backupVersion = $container->get(SettingGateway::class)->getSettingByScope('System Admin', 'fileUpdaterBackup');

    // $table->setDescription(__('This page allows you to semi-automatically update your Gibbon installation to a new version. You need to take care of the file updates, and based on the new files, Gibbon will do the database upgrades.'));

    // 1) DB update  2) else file update if a newer version exists  3) else no update needed, files & DB up to date
    if ($updateRequired > 0) {
        $updatePath = $session->get('absoluteURL').'/modules/System Admin/updateProcess.php';

        // $form = Form::create('action', $session->get('absoluteURL').'/modules/System Admin/updateProcess.php');
        // $form->addHiddenValue('address', $session->get('address'));
        // $form->addHiddenValue('type', $cuttingEdge ? 'cuttingEdge' : 'regularRelease');
        // $form->setTitle(__('Database Update'));

        // if ($cuttingEdge) {
        //     $form->setDescription(sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s line %2$s to v%3$s line %4$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $updater->versionDB, $updater->cuttingEdgeCodeLine, $updater->cuttingEdgeVersion, $updater->cuttingEdgeMaxLine).'</b>');

        //     $row = $form->addRow();
        //         $row->addContent('v'.$updater->versionDB)
        //             ->addClass('text-xl text-right')
        //             ->append('<br/><span class="text-xs">'.__('Line').': '.$updater->cuttingEdgeCodeLine.'</span>');
        //         $row->addContent('<img src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/page_right.png" class="w-6">')->addClass('flex-none w-16');
        //         $row->addContent('v'.$updater->versionCode)
        //             ->addClass('text-xl text-left flex-1')
        //             ->append('<br/><span class="text-xs">'.__('Line').': '.$updater->cuttingEdgeMaxLine.'</span>');
        // } else {
        //     $form->setDescription(sprintf(__('It seems that you have updated your Gibbon code to a new version, and are ready to update your database from v%1$s to v%2$s. <b>Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), $updater->versionDB, $updater->versionCode).'</b>');

        //     $row = $form->addRow();
        //         $row->addContent('v'.$updater->versionDB)->addClass('text-xl text-right');
        //         $row->addContent('<img src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/page_right.png" class="w-6">')->addClass('flex-none w-16');
        //         $row->addContent('v'.$updater->versionCode)->addClass('text-xl text-left flex-1');
        // }

        // $form->addRow()->addSubmit();
        // echo $form->getOutput();
    } else {
        $nextRelease = $versionCatalog->getNextRelease($updater->versionCode, $cuttingEdge);
        $updatePath = $session->get('absoluteURL').'/modules/System Admin/updateFilesProcess.php';
    }

    // Updater version number and status overview
    echo $page->fetchFromTemplate('updateOverview.twig.html', [
        'versionCode'     => $updater->versionCode,
        'versionUpdate'   => !empty($nextRelease),
        'versionDB'       => $updater->versionDB,
        'databaseUpdate'  => !empty($nextRelease) || $updateRequired > 0,
        'cuttingEdge'     => $cuttingEdge,
        'cuttingEdgeCode' => __('Line').' '.$updater->cuttingEdgeCodeLine,
        'cuttingEdgeUpdate' => $updater->cuttingEdgeMaxLine - $updater->cuttingEdgeCodeLine,
    ]);

    // Update status message
    if (empty($nextRelease) && !$cuttingEdge && $updateRequired === -1) {
        echo Format::alert(__('An error has occurred determining the version of the system you are using.'), 'error');
    } elseif (empty($nextRelease)) {
        echo Format::alert(__('Your Gibbon core is up to date.'), 'success');
    } else {
        echo Format::alert(__('A new version of Gibbon is available. Please review the changelog below, backup before installing, and update your system.'), 'message');
        echo Format::alert('<b>'.__('Note').'</b>: '.__('The auto-updater may fail to update successfully depending on your system settings and file permissions. A failed auto-update will rollback to the previously installed version. When in doubt, please follow the documentation to update manually.'), 'message');
    }

    // Cutting edge warning and composer check
    if ($cuttingEdge) {
        echo Format::alert(__('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.'), 'warning');

        if ($updater->isComposerUpdateRequired()) {
            echo Format::alert('<b>'.__('Composer Update Required').'</b>: '.__('The updater has detected a change in the composer.lock file. In the command line, navigate to your Gibbon base path and run the {composer} command. Visit the {docsLink} page in the docs for more information about using composer.<br/><br/>Once you have updated composer, click {updateLink} to dismiss this message. ', [
                'composer' => '<code class="bg-gray-800 text-white rounded px-1 py-px">composer install --no-dev</code>',
                'docsLink' => Format::link('https://docs.gibbonedu.org/development/getting-started/developer-workflow', __('Developer Workflow')),
                'updateLink' => Format::link('./modules/System Admin/updateComposerProcess.php', __('Update')),
            ]), 'error');
        }
    }

    // Fetch the remote version catalog and merge it with the changelog from the local file
    $versions = $versionCatalog->fetch($updater->versionCode);
    $changelog = $versionCatalog->getChangeLogFromFile();
    $versionCatalog->mergeCatalog($changelog);

    if (!empty($changelog)) {
        $form = Form::createBlank('fileUpdate', $updatePath);
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('type', $cuttingEdge ? 'cuttingEdge' : 'regularRelease');
        $form->addHiddenValue('confirm', 'Y');

        // $form->setTitle(__('Changelog'));

        foreach ($versionCatalog->getCatalog() as $version => $data) {
            $categories = $data['changelog'] ?? [];

            $col = $form->addRow()->setClass('border bg-gray-50 rounded-lg shadow-sm p-6 mb-4')->addColumn();

            $versionTag = $releaseDate = '';

            if (!empty($versions[$version])) {
                if (version_compare($version, $updater->versionCode, '>')) {
                    $versionTag = Format::tag(__('Update Available'), 'message ml-4 align-middle');
                }

                if (version_compare($version, $updater->versionCode, '=')) {
                    $versionTag = !empty($nextRelease) 
                        ? Format::tag(__('Update Required'), 'warning ml-4 align-middle')
                        : Format::tag(__('Current'), 'success ml-4 align-middle');
                }

                if ($versions[$version]['status'] == 'Development' && $cuttingEdge) {
                    $versionTag .= Format::tag('Cutting Edge', 'warning ml-4 align-middle');
                }

                $releaseDate = !empty($versions[$version]['releaseDate'])
                    ? __('Released On {date}', ['date' => $versions[$version]['releaseDate']]) 
                    : '';
            }

            $row = $col->addColumn()->setClass('flex justify-between items-center');
            $row->addContent('v'.$version.$versionTag)->addClass('text-2xl font-mono');

            if ($updateRequired > 0 && $updater->versionCode == $version) {
                if ($cuttingEdge && $updater->cuttingEdgeMaxLine > $updater->cuttingEdgeCodeLine) {
                    $row->addSubmit(__('Update Line {code} to Line {max}', [
                        'code' => $updater->cuttingEdgeCodeLine,
                        'max' => $updater->cuttingEdgeMaxLine]
                    ))->addClass('text-sm text-gray-600');
                } else {
                    $row->addSubmit(__('Run Database Update'))->addClass('text-sm text-gray-600');
                }
            } 
            elseif (!empty($nextRelease) && $nextRelease['version'] == $version) {
                $row->addSubmit(__('Update to {version}', ['version' => $version]))->addClass('text-sm text-gray-600');
            }
            else if (version_compare($version, $updater->versionCode, '>'))
            {
               $row->addButton(__('Update Available', ['version' => $version]))->addClass('text-sm text-gray-600')->setAttribute('disabled', 'disabled')->setAttribute('title', __('Update earlier versions first')); 
            }
            else if ($version == $updater->versionCode) {
                $row->addButton(__('Installed', ['version' => $version]))->addClass('text-sm text-gray-600')->setAttribute('disabled', 'disabled'); 
            }
            else if ($backupVersion == $version)
            {
                $restorePath = $session->get('absoluteURL').'/modules/System Admin/updateRestoreProcess.php';
                $row->addSubmit(__('Downgrade to {version}', ['version' => $version]))->addClass(' rounded-md')->setAttribute('onclick', 'document.getElementById("fileUpdate").action="'.$restorePath.'";' )->setColor('gray');
            }

            $col->onlyIf($releaseDate)->addContent($releaseDate)->addClass('mt-1 text-sm text-gray-600');

            if (version_compare($version, $updater->versionCode, '>') && $nextRelease['version'] != $version) {
                $col = $col->addDetails()->summary(__('View Changelog'), 'text-gray-600 text-sm')->setClass('mt-2 -mb-2')->addColumn()->setClass('flex justify-between items-start');
            }

            foreach ($categories as $category => $changes) {
                $col->addContent($category)->addClass('text-base font-bold mt-6');

                $col->addContent(Format::list($changes, 'ul', 'text-base'));

            }
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

            if ($updateRequired > 0) {
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
