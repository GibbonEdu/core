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

use Gibbon\Module\SystemAdmin\Forms\UpdateForm;
use Gibbon\Contracts\Services\Session as SessionInterface;
use Gibbon\Database\Updater;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Filesystem\VersionCatalog;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4).'/functions.php';
require_once dirname(__DIR__, 4).'/modules/System Admin/src/Forms/UpdateForm.php';

/**
 * @covers \Gibbon\Module\SystemAdmin\Forms\UpdateForm
 */
class UpdateFormTest extends TestCase
{
    public function testCreatesAvailableUpdateActions(): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn(false);
        $updater->method('isUpdateRequired')->willReturn(0);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn([
            '30.0.00' => ['status' => 'Current', 'releaseDate' => '2026-01-01'],
            '31.0.00' => ['status' => 'Current'],
            '32.0.00' => ['status' => 'Current'],
        ]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getNextRelease')->willReturn([
            'version' => '31.0.00',
            'phpVersion' => '8.2',
        ]);
        $versionCatalog->method('getCatalog')->willReturn([
            '29.0.00' => ['changelog' => ['Bug Fixes' => ['A previous fix.']]],
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
            '31.0.00' => ['status' => 'Current', 'changelog' => []],
            '32.0.00' => ['status' => 'Current', 'changelog' => []],
            '33.0.00' => ['status' => 'Development', 'changelog' => []],
        ]);
        $versionCatalog->method('getCatalogVersion')->willReturn('30.0.00');

        $form = $this->createUpdateForm($updater, $versionCatalog, '29.0.00')->createForm($updater, $versionCatalog);

        $output = $form->getOutput();

        $this->assertStringContainsString('updateFilesProcess.php', $output);
        $this->assertStringContainsString('Downgrade to 29.0.00', $output);
        $this->assertStringContainsString('updateRestoreProcess.php', $output);
        $this->assertMatchesRegularExpression(
            '/hx-post="https:\/\/example\.test\/modules\/System Admin\/updateRestoreProcess\.php"/',
            $output
        );
        $this->assertStringContainsString('Installed', $output);
        $this->assertStringContainsString('Update to 31.0.00', $output);
        $this->assertStringContainsString('Update earlier versions first', $output);
        $this->assertStringContainsString('Released On 2026-01-01', $output);
        $this->assertStringNotContainsString('v33.0.00', $output);
    }

    /**
     * @dataProvider phpRequirementProvider
     */
    public function testUpdateButtonFollowsThePhpVersionTheReleaseRequires(array $release, bool $canUpdate): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn(false);
        $updater->method('isUpdateRequired')->willReturn(0);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn([
            '30.0.00' => ['status' => 'Current'],
            '31.0.00' => ['status' => 'Current'],
        ]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getNextRelease')->willReturn(['version' => '31.0.00'] + $release);
        $versionCatalog->method('getCatalog')->willReturn([
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
            '31.0.00' => ['status' => 'Current', 'changelog' => []],
        ]);
        $versionCatalog->method('getCatalogVersion')->willReturn('30.0.00');

        // PHPUnit turns warnings into failures, so a missing phpVersion key must not warn.
        $output = $this->createUpdateForm($updater, $versionCatalog, '')
            ->createForm($updater, $versionCatalog)
            ->getOutput();

        $this->assertSame($canUpdate, str_contains($output, 'Update to 31.0.00'));
        $this->assertSame(!$canUpdate, str_contains($output, 'Requires PHP 99.0 or higher'));
    }

    public static function phpRequirementProvider(): array
    {
        return [
            'newer PHP required' => [['phpVersion' => '99.0'], false],
            'older PHP required' => [['phpVersion' => '8.0'], true],
            'empty requirement' => [['phpVersion' => ''], true],
            'no requirement in the catalog' => [[], true],
        ];
    }

    /**
     * @dataProvider restoreDatabaseVersionProvider
     */
    public function testDowngradeIsOnlyOfferedWhenTheDatabaseIsNotAheadOfTheBackup(?string $databaseVersion, bool $offered): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->versionDB = $databaseVersion;
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn(false);
        $updater->method('isUpdateRequired')->willReturn(0);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn(['30.0.00' => ['status' => 'Current']]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getNextRelease')->willReturn(null);
        $versionCatalog->method('getCatalog')->willReturn([
            '29.0.00' => ['changelog' => []],
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
        ]);
        $versionCatalog->method('getCatalogVersion')->willReturn('30.0.00');

        $output = $this->createUpdateForm($updater, $versionCatalog, '29.0.00')
            ->createForm($updater, $versionCatalog)
            ->getOutput();

        $this->assertSame($offered, str_contains($output, 'Downgrade to 29.0.00'));
        $this->assertSame($offered, str_contains($output, 'hx-confirm='));
    }

    public static function restoreDatabaseVersionProvider(): array
    {
        return [
            'database is newer than the backup' => ['30.0.00', false],
            'database is the same version as the backup' => ['29.0.00', true],
            'database is older than the backup' => ['28.0.00', true],
            'database version is unknown' => [null, true],
        ];
    }

    public function testCheckForUpdatesIsOfferedOnAnInstalledDevelopmentBuildWithNoNewerVersion(): void
    {
        $output = $this->renderCheckForm(true, 0, null, 'Development');

        $this->assertStringContainsString('Check for Updates', $output);
        $this->assertMatchesRegularExpression(
            '/hx-post="https:\/\/example\.test\/modules\/System Admin\/updateCheckProcess\.php"/',
            $output
        );
    }

    /**
     * @dataProvider checkNotOfferedProvider
     */
    public function testCheckForUpdatesIsNotOfferedWhenThereIsNothingToCheck(bool $cuttingEdge, int $updateRequired, ?array $nextRelease, string $status): void
    {
        $output = $this->renderCheckForm($cuttingEdge, $updateRequired, $nextRelease, $status);

        $this->assertStringNotContainsString('updateCheckProcess.php', $output);
        $this->assertStringNotContainsString('Check for Updates', $output);
    }

    public static function checkNotOfferedProvider(): array
    {
        return [
            'not on cutting edge' => [false, 0, null, 'Development'],
            'a newer version can be installed' => [true, 0, ['version' => '31.0.01', 'phpVersion' => '8.2'], 'Development'],
            'a released version, not a development build' => [true, 0, null, 'Current'],
            'the database needs an update first' => [true, 1, null, 'Development'],
        ];
    }

    public function testCheckForUpdatesIsDisabledWhileAnotherUpdateIsRunning(): void
    {
        $updater = $this->createCheckUpdater(true, 0);
        $versionCatalog = $this->createCheckCatalog(null, 'Development');

        $output = $this->createUpdateForm($updater, $versionCatalog)
            ->createForm($updater, $versionCatalog, true)
            ->getOutput();

        $this->assertStringNotContainsString('updateCheckProcess.php', $output);
        $this->assertStringContainsString('Update in Progress', $output);
    }

    private function renderCheckForm(bool $cuttingEdge, int $updateRequired, ?array $nextRelease, string $status): string
    {
        $updater = $this->createCheckUpdater($cuttingEdge, $updateRequired);
        $versionCatalog = $this->createCheckCatalog($nextRelease, $status);

        return $this->createUpdateForm($updater, $versionCatalog)
            ->createForm($updater, $versionCatalog)
            ->getOutput();
    }

    private function createCheckUpdater(bool $cuttingEdge, int $updateRequired): Updater
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '31.0.00';
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn($cuttingEdge);
        $updater->method('isUpdateRequired')->willReturn($updateRequired);

        return $updater;
    }

    private function createCheckCatalog(?array $nextRelease, string $status): VersionCatalog
    {
        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn(['31.0.00' => ['status' => $status]]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getNextRelease')->willReturn($nextRelease);
        $versionCatalog->method('getCatalog')->willReturn(['31.0.00' => ['status' => $status, 'changelog' => []]]);
        $versionCatalog->method('getCatalogVersion')->willReturn('31.0.00');

        return $versionCatalog;
    }

    public function testSuppressesFileUpdateSubmissionWhileBackgroundUpdateIsActive(): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn(false);
        $updater->method('isUpdateRequired')->willReturn(0);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn([
            '30.0.00' => ['status' => 'Current'],
            '31.0.00' => ['status' => 'Current'],
        ]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getNextRelease')->willReturn([
            'version' => '31.0.00',
            'phpVersion' => '8.2',
        ]);
        $versionCatalog->method('getCatalog')->willReturn([
            '29.0.00' => ['changelog' => []],
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
            '31.0.00' => ['status' => 'Current', 'changelog' => []],
        ]);

        $form = $this->createUpdateForm($updater, $versionCatalog, '29.0.00')
            ->createForm($updater, $versionCatalog, true);

        $output = $form->getOutput();

        $this->assertStringNotContainsString('Update to 31.0.00', $output);
        $this->assertStringContainsString('Update in Progress', $output);
        $this->assertStringNotContainsString('Downgrade to 29.0.00', $output);
        $this->assertStringNotContainsString('updateRestoreProcess.php', $output);
    }

    public function testSuppressesDatabaseUpdateSubmissionWhileBackgroundUpdateIsActive(): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->cuttingEdgeCodeLine = 0;
        $updater->cuttingEdgeMaxLine = 0;
        $updater->method('isCuttingEdge')->willReturn(false);
        $updater->method('isUpdateRequired')->willReturn(1);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn([
            '30.0.00' => ['status' => 'Current'],
        ]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getCatalog')->willReturn([
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
        ]);

        $form = $this->createUpdateForm($updater, $versionCatalog)
            ->createForm($updater, $versionCatalog, true);

        $output = $form->getOutput();

        $this->assertStringNotContainsString('Run Database Update', $output);
        $this->assertStringContainsString('Update in Progress', $output);
    }

    public function testDisplaysCuttingEdgeDatabaseUpdate(): void
    {
        $updater = $this->createMock(Updater::class);
        $updater->versionCode = '30.0.00';
        $updater->cuttingEdgeCodeLine = 2;
        $updater->cuttingEdgeMaxLine = 4;
        $updater->method('isCuttingEdge')->willReturn(true);
        $updater->method('isUpdateRequired')->willReturn(1);

        $versionCatalog = $this->createMock(VersionCatalog::class);
        $versionCatalog->method('fetch')->willReturn([
            '30.0.00' => ['status' => 'Development'],
        ]);
        $versionCatalog->method('getChangeLogFromFile')->willReturn([]);
        $versionCatalog->method('getCatalog')->willReturn([
            '30.0.00' => ['status' => 'Development', 'changelog' => []],
        ]);

        $form = $this->createUpdateForm($updater, $versionCatalog)->createForm($updater, $versionCatalog);

        $output = $form->getOutput();

        $this->assertStringContainsString('Cutting Edge', $output);
        $this->assertStringContainsString('Update Database Line 2 to Line 4', $output);
    }

    private function createUpdateForm(Updater $updater, VersionCatalog $versionCatalog, string $backupVersion = ''): UpdateForm
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(function (string $key) {
            return [
                'address' => '/modules/System Admin/update.php',
                'absoluteURL' => 'https://example.test',
            ][$key] ?? '';
        });

        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn($backupVersion);

        return new UpdateForm($session, $settingGateway);
    }
}
