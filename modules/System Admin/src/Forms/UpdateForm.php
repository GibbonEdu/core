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

namespace Gibbon\Module\SystemAdmin\Forms;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Database\Updater;
use Gibbon\Forms\Layout\Column;
use Gibbon\Forms\Layout\Row;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;

/**
 * Builds the available system update actions and their changelogs.
 *
 * @version v30
 * @since   v30
 */
class UpdateForm
{
    private Session $session;
    private Updater $updater;
    private VersionCatalog $versionCatalog;
    private string $backupVersion;
    private bool $isUpdateActive = false;
    private bool $canRunAutomaticFileUpdate = true;

    /**
     * @param Session $session
     * @param SettingGateway $settingGateway
     * @return void
     */
    public function __construct(Session $session, SettingGateway $settingGateway)
    {
        $this->session = $session;
        $this->backupVersion = (string) $settingGateway->getSettingByScope('System Admin', 'fileUpdaterBackup');
    }

    /**
     * @param Updater $updater
     * @param VersionCatalog $versionCatalog
     * @param bool $isUpdateActive Whether a background file or database update is active.
     * @param bool $canRunAutomaticFileUpdate Whether the automatic core-file update can write its required paths.
     * @return Form
     */
    public function createForm(
        Updater $updater,
        VersionCatalog $versionCatalog,
        bool $isUpdateActive = false,
        bool $canRunAutomaticFileUpdate = true
    ): Form
    {
        $this->updater = $updater;
        $this->versionCatalog = $versionCatalog;
        $this->isUpdateActive = $isUpdateActive;
        $this->canRunAutomaticFileUpdate = $canRunAutomaticFileUpdate;

        $versions = $versionCatalog->fetch($updater->versionCode);
        $changelog = $versionCatalog->getChangeLogFromFile();
        $versionCatalog->mergeCatalog($changelog);

        $updateRequired = $this->updater->isUpdateRequired() > 0;
        $cuttingEdge = $this->updater->isCuttingEdge();
        
        // Check for database updates first, otherwise check for file updates
        if ($updateRequired) {
            $action = $this->session->get('absoluteURL').'/modules/System Admin/updateProcess.php';
            $nextRelease = null;
        } else {
            $action = $this->session->get('absoluteURL').'/modules/System Admin/updateFilesProcess.php';
            $nextRelease = $versionCatalog->getNextRelease($updater->versionCode, $cuttingEdge);
        }
        
        $form = Form::createBlank('fileUpdate', $action);
        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('type', $this->updater->isCuttingEdge() ? 'cuttingEdge' : 'regularRelease');
        $form->addHiddenValue('confirm', 'Y');

        foreach ($this->versionCatalog->getCatalog() as $version => $release) {
            if (!is_array($release) || $this->shouldHideRelease($release, $cuttingEdge)) {
                continue;
            }

            $categories = $release['changelog'] ?? [];
            $column = $form->addRow()
                ->setClass('border bg-gray-50 rounded-lg shadow-sm p-6 mb-4')
                ->addColumn();

            $this->addReleaseHeading($column, $version, $versions, $nextRelease, $updateRequired, $cuttingEdge);
            $this->addReleaseDate($column, $version, $versions);

            $changelogColumn = $this->getChangelogColumn($column, $version, $nextRelease);
            $this->addChangelog($changelogColumn, is_array($categories) ? $categories : []);
        }

        return $form;
    }

    /**
     * Add the release heading with version number, status tag, and action button
     *
     * @param Column $column
     * @param string $version
     * @param array $versions
     * @param array|null $nextRelease
     * @param bool $updateRequired
     * @param bool $cuttingEdge
     * @return void
     */
    private function addReleaseHeading(Column $column, string $version, array $versions, ?array $nextRelease, bool $updateRequired, bool $cuttingEdge): void
    {
        $row = $column->addColumn()->setClass('flex justify-between items-center');
        $row->addContent('v'.$version.$this->getVersionTag($version, $versions, $nextRelease, $cuttingEdge))
            ->addClass('text-2xl font-mono');

        $this->addReleaseAction($row, $version, $nextRelease, $updateRequired, $cuttingEdge);
    }

