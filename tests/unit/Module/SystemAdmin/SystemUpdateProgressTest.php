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

declare(strict_types=1);

namespace Gibbon\Module\SystemAdmin;

use Gibbon\Domain\System\SettingGateway;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProgress.php';

/**
 * @covers \Gibbon\Module\SystemAdmin\SystemUpdateProgress
 */
class SystemUpdateProgressTest extends TestCase
{
    public function testBeginWritesTheInitialFourFieldSnapshot(): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->expects($this->once())
            ->method('updateSettingByScope')
            ->with(
                'System Admin',
                'backgroundUpdateProgress',
                $this->callback(function (string $value): bool {
                    $this->assertSame([
                        'type' => 'file',
                        'stage' => null,
                        'status' => 'working',
                        'progress' => null,
                    ], json_decode($value, true, 512, JSON_THROW_ON_ERROR));

                    return true;
                })
            )
            ->willReturn(true);

        (new SystemUpdateProgress($settingGateway))->begin('file');
    }

    public function testLabelAccessorsReturnTranslatedLabelsAndNullForUnknownCodes(): void
    {
        $progress = new SystemUpdateProgress($this->createMock(SettingGateway::class));

        $this->assertSame(__('Database update in progress.'), $progress->getTypeLabel('database'));
        $this->assertSame(__('System restore in progress.'), $progress->getTypeLabel('restore'));
        $this->assertSame(__('Installing update…'), $progress->getStageLabel('installing'));
        $this->assertSame(__('Restoring files…'), $progress->getStageLabel('restoring'));
        $this->assertNull($progress->getStatusLabel('unknown'));
        $this->assertNull($progress->getTypeLabel(null));
        $this->assertNull($progress->getStageLabel('unknown'));
    }

    public function testUpdateWritesTheCurrentTypeAndValidStageSnapshot(): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn(json_encode([
            'type' => 'database',
            'stage' => null,
            'status' => 'working',
            'progress' => null,
        ], JSON_THROW_ON_ERROR));
        $settingGateway->expects($this->once())
            ->method('updateSettingByScope')
            ->with(
                'System Admin',
                'backgroundUpdateProgress',
                $this->callback(function (string $value): bool {
                    $this->assertSame([
                        'type' => 'database',
                        'stage' => 'installing',
                        'status' => 'working',
                        'progress' => 70,
                    ], json_decode($value, true, 512, JSON_THROW_ON_ERROR));

                    return true;
                })
            )
            ->willReturn(true);

        (new SystemUpdateProgress($settingGateway))->update('installing', 'working', 70);
    }

    /**
     * @dataProvider invalidSnapshotProvider
     */
    public function testGetReturnsSafeDefaultsForMissingMalformedOrInvalidCodes(mixed $value): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn($value);

        $this->assertSame([
            'type' => null,
            'stage' => null,
            'status' => 'idle',
            'progress' => null,
        ], (new SystemUpdateProgress($settingGateway))->get());
    }

    public static function invalidSnapshotProvider(): array
    {
        return [
            'missing setting' => [false],
            'malformed JSON' => ['{'],
            'non-object JSON' => ['"working"'],
            'invalid type code' => [json_encode([
                'type' => 'unknown',
                'stage' => 'preparing',
                'status' => 'working',
                'progress' => 10,
            ], JSON_THROW_ON_ERROR)],
            'invalid stage code' => [json_encode([
                'type' => 'file',
                'stage' => 'unknown',
                'status' => 'working',
                'progress' => 10,
            ], JSON_THROW_ON_ERROR)],
            'invalid status code' => [json_encode([
                'type' => 'file',
                'stage' => 'preparing',
                'status' => 'unknown',
                'progress' => 10,
            ], JSON_THROW_ON_ERROR)],
        ];
    }

    /**
     * @dataProvider invalidProgressProvider
     */
    public function testGetRemovesOutOfRangeProgress(mixed $progress): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn(json_encode([
            'type' => 'file',
            'stage' => 'installing',
            'status' => 'working',
            'progress' => $progress,
        ], JSON_THROW_ON_ERROR));

        $this->assertSame([
            'type' => 'file',
            'stage' => 'installing',
            'status' => 'working',
            'progress' => null,
        ], (new SystemUpdateProgress($settingGateway))->get());
    }

    public static function invalidProgressProvider(): array
    {
        return [
            'below zero' => [-1],
            'above one hundred' => [101],
            'string number' => ['70'],
        ];
    }

    /**
     * @dataProvider invalidWriteProvider
     */
    public function testWriteMethodsRejectInvalidCodesAndProgress(string $method, array $arguments): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->expects($this->never())->method('updateSettingByScope');
        $progress = new SystemUpdateProgress($settingGateway);

        $this->expectException(\InvalidArgumentException::class);

        $progress->{$method}(...$arguments);
    }

    public static function invalidWriteProvider(): array
    {
        return [
            'invalid type' => ['begin', ['unknown']],
            'invalid stage' => ['update', ['unknown', 'working', 10]],
            'invalid status' => ['update', ['preparing', 'unknown', 10]],
            'below range' => ['update', ['preparing', 'working', -1]],
            'above range' => ['update', ['preparing', 'working', 101]],
        ];
    }
}
