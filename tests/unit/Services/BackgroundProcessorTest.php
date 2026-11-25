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

namespace Gibbon\Services;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\LogGateway;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Services\BackgroundProcessor
 */
class BackgroundProcessorTest extends TestCase
{
    public function testExistingCallersFallBackToSynchronousExecutionWhenDispatchFails(): void
    {
        $logGateway = $this->createMock(LogGateway::class);
        $logGateway->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (array $record): bool {
                $data = unserialize($record['serialisedArray'], ['allowed_classes' => false]);

                $this->assertSame('ExampleProcess', $data['class']);
                $this->assertSame('run', $data['method']);
                $this->assertSame(['file'], $data['data']);
                $this->assertSame('Ready', $data['status']);

                return true;
            }))
            ->willReturn(25);

        $processor = new DispatchFailureBackgroundProcessor($this->createSession(), $logGateway);

        $this->assertSame(25, $processor->startProcess('ExampleProcess', 'run', ['file']));
        $this->assertSame([[25, $processor->processKey]], $processor->runProcessCalls);
    }

    public function testDisabledSynchronousFallbackRecordsDispatchErrorWithoutExecutingWork(): void
    {
        $logGateway = $this->createMock(LogGateway::class);
        $logGateway->expects($this->once())->method('insert')->willReturn(26);
        $logGateway->expects($this->once())
            ->method('update')
            ->with(26, $this->callback(function (array $changes): bool {
                $data = unserialize($changes['serialisedArray'], ['allowed_classes' => false]);

                $this->assertSame('Error', $data['status']);
                $this->assertSame(0, $data['pID']);

                return true;
            }))
            ->willReturn(true);

        $processor = new DispatchFailureBackgroundProcessor($this->createSession(), $logGateway);

        try {
            $processor->startProcess('ExampleProcess', 'run', ['file'], false);
            $this->fail('A dispatch failure must be returned to the initiating request.');
        } catch (\RuntimeException $exception) {
            $this->assertSame([], $processor->runProcessCalls);
        }
    }

    public function testDisabledBackgroundProcessingDoesNotExecuteWorkWhenFallbackIsDisabled(): void
    {
        $logGateway = $this->createMock(LogGateway::class);
        $logGateway->expects($this->never())->method('insert');

        $processor = new DispatchFailureBackgroundProcessor(
            $this->createSession('N'),
            $logGateway
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Background processing is disabled.');

        try {
            $processor->startProcess('ExampleProcess', 'run', ['file'], false);
        } finally {
            $this->assertSame([], $processor->runProcessCalls);
        }
    }

    private function createSession(string $backgroundProcessing = 'Y'): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(function (string $key) use ($backgroundProcessing) {
            return [
                'absolutePath' => dirname(__DIR__, 3),
                'backgroundProcessing' => $backgroundProcessing,
                'gibbonPersonID' => '1',
                'gibbonSchoolYearID' => '1',
                'module' => 'System Admin',
            ][$key] ?? '';
        });

        return $session;
    }
}

class DispatchFailureBackgroundProcessor extends BackgroundProcessor
{
    public array $runProcessCalls = [];
    public string $processKey = '';

    public function runProcess($processID, $processKey): bool
    {
        $this->processKey = $processKey;
        $this->runProcessCalls[] = [$processID, $processKey];

        return true;
    }

    protected function getOS()
    {
        return self::OS_OTHER;
    }
}