    /**
     * Get the version tag for the release, indicating if it's current, out of date, or cutting edge
     *
     * @param string $version
     * @param array $versions
     * @param array|null $nextRelease
     * @param bool $cuttingEdge
     * @return string
     */
    private function getVersionTag(string $version, array $versions, ?array $nextRelease, bool $cuttingEdge): string
    {
        $remoteRelease = $versions[$version] ?? null;
        if (!is_array($remoteRelease)) {
            return '';
        }

        $versionTag = '';
        if (version_compare($version, $this->updater->versionCode, '>')) {
            $versionTag = Format::tag(__('Update Available'), 'message ml-4 align-middle');
        }

        if (version_compare($version, $this->updater->versionCode, '=')) {
            $versionTag = $nextRelease !== null
                ? Format::tag(__('Out of date'), 'warning ml-4 align-middle')
                : Format::tag(__('Current'), 'success ml-4 align-middle');
        }

        if (($remoteRelease['status'] ?? '') === 'Development' && $cuttingEdge) {
            $versionTag .= Format::tag('Cutting Edge', 'exception ml-4 align-middle');
        }

        return $versionTag;
    }

    /**
     * Add the appropriate action button for the release, based on the current system state
     *
     * @param Row $row
     * @param string $version
     * @param array|null $nextRelease
     * @param bool $updateRequired
     * @param bool $cuttingEdge
     * @return void
     */
    private function addReleaseAction(Row $row, string $version, ?array $nextRelease, bool $updateRequired, bool $cuttingEdge): void
    {
        $isBackgroundEligible = $this->isDatabaseUpdateRelease($version, $updateRequired)
            || $this->isNextRelease($version, $nextRelease);
        // Only offer a restore that would not leave the database ahead of the restored files
        $isRestoreRelease = $version === $this->backupVersion
            && version_compare($version, (string) $this->updater->versionDB, '>=');
        $isHigherVersion = version_compare($version, $this->updater->versionCode, '>');

        if ($this->isUpdateActive && ($isBackgroundEligible || $isRestoreRelease)) {
            $row->addButton(__('Update in Progress'))
                ->addClass('text-sm text-gray-600')
                ->setAttribute('disabled', 'disabled');
            return;
        }

        if ($this->isDatabaseUpdateRelease($version, $updateRequired)) {
            $this->addDatabaseUpdateSubmit($row, $cuttingEdge);
            return;
        }

        if ($version === $this->updater->versionCode) {
            $row->addButton(__('Installed', ['version' => $version]))
                ->addClass('text-sm text-gray-600')
                ->setAttribute('disabled', 'disabled');
            return;
        }

        if (!$this->canRunAutomaticFileUpdate && ($isHigherVersion || $isRestoreRelease)) {
            $row->addButton(__('Unavailable'))
                ->addClass('text-sm text-gray-600')
                ->setAttribute('disabled', 'disabled');
            return;
        }

        if (empty($this->versionCatalog->getCatalogVersion()) && ($isHigherVersion || $isRestoreRelease)) {
            $row->addButton(__('Unavailable'))
                ->addClass('text-sm text-gray-600')
                ->setAttribute('disabled', 'disabled');
            return;
        }

        if ($this->isNextRelease($version, $nextRelease)) {
            $requiredPhp = (string) ($nextRelease['phpVersion'] ?? '');
            if ($requiredPhp !== '' && version_compare(phpversion(), $requiredPhp, '<')) {
                $row->addButton(__('Requires {version} or higher', ['version' => 'PHP '.$requiredPhp]))
                    ->addClass('text-sm text-gray-600')
                    ->setAttribute('disabled', 'disabled');
                return;
            } else {
                $submit = $row->addSubmit(__('Update to {version}', ['version' => $version]))
                    ->addClass('text-sm text-gray-600');
                $this->enableBackgroundSubmission(
                    $submit,
                    $this->session->get('absoluteURL').'/modules/System Admin/updateFilesProcess.php'
                );
                return;
            }
        }

        if ($isHigherVersion) {
            $row->addButton(__('Update Available', ['version' => $version]))
                ->addClass('text-sm text-gray-600')
                ->setAttribute('disabled', 'disabled')
                ->setAttribute('title', __('Update earlier versions first'));
            return;
        }

        if ($isRestoreRelease) {
            $restorePath = $this->session->get('absoluteURL').'/modules/System Admin/updateRestoreProcess.php';
            $submit = $row->addSubmit(__('Downgrade to {version}', ['version' => $version]))
                ->addClass('rounded-md')
                ->setAttribute('formaction', $restorePath)
                ->setAttribute('hx-confirm', __('Replace your current files with the previous version? Your database will not be changed.'))
                ->setColor('gray');
            $this->enableBackgroundSubmission($submit, $restorePath);
        }
    }

