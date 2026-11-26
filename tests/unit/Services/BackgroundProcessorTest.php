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
    private string $originalTimeLimit = '0';
    private bool $originalIgnoreUserAbort = false;

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
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
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

    /**
     * @dataProvider timeLimitProvider
     */
    public function testFallbackKeepsRunningAfterTheBrowserLeavesAndAllowsEnoughTime(string $limit, string $expected): void
    {
        ini_set('max_execution_time', $limit);
        ignore_user_abort(false);
        $processor = new DispatchFailureBackgroundProcessor($this->createSession(), $this->createLogGateway(30));

        $processor->startProcess('ExampleProcess', 'run', ['file']);

        $this->assertCount(1, $processor->runProcessCalls);
        $this->assertTrue((bool) ignore_user_abort());
        $this->assertSame($expected, ini_get('max_execution_time'));
    }

    public static function timeLimitProvider(): array
    {
        return [
            'a short web server limit is raised' => ['30', '1800'],
            'just under the allowed time is raised' => ['1799', '1800'],
            'exactly the allowed time is kept' => ['1800', '1800'],
            'a longer limit is kept' => ['3000', '3000'],
            'no limit is kept' => ['0', '0'],
        ];
    }

    public function testDisabledBackgroundProcessingRunsInThisRequestWithTheSameGuard(): void
    {
        ini_set('max_execution_time', '30');
        ignore_user_abort(false);
        $processor = new DispatchFailureBackgroundProcessor($this->createSession('N'), $this->createLogGateway(31));

        $processor->startProcess('ExampleProcess', 'run', ['file']);

        $this->assertCount(1, $processor->runProcessCalls);
        $this->assertTrue((bool) ignore_user_abort());
        $this->assertSame('1800', ini_get('max_execution_time'));
    }

    public function testNothingIsChangedWhenTheProcessDoesNotRunInThisRequest(): void
    {
        ini_set('max_execution_time', '30');
        ignore_user_abort(false);
        $logGateway = $this->createLogGateway(32);
        $logGateway->method('update')->willReturn(true);
        $processor = new DispatchFailureBackgroundProcessor($this->createSession(), $logGateway);

        try {
            $processor->startProcess('ExampleProcess', 'run', ['file'], false);
            $this->fail('A dispatch failure must be returned when the fallback is turned off.');
        } catch (\PHPUnit\Framework\AssertionFailedError $failure) {
            throw $failure;
        } catch (\RuntimeException $exception) {
            $this->assertSame([], $processor->runProcessCalls);
        }

        $this->assertFalse((bool) ignore_user_abort());
        $this->assertSame('30', ini_get('max_execution_time'));
    }

    /**
     * @dataProvider failureProvider
     */
    public function testAnyFailureWhileRunningIsRecordedAsAnError(\Throwable $failure): void
    {
        $recorded = [];
        $processor = $this->createRunnableProcessor($this->createProcessThatThrows($failure), $recorded);

        $this->assertFalse($processor->runProcess(40, 'secret-key'));

        $this->assertSame('Error', end($recorded)['status']);
        $this->assertSame($failure->getMessage(), end($recorded)['output']);
        $this->assertSame(0, end($recorded)['pID']);
    }

    public static function failureProvider(): array
    {
        return [
            'an exception' => [new \RuntimeException('The process failed.')],
            'a type error' => [new \TypeError('Argument #1 must be of type string, null given.')],
            'an arithmetic error' => [new \DivisionByZeroError('Division by zero')],
            'a plain error' => [new \Error('Call to undefined method.')],
        ];
    }

    public function testAFailureCreatingTheProcessIsRecordedAsAnErrorToo(): void
    {
        $recorded = [];
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')->willThrowException(new \TypeError('The process class could not be built.'));
        $processor = $this->createRunnableProcessor(null, $recorded, $container);

        $this->assertFalse($processor->runProcess(40, 'secret-key'));

        $this->assertSame('Error', end($recorded)['status']);
        $this->assertSame('The process class could not be built.', end($recorded)['output']);
    }

    public function testASuccessfulProcessIsRecordedAsComplete(): void
    {
        $recorded = [];
        $processor = $this->createRunnableProcessor($this->createProcessThatThrows(null), $recorded);

        $this->assertTrue($processor->runProcess(40, 'secret-key'));

        $this->assertSame(['Running', 'Complete'], array_column($recorded, 'status'));
    }

    /**
     * @dataProvider fatalErrorProvider
     */
    public function testEveryKindOfFatalErrorIsRecordedWhenTheScriptEnds(int $type, bool $recorded): void
    {
        $updates = [];
        $processor = $this->createRunnableProcessor(null, $updates);

        $processor->shutdown(['type' => $type, 'message' => 'Allowed memory size exhausted', 'file' => 'x.php', 'line' => 1]);

        $this->assertSame($recorded ? ['Error'] : [], array_column($updates, 'status'));
        if ($recorded) {
            $this->assertSame('Allowed memory size exhausted', $updates[0]['output']);
        }
    }

    public static function fatalErrorProvider(): array
    {
        return [
            'fatal error' => [E_ERROR, true],
            'parse error' => [E_PARSE, true],
            'core error' => [E_CORE_ERROR, true],
            'compile error' => [E_COMPILE_ERROR, true],
            'user error' => [E_USER_ERROR, true],
            'warning' => [E_WARNING, false],
            'user warning' => [E_USER_WARNING, false],
            'notice' => [E_NOTICE, false],
            'deprecated' => [E_DEPRECATED, false],
        ];
    }

    public function testAScriptThatEndsWithoutAnErrorIsNotChanged(): void
    {
        $updates = [];
        $processor = $this->createRunnableProcessor(null, $updates);

        $processor->shutdown(null);

        $this->assertSame([], $updates);
    }

    public function testFallbackStillRunsWhenExecIsDisabledOnTheServer(): void
    {
        // PHP 8 throws an Error, not an Exception, when exec() is in disable_functions
        $logGateway = $this->createLogGateway(33);
        $processor = new ExecDisabledBackgroundProcessor($this->createSession(), $logGateway);

        $this->assertSame(33, $processor->startProcess('ExampleProcess', 'run', ['file']));
        $this->assertCount(1, $processor->runProcessCalls);
    }

    public function testDisabledExecIsRecordedAndReturnedWhenTheFallbackIsOff(): void
    {
        $logGateway = $this->createLogGateway(34);
        $logGateway->expects($this->once())
            ->method('update')
            ->with(34, $this->callback(function (array $changes): bool {
                $data = unserialize($changes['serialisedArray'], ['allowed_classes' => false]);
                $this->assertSame('Error', $data['status']);

                return true;
            }))
            ->willReturn(true);
        $processor = new ExecDisabledBackgroundProcessor($this->createSession(), $logGateway);

        $this->expectException(\Error::class);
        try {
            $processor->startProcess('ExampleProcess', 'run', ['file'], false);
        } finally {
            $this->assertSame([], $processor->runProcessCalls);
        }
    }

    /**
     * Build a processor whose stored process is ready to run. Every status update the
     * processor stores is added to $recorded, as the data that was serialised.
     *
     * @param object|null $process The process object the container returns.
     */
    private function createRunnableProcessor(?object $process, array &$recorded, $container = null): RunnableBackgroundProcessor
    {
        $processData = ['class' => 'ExampleProcess', 'method' => 'run', 'key' => 'secret-key', 'data' => [], 'status' => 'Ready'];

        // Only these two are replaced, so the real static getLogData() reads the stored data
        $logGateway = $this->getMockBuilder(LogGateway::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByID', 'update'])
            ->getMock();
        $logGateway->method('getByID')->willReturn(['serialisedArray' => serialize($processData)]);
        $logGateway->method('update')->willReturnCallback(function ($id, array $changes) use (&$recorded): bool {
            $recorded[] = unserialize($changes['serialisedArray'], ['allowed_classes' => false]);

            return true;
        });

        if ($container === null) {
            $container = new \League\Container\Container();
            if ($process !== null) {
                $container->add('ExampleProcess', $process);
            }
        }

        $processor = new RunnableBackgroundProcessor($this->createSession(), $logGateway);
        $processor->setContainer($container);

        return $processor;
    }

    private function createProcessThatThrows(?\Throwable $failure): object
    {
        return new class($failure) {
            public function __construct(private ?\Throwable $failure)
            {
            }

            public function execute(string $method, array $data): string
            {
                if ($this->failure !== null) {
                    throw $this->failure;
                }

                return 'done';
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTimeLimit = (string) ini_get('max_execution_time');
        $this->originalIgnoreUserAbort = (bool) ignore_user_abort();
    }

    protected function tearDown(): void
    {
        ini_set('max_execution_time', $this->originalTimeLimit);
        ignore_user_abort($this->originalIgnoreUserAbort);

        parent::tearDown();
    }

    private function createLogGateway(int $processID)
    {
        $logGateway = $this->createMock(LogGateway::class);
        $logGateway->method('insert')->willReturn($processID);

        return $logGateway;
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

/**
 * Dispatching throws an Error, as PHP 8 does when exec() is in disable_functions.
 */
class ExecDisabledBackgroundProcessor extends DispatchFailureBackgroundProcessor
{
    protected function getOS()
    {
        throw new \Error('Call to undefined function exec()');
    }
}

/**
 * Runs the real runProcess() without taking over the PHP error and exception handlers.
 */
class RunnableBackgroundProcessor extends BackgroundProcessor
{
    protected function registerHandlers($processID, $processData)
    {
        // Registering global handlers would affect the rest of the test run
        $this->processID = $processID;
        $this->processData = $processData;
    }

    private $processID;
    private $processData;

    public function shutdown(?array $lastError): void
    {
        $this->processID = 40;
        $this->processData = ['class' => 'ExampleProcess', 'method' => 'run', 'key' => 'secret-key', 'data' => [], 'status' => 'Running'];
        $this->handleShutdown($this->processID, $this->processData, $lastError ?? ['type' => 0]);
    }
}
