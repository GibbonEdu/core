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
use Gibbon\Services\BackgroundProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4).'/functions.php';
require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateProgress.php';
require_once dirname(__DIR__, 4).'/modules/System Admin/src/SystemUpdateMonitor.php';

/**
 * @covers \Gibbon\Module\SystemAdmin\SystemUpdateMonitor
 */
class SystemUpdateMonitorTest extends TestCase
{
    private const NOW = 1700000000;

    /** @var string|null The stored progress snapshot JSON. */
    private ?string $storedSnapshot = null;

    /** @var MockObject&BackgroundProcessor */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storedSnapshot = null;
        $this->processor = $this->createMock(BackgroundProcessor::class);
    }

    public function testIdleSystemIsNotBusyAndNothingIsReclaimed(): void
    {
        $monitor = $this->createMonitor(null, null, []);
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertNull($this->storedSnapshot);
    }

    public function testEndedProcessWithIdleProgressIsNotBusy(): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('idle', 10), $this->process('Complete'));
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertFalse($monitor->isBusy(self::NOW));
    }

    public function testRecentHeartbeatKeepsARunningProcessBusyWithoutCheckingItsPid(): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('working', 60), $this->process('Running'));
        $this->processor->expects($this->never())->method('isProcessRunning');
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertTrue($monitor->isBusy(self::NOW));
    }

    public function testQuietHeartbeatIsStillBusyWhileTheProcessIsAlive(): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('working', 900), $this->process('Running'));
        $this->processor->expects($this->once())->method('isProcessRunning')->with('7')->willReturn(true);
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertTrue($monitor->isBusy(self::NOW));
        $this->assertSame('working', $this->decodeStored()['status']);
    }

    /**
     * @dataProvider activeStatusProvider
     */
    public function testQuietHeartbeatAndDeadProcessIsReclaimed(string $status): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('working', 400, 'installing'), $this->process($status));
        $this->processor->method('isProcessRunning')->willReturn(false);
        $this->processor->expects($this->once())
            ->method('endProcess')
            ->with('7', $this->callback(function (array $data) use ($status): bool {
                $this->assertSame('Stopped', $data['status']);
                $this->assertSame('SystemUpdateProcess', $data['class']);
                $this->assertNotSame($status, $data['status']);

                return true;
            }))
            ->willReturn(false);

        $this->assertFalse($monitor->isBusy(self::NOW));

        $stored = $this->decodeStored();
        $this->assertSame('failed', $stored['status']);
        $this->assertSame('installing', $stored['stage']);
        $this->assertGreaterThan(self::NOW, $stored['updated'], 'The failure write refreshes the heartbeat.');
    }

    public static function activeStatusProvider(): array
    {
        return [
            'Running' => ['Running'],
            'Ready (PHP binary missing, process never started)' => ['Ready'],
        ];
    }

    public function testQuietHeartbeatWithoutAKnownPidWaitsForTheMaximumRuntime(): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('working', 900), $this->process('Running', 0));
        $this->processor->expects($this->never())->method('isProcessRunning');
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertTrue($monitor->isBusy(self::NOW));
    }

    public function testHeartbeatOlderThanTheMaximumRuntimeIsAlwaysReclaimed(): void
    {
        $age = SystemUpdateMonitor::MAX_RUNTIME + 1;
        $monitor = $this->createMonitor('7', $this->snapshot('working', $age), $this->process('Running', 0));
        $this->processor->expects($this->never())->method('isProcessRunning');
        $this->processor->expects($this->once())->method('endProcess')->willReturn(false);

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertSame('failed', $this->decodeStored()['status']);
    }

    public function testProcessThatEndedWithAnErrorIsReclaimedOnceTheGracePeriodPasses(): void
    {
        $age = SystemUpdateMonitor::ENDED_GRACE + 1;
        $monitor = $this->createMonitor('7', $this->snapshot('working', $age), $this->process('Error'));
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertSame('failed', $this->decodeStored()['status']);
    }

    public function testNewUpdateIsNotReclaimedWhileItsProcessLogIsBeingCreated(): void
    {
        // begin() writes the snapshot before startProcess() creates the new log,
        // so for a moment the latest log still belongs to the previous update.
        $monitor = $this->createMonitor('6', $this->snapshot('working', 2), $this->process('Complete'));
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertTrue($monitor->isBusy(self::NOW));
        $this->assertSame('working', $this->decodeStored()['status']);
    }

    public function testSnapshotWithoutAHeartbeatIsTreatedAsOld(): void
    {
        $snapshot = ['type' => 'file', 'stage' => 'installing', 'status' => 'working', 'progress' => 50];
        $monitor = $this->createMonitor('7', $snapshot, $this->process('Running'));
        $this->processor->method('isProcessRunning')->willReturn(true);
        $this->processor->expects($this->once())->method('endProcess')->willReturn(false);

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertSame('failed', $this->decodeStored()['status']);
    }

    public function testWorkingSnapshotWithNoProcessLogIsReclaimedOnceOld(): void
    {
        $monitor = $this->createMonitor(null, $this->snapshot('working', 120, null), []);
        $this->processor->expects($this->never())->method('endProcess');

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertSame('failed', $this->decodeStored()['status']);
        $this->assertSame('preparing', $this->decodeStored()['stage']);
    }

    public function testDeadProcessWithAFailedSnapshotIsStoppedWithoutRewritingTheSnapshot(): void
    {
        $snapshot = $this->snapshot('failed', 400, 'installing');
        $monitor = $this->createMonitor('7', $snapshot, $this->process('Running'));
        $this->processor->method('isProcessRunning')->willReturn(false);
        $this->processor->expects($this->once())->method('endProcess')->willReturn(false);
        $before = $this->storedSnapshot;

        $this->assertFalse($monitor->isBusy(self::NOW));
        $this->assertSame($before, $this->storedSnapshot);
    }

    public function testFailureWhileReclaimingDoesNotEscapeAndStillReportsNotBusy(): void
    {
        $monitor = $this->createMonitor('7', $this->snapshot('working', 400), $this->process('Running'));
        $this->processor->method('isProcessRunning')->willReturn(false);
        $this->processor->method('endProcess')->willThrowException(new \RuntimeException('database unavailable'));

        $this->assertFalse($monitor->isBusy(self::NOW));
    }

    public function testGetProcessIDReturnsTheLatestSystemUpdateProcess(): void
    {
        $monitor = $this->createMonitor('0000000000000042', null, []);

        $this->assertSame('0000000000000042', $monitor->getProcessID());
    }

    /**
     * Build a monitor backed by a real progress service over an in-memory setting.
     *
     * @param string|null $processID Latest SystemUpdateProcess log ID, or null for none.
     * @param array|null $snapshot Stored progress snapshot, or null for none.
     * @param array $process Stored process data for the log ID.
     */
    private function createMonitor(?string $processID, ?array $snapshot, array $process): SystemUpdateMonitor
    {
        $this->storedSnapshot = $snapshot === null ? null : json_encode($snapshot, JSON_THROW_ON_ERROR);

        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')->willReturnCallback(fn () => $this->storedSnapshot ?? false);
        $settingGateway->method('updateSettingByScope')->willReturnCallback(function ($scope, $name, $value): bool {
            $this->storedSnapshot = $value;

            return true;
        });

        $this->processor->method('getProcessIDByName')->with('SystemUpdateProcess')->willReturn($processID ?? '');
        $this->processor->method('getProcess')->willReturn($process);

        return new SystemUpdateMonitor(new SystemUpdateProgress($settingGateway), $this->processor);
    }

    /**
     * @return array{type: string, stage: ?string, status: string, progress: int, updated: int}
     */
    private function snapshot(string $status, int $age, ?string $stage = 'installing'): array
    {
        return [
            'type' => 'file',
            'stage' => $stage,
            'status' => $status,
            'progress' => 40,
            'updated' => self::NOW - $age,
        ];
    }

    private function process(string $status, int $pid = 4242): array
    {
        return [
            'class' => 'SystemUpdateProcess',
            'method' => 'run',
            'key' => 'abc',
            'data' => ['file'],
            'status' => $status,
            'pID' => $pid,
        ];
    }

    private function decodeStored(): array
    {
        return json_decode((string) $this->storedSnapshot, true, 512, JSON_THROW_ON_ERROR);
    }
}