    /**
     * Add the database update submit button with cutting edge line information if applicable
     *
     * @param Row $row
     * @param bool $cuttingEdge
     * @return void
     */
    private function addDatabaseUpdateSubmit(Row $row, bool $cuttingEdge): void
    {
        $hasPendingCuttingEdgeLines = $cuttingEdge
            && $this->updater->cuttingEdgeMaxLine > $this->updater->cuttingEdgeCodeLine;

        if ($hasPendingCuttingEdgeLines) {
            $submit = $row->addSubmit(__('Update Database Line {code} to Line {max}', [
                'code' => $this->updater->cuttingEdgeCodeLine,
                'max' => $this->updater->cuttingEdgeMaxLine,
            ]))->addClass('text-sm text-gray-600');
            $this->enableBackgroundSubmission(
                $submit,
                $this->session->get('absoluteURL').'/modules/System Admin/updateProcess.php'
            );
            return;
        }

        $submit = $row->addSubmit(__('Run Database Update'))->addClass('text-sm text-gray-600');
        $this->enableBackgroundSubmission(
            $submit,
            $this->session->get('absoluteURL').'/modules/System Admin/updateProcess.php'
        );
    }

    /**
     * Configure a background update or restore button for an HTMX submission.
     *
     * InnoDB controls keep their synchronous native form behavior.
     *
     * @param object $submit
     * @param string $action
     * @return void
     */
    private function enableBackgroundSubmission(object $submit, string $action): void
    {
        $submit
            ->setAttribute('hx-post', $action)
            ->setAttribute('hx-include', 'closest form')
            ->setAttribute('hx-swap', 'none');
    }

    /**
     * Add the release date if available
     *
     * @param Column $column
     * @param string $version
     * @param array $versions
     * @return void
     */
    private function addReleaseDate(Column $column, string $version, array $versions): void
    {
        $releaseDate = $versions[$version]['releaseDate'] ?? '';
        if (empty($releaseDate)) {
            return;
        }

        $column->addContent(__('Released On {date}', ['date' => $releaseDate]))
            ->addClass('mt-1 text-sm text-gray-600');
    }

    /**
     * Add formatted changelog content
     *
     * @param Column $column
     * @param array $categories
     * @return void
     */
    private function addChangelog(Column $column, array $categories): void
    {
        foreach ($categories as $category => $changes) {
            if (!is_array($changes)) {
                continue;
            }

            $column->addContent((string) $category)->addClass('text-base font-bold mt-6');
            $column->addContent(Format::list($changes, 'ul', 'text-base'));
        }
    }

    /**
     * Get the changelog column, adding details if earlier versions have changelog data.
     *
     * @param Column $column
     * @param string $version
     * @param array<string, mixed>|null $nextRelease
     * @return Column
     */
    private function getChangelogColumn(Column $column, string $version, ?array $nextRelease): Column
    {
        $hasEarlierChangelog = version_compare($version, $this->updater->versionCode, '>')
            && !$this->isNextRelease($version, $nextRelease);

        if (!$hasEarlierChangelog) {
            return $column;
        }

        return $column->addDetails()
            ->summary(__('View Changelog'), 'text-gray-600 text-sm')
            ->setClass('mt-2 -mb-2')
            ->addColumn()
            ->setClass('flex justify-between items-start');
    }

    /**
     * @param array $release
     * @param bool $cuttingEdge
     * @return bool
     */
    private function shouldHideRelease(array $release, bool $cuttingEdge): bool
    {
        return ($release['status'] ?? '') === 'Development' && !$cuttingEdge;
    }

    /**
     * @param string $version
     * @param bool $updateRequired
     * @return bool
     */
    private function isDatabaseUpdateRelease(string $version, bool $updateRequired): bool
    {
        return $updateRequired && $version === $this->updater->versionCode;
    }

    /**
     * @param string $version
     * @param array|null $nextRelease
     * @return bool
     */
    private function isNextRelease(string $version, ?array $nextRelease): bool
    {
        return $nextRelease !== null && ($nextRelease['version'] ?? '') === $version;
    }
}
