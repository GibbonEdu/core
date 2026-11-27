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

require_once dirname(__DIR__, 4).'/functions.php';
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
                    $snapshot = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $this->assertIsInt($snapshot['updated']);
                    unset($snapshot['updated']);
                    $this->assertSame([
                        'type' => 'file',
                        'stage' => null,
                        'status' => 'working',
                        'progress' => null,
                    ], $snapshot);

                    return true;
                })
            )
            ->willReturn(true);

        (new SystemUpdateProgress($settingGateway))->begin('file');
    }

    public function testEveryWriteRecordsTheCurrentTimeAsAHeartbeat(): void
    {
        $written = null;
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturnCallback(function () use (&$written) {
            return $written;
        });
        $settingGateway->method('updateSettingByScope')->willReturnCallback(function ($scope, $name, $value) use (&$written) {
            $written = $value;

            return true;
        });

        $progress = new SystemUpdateProgress($settingGateway);
        $this->assertNull($progress->getUpdatedAt());

        $before = time();
        $progress->begin('file');
        $this->assertGreaterThanOrEqual($before, $progress->getUpdatedAt());
        $this->assertLessThanOrEqual(time(), $progress->getUpdatedAt());

        $written = json_encode(['type' => 'file', 'stage' => null, 'status' => 'working', 'progress' => null, 'updated' => 1]);
        $progress->update('installing', 'working', 70);
        $this->assertGreaterThanOrEqual($before, $progress->getUpdatedAt());

        $written = json_encode(['type' => 'file', 'stage' => null, 'status' => 'working', 'progress' => null, 'updated' => 1]);
        $progress->fail('installing');
        $this->assertGreaterThanOrEqual($before, $progress->getUpdatedAt());
    }

    /**
     * @dataProvider invalidHeartbeatProvider
     */
    public function testGetUpdatedAtIgnoresMissingOrInvalidTimestamps(mixed $value): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn($value);

        $this->assertNull((new SystemUpdateProgress($settingGateway))->getUpdatedAt());
    }

    public static function invalidHeartbeatProvider(): array
    {
        return [
            'missing setting' => [false],
            'malformed JSON' => ['{'],
            'non-object JSON' => ['"working"'],
            'legacy snapshot without a timestamp' => [json_encode(['status' => 'working'])],
            'string timestamp' => [json_encode(['updated' => '1700000000'])],
            'zero timestamp' => [json_encode(['updated' => 0])],
            'negative timestamp' => [json_encode(['updated' => -5])],
        ];
    }

    public function testGetKeepsTheFourFieldShapeAndHidesTheHeartbeat(): void
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturn(json_encode([
            'type' => 'file',
            'stage' => 'installing',
            'status' => 'working',
            'progress' => 70,
            'updated' => 1700000000,
        ], JSON_THROW_ON_ERROR));

        $this->assertSame([
            'type' => 'file',
            'stage' => 'installing',
            'status' => 'working',
            'progress' => 70,
        ], (new SystemUpdateProgress($settingGateway))->get());
    }

    public function testLabelAccessorsReturnTranslatedLabelsAndNullForUnknownCodes(): void
    {
        $progress = new SystemUpdateProgress($this->createMock(SettingGateway::class));

        $this->assertSame(__('Database update in progress.'), $progress->getTypeLabel('database'));
        $this->assertSame(__('File system restore in progress.'), $progress->getTypeLabel('restore'));
        $this->assertSame(__('Installing update…'), $progress->getStageLabel('installing'));
        $this->assertSame(__('Restoring files…'), $progress->getStageLabel('restoring'));
        $this->assertSame(__('Cutting Edge update check in progress.'), $progress->getTypeLabel('cuttingEdgeCheck'));
        $this->assertSame(__('Up to date'), $progress->getStageLabel('upToDate'));
        $this->assertSame(__('Checking for updates…'), $progress->getStageLabel('checking'));
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
                    $snapshot = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $this->assertIsInt($snapshot['updated']);
                    unset($snapshot['updated']);
                    $this->assertSame([
                        'type' => 'database',
                        'stage' => 'installing',
                        'status' => 'working',
                        'progress' => 70,
                    ], $snapshot);

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
