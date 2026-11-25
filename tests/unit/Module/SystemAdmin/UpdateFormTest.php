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
        $versionCatalog->method('getNextRelease')->willReturn(['version' => '31.0.00']);
        $versionCatalog->method('getCatalog')->willReturn([
            '29.0.00' => ['changelog' => ['Bug Fixes' => ['A previous fix.']]],
            '30.0.00' => ['status' => 'Current', 'changelog' => []],
            '31.0.00' => ['status' => 'Current', 'changelog' => []],
            '32.0.00' => ['status' => 'Current', 'changelog' => []],
            '33.0.00' => ['status' => 'Development', 'changelog' => []],
        ]);

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
        $versionCatalog->method('getNextRelease')->willReturn(['version' => '31.0.00']);
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
