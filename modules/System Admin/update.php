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

use Gibbon\Database\Migrations\EngineUpdate;
use Gibbon\Database\Updater;
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\Filesystem;
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Module\SystemAdmin\Forms\EngineUpdateForm;
use Gibbon\Module\SystemAdmin\Forms\UpdateForm;
use Gibbon\Module\SystemAdmin\SystemUpdateMonitor;
use Gibbon\Module\SystemAdmin\SystemUpdateProgress;
use Gibbon\Services\Format;

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
        'warning1'             => __('Some aspects of your request failed, but others were successful. The elements that failed are shown below:'),
        'error3'               => __('Your request failed because your inputs were invalid, or no update was required.'),
        'errorFiles'           => $errorFiles,
        'errorNoBackup'        => __('The restore could not start because there is no backup of the previous version. Backups are created each time the updater installs a new version.'),
        'errorFilePermissions' => __('Automatic updates are unavailable because the application does not have the required file permissions. Check the file permissions on your server or update manually.'),
        'errorUpdateBusy'      => __('A system update is already in progress. Please wait a few minutes and try again.'),
        'errorUpdateFailed'    => __('Update failed. Check the server logs and try again, or update manually.'),
    ]);

    // Get and display SQL errors
    if ($session->has('systemUpdateError')) {
        echo Format::alert(__('The following SQL statements caused errors:').'<br/>'.implode('<br/>', $session->get('systemUpdateError')));
        $session->forget('systemUpdateError');
    }

    // Compare codebase version (version.php) with database version
    $updater = $container->get(Updater::class);
    $downloader = $container->get(Downloader::class);
    $versionCatalog = $container->get(VersionCatalog::class);

    $updateRequired = $updater->isUpdateRequired();
    $cuttingEdge = $updater->isCuttingEdge();
    $nextRelease = null;

    // Only check for a new release if the codebase is up to date with the database.
    if ($updateRequired <= 0) {
        $nextRelease = $versionCatalog->getNextRelease($updater->versionCode, $cuttingEdge);
    } else {
        $thisRelease = $versionCatalog->getRelease($updater->versionCode);
    }

    $filesystem = $container->get(Filesystem::class);
    $canRunAutomaticFileUpdate = empty($nextRelease) || $filesystem->checkPermissions($downloader->getPermissionPaths());

    // Updater version number and status overview
    echo $page->fetchFromTemplate('updateOverview.twig.html', [
        'versionError'      => empty($versionCatalog->getCatalog()),
        'versionCode'       => $updater->versionCode,
        'versionUpdate'     => !empty($nextRelease),
        'versionDB'         => $updater->versionDB,
        'databaseUpdate'    => !empty($nextRelease) || $updateRequired > 0,
        'cuttingEdge'       => $cuttingEdge,
        'cuttingEdgeCode'   => __('Line').' '.$updater->cuttingEdgeCodeLine,
        'cuttingEdgeUpdate' => $updater->cuttingEdgeMaxLine - $updater->cuttingEdgeCodeLine,
    ]);

    // The latest SystemUpdateProcess log identifies the poll target. The shared
    // progress snapshot is the sole source for displayed state and polling.
    // The monitor first clears any update left behind by a crashed process, so
    // the snapshot read below never reports a dead update as running.
    $updateMonitor = $container->get(SystemUpdateMonitor::class);
    $isUpdateBusy = $updateMonitor->isBusy();
    $processID = $updateMonitor->getProcessID();

    $updateProgressService = $container->get(SystemUpdateProgress::class);
    $updateProgress = $updateProgressService->get();

    $isUpdateActive = $isUpdateBusy && !empty($processID) && $updateProgress['status'] === 'working';
    $hasUpdateFailure = !empty($processID) && $updateProgress['status'] === 'failed';

    $progress = $updateProgress['progress'];
    if (!is_int($progress) || $progress < 0 || $progress > 100) {
        $progress = null;
    }

    $progressEndpoint = $session->get('absoluteURL').'/modules/System Admin/updateProgressAjax.php';
    $pollUrl = $isUpdateActive
        ? $progressEndpoint.'?gibbonLogID='.rawurlencode((string) $processID)
        : $progressEndpoint;

    echo $page->fetchFromTemplate('updateProgress.twig.html', [
        'isDormant'          => !$isUpdateActive ,
        'isPolling'          => $isUpdateActive,
        'pollUrl'            => $pollUrl,
        'isFailure'          => $hasUpdateFailure,
        'lifecycleLabel'     => $updateProgressService->getStatusLabel($updateProgress['status']) ?? '',
        'typeLabel'          => $updateProgressService->getTypeLabel($updateProgress['type']) ?? '',
        'stageLabel'         => $updateProgressService->getStageLabel($updateProgress['stage']) ?? '',
        'message'            => $hasUpdateFailure ? __('Update failed. Check the server logs and try again, or update manually.') : '',
        'progress'           => $progress,
        'unavailableMessage' => __('Could not connect to the GibbonEdu repository. Please wait a few minutes and try again, or update manually.'),
    ]);

    // Update status message
    if ($updateRequired > 0) {
        echo Format::alert(__('You have updated your Gibbon code to a new version, and are ready to update your database from {versionDB} to {versionCode}. This operation cannot be undone: backup your entire database prior to running the update!', ['versionDB' => $updater->versionDB, 'versionCode' => $updater->versionCode]), 'warning');
    } elseif (!$canRunAutomaticFileUpdate) {
        echo Format::alert(__('Automatic updates are unavailable because the application does not have the required file permissions. Check the file permissions on your server or update manually.'), 'error');
    } elseif (empty($nextRelease) && !$cuttingEdge && $updateRequired === -1) {
        echo Format::alert(__('An error has occurred determining the version of the system you are using.'), 'error');
    } elseif (empty($nextRelease) && !empty($versionCatalog->getCatalog())) {
        echo Format::alert(__('Your Gibbon core is up to date.'), 'success');
    } elseif (empty($nextRelease)) {
        echo Format::alert(__('Version check failed').'. '.__('Could not connect to the GibbonEdu repository. Please wait a few minutes and try again, or update manually.'), 'error');
    } else {
        echo Format::alert(__('A new version of Gibbon is available. Please review the changelog below, backup before installing, and update your system.').'<br><br><b>'.__('Note').'</b>: '.__('The auto-updater may fail to update successfully depending on your system settings and file permissions. A failed auto-update will rollback to the previously installed version. When in doubt, please follow the documentation to update manually.'), 'message');
    }

    // Cutting edge warning and composer check
    if ($cuttingEdge) {
        echo Format::alert(__('Your system is set up to run Cutting Edge code, which may or may not be as reliable as regular release code. Backup before installing, and avoid using cutting edge in production.'), 'exception');

        if ($updater->isComposerUpdateRequired()) {
            echo Format::alert('<b>'.__('Composer Update Required').'</b>: '.__('The updater has detected a change in the composer.lock file. In the command line, navigate to your Gibbon base path and run the {composer} command. Visit the {docsLink} page in the docs for more information about using composer.<br/><br/>Once you have updated composer, click {updateLink} to dismiss this message. ', [
                'composer' => '<code class="bg-gray-800 text-white rounded px-1 py-px">composer install --no-dev</code>',
                'docsLink' => Format::link('https://docs.gibbonedu.org/development/getting-started/developer-workflow', __('Developer Workflow')),
                'updateLink' => Format::link('./modules/System Admin/updateComposerProcess.php', __('Update')),
            ]), 'error');
        }
    }
    
    // UPDATE FORM - displays versions and changelog, and an update button if possible
    $form = $container->get(UpdateForm::class)->createForm(
        $updater,
        $versionCatalog,
        $isUpdateActive,
        $canRunAutomaticFileUpdate
    );
    echo $form->getOutput();

    // INNODB UPGRADE - only displays if an update is required
    if (version_compare($updater->versionCode, '16.0.00', '>=')) {
        $engineUpdate = $container->get(EngineUpdate::class);

        if ($engineUpdate->canMigrate()) {
            $form = $container->get(EngineUpdateForm::class)->createForm($engineUpdate, $updater);
            echo $form->getOutput();
        }
    }
}
