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

namespace Gibbon\Services;

use Gibbon\Domain\System\LogGateway;
use Gibbon\Contracts\Services\Session;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Background Processor
 *
 * This class facilitates running a set of code in the background by creating a separate
 * php instance to instantiate a class and call a method on it. These are fire-and-forget
 * processes, aimed to help with time-consuming processes like mailing and report creation.
 *
 * The processor uses the database to track the background processes and pass the necessary
 * data from the initial process to the background one. It uses the database ID and a randomized
 * string to validate the process being run: this is done to prevent unwanted script access, and
 * to avoid placing any user-supplied data into an exec command.
 *
 * @version v18
 * @since   v18
 */
class BackgroundProcessor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const OS_WINDOWS = 1;
    const OS_NIX     = 2;
    const OS_OTHER   = 3;

    // Seconds allowed for a process that has to run in the web request (as in the CLI runner)
    const SYNCHRONOUS_TIME_LIMIT = 1800;

    // The kinds of PHP error that end the script
    const FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

    protected $session;
    protected $logGateway;

    /**
     * Create a background processor, tracking processes using the gibbonLog table (for now?)
     *
     * @param Session $session
     * @param LogGateway $logGateway
     * @return void
     */
    public function __construct(Session $session, LogGateway $logGateway)
    {
        $this->session = $session;
        $this->logGateway = $logGateway;
    }

    /**
     * Starts a new background process with a given class name. If the exec command
     * fails, or background processing is turned off, it runs the process immediately in
     * the current request instead.
     *
     * This fallback is deliberate: many hosts block exec() or turn background processing
     * off, and mail, reports and system updates still need to work there. The request then
     * waits for the process, so callers that must never run in a web request can pass false
     * to get an exception instead.
     *
     * @param   string  $processClassName
     * @param   string  $processMethodName
     * @param   array   $arguments
     * @param   bool    $allowSynchronousFallback Run in this request when it cannot run in the background.
     * @throws \RuntimeException When the process file is missing, or it cannot start and the fallback is turned off.
     * @return  int     The process ID.
     */
    public function startProcess(
        $processClassName,
        $processMethodName,
        array $arguments = [],
        bool $allowSynchronousFallback = true
    ) : int
    {
        $phpFile = $this->session->get('absolutePath').'/cli/system_backgroundProcessor.php';
        $phpOutput = '/dev/null';

        if (!file_exists($phpFile)) {
            throw new \RuntimeException('File not found: '.$phpFile);
        }

        if (empty($processClassName) || !is_array($arguments)) {
            throw new \InvalidArgumentException();
        }

        if ($this->session->get('backgroundProcessing') == 'N' && !$allowSynchronousFallback) {
            throw new \RuntimeException('Background processing is disabled.');
        }

        // Create the data to be serialized and passed to the process
        $processData = [
            'class'   => $processClassName,
            'method'  => $processMethodName,
            'key'     => bin2hex(random_bytes(16)),
            'data'    => $arguments,
            'status'  => 'Ready',
        ];

        // Create a log entry to track this process
        $processName = substr(strrchr($processClassName, '\\'), 1);
        $processID = $this->logGateway->insert([
            'title'              => 'Background Process - '.$processName,
            'gibbonPersonID'     => $this->session->get('gibbonPersonID'),
            'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'),
            'serialisedArray'    => serialize($processData),
        ]);

        // Background processing is turned off: run the process in this request instead
        if ($this->session->get('backgroundProcessing') == 'N') {
            $this->runInThisRequest($processID, $processData['key']);
            return $processID;
        }

        // Create and escape the set of args used in the php command.
        // These should always be non-user-supplied values.
        $args = [$phpFile, $processID, $processData['key'], $this->session->get('module')];
        $argsEscaped = implode(' ', array_map('escapeshellarg', $args));

        try {
            // Start the background process as a long-running PHP command
            switch ($this->getOS()) {
                case self::OS_WINDOWS:
                    $command = PHP_BINARY.' '.$argsEscaped;
                    exec(sprintf('%s > NUL &', $command));
                    break;

                case self::OS_NIX:
                    $command = PHP_BINDIR.'/php '.$argsEscaped;
                    $pID = exec(sprintf("%s > %s 2>&1 & echo $!", $command, $phpOutput));
                    break;

                default:
                    throw new \RuntimeException('Could not start background process, operating system not supported: '.PHP_OS);
            }

            $this->updateProcess($processID, [
                'pID' => $pID ?? 0,
            ] + $processData);
        } catch (\Throwable $e) {
            // Includes \Error: PHP 8 throws one when exec() is disabled on the server
            if (!$allowSynchronousFallback) {
                $this->handleException($processID, $processData, $e);
                throw $e;
            }

            // If the process cannot be started in the background, run it in this request.
            $this->runInThisRequest($processID, $processData['key']);
        }

        return $processID;
    }

    /**
     * Runs the process in the current request, for when it cannot be started in the background.
     * The browser may disconnect and the web server's time limit is usually short, so, like the
     * command line runner, keep going and allow enough time. A longer or unlimited limit is kept.
     *
     * @param string    $processID
     * @param string    $processKey
     * @return bool     Process was successfully run.
     */
    protected function runInThisRequest($processID, $processKey) : bool
    {
        ignore_user_abort(true);

        $timeLimit = (int) ini_get('max_execution_time');
        if ($timeLimit > 0 && $timeLimit < self::SYNCHRONOUS_TIME_LIMIT) {
            @set_time_limit(self::SYNCHRONOUS_TIME_LIMIT);
        }

        return $this->runProcess($processID, $processKey);
    }

    /**
     * Runs the process by instantiating the process class via the DI container
     * and calling the process method using the stored argument data.
     *
     * @param string    $processID
     * @param string    $processKey
     * @throws \InvalidArgumentException When the process data is missing.
     * @throws \RuntimeException When the key does not match.
     * @return bool     Process was successfully run.
     */
    public function runProcess($processID, $processKey) : bool
    {
        $processData = $this->getProcess($processID);

        if (empty($processData) || empty($processKey)) {
            throw new \InvalidArgumentException();
        }

        // Validate against the unique key provided in the process data
        if ($processData['key'] != $processKey || $processData['status'] != 'Ready') {
            throw new \RuntimeException('You do not have access to this action.');
        }

        $this->registerHandlers($processID, $processData);

        // Create the process via the DI container. Errors such as a TypeError are recorded
        // like exceptions, so the process never stays marked as running after a failure.
        try {
            $process = $this->getContainer()->get($processData['class']);
            $this->updateProcess($processID, [
                'status' => 'Running',
            ] + $processData);
        } catch (\Throwable $e) {
            return $this->handleException($processID, $processData, $e);
        }

        // Run the process
        try {
            $output = $process->execute($processData['method'], $processData['data']);
            return $this->endProcess($processID, [
                'status' => 'Complete',
                'output' => $output,
            ] + $processData);
        } catch (\Throwable $e) {
            return $this->handleException($processID, $processData, $e);
        }
    }

    /**
     * Register handlers for fatal errors, and for exceptions that escape runProcess().
     *
     * @param string $processID
     * @param array $processData
     * @return void
     */
    protected function registerHandlers($processID, $processData)
    {
        register_shutdown_function(function () use ($processID, $processData) {
            $this->handleShutdown($processID, $processData);
        });
        set_exception_handler(function ($e) use ($processID, $processData) {
            $this->handleException($processID, $processData, $e);
        });
    }

    /**
     * Gets the stored process data from the database using its ID.
     *
     * @param string $processID
     * @return array
     */
    public function getProcess($processID) : array
    {
        $log = $this->logGateway->getByID($processID);

        $processData = $this->logGateway->getLogData(is_array($log) ? ($log['serialisedArray'] ?? '') : '');

        if (empty($processData)) return [];
        if (empty($processData['class'])) return [];

        return $processData;
    }

    /**
     * Gets the most recent process by name. Can be used to monitor processes that
     * should not run concurrently.
     *
     * @param string $processName
     * @return string
     */
    public function getProcessIDByName($processName) : string
    {
        $log = $this->logGateway
            ->selectLogsByModuleAndTitle(null, 'Background Process - '.$processName)
            ->fetch();

        return $log['gibbonLogID'] ?? '';
    }

    /**
     * Updates the stored data for this process.
     *
     * @param string $processID
     * @param array $processData
     * @return bool
     */
    public function updateProcess($processID, array $processData) : bool
    {
        return $this->logGateway->update($processID, [
            'serialisedArray' => serialize($processData),
        ]);
    }

    /**
     * Ends a process by clearing its pID and saving its data. Returns false when the
     * process failed or errored.
     *
     * @param string $processID
     * @param array $processData
     * @return bool
     */
    public function endProcess($processID, array $processData) : bool
    {
        $this->logGateway->update($processID, [
            'serialisedArray' => serialize(['pID' => 0] + $processData),
        ]);

        // Error is the status recorded when a process throws or hits a fatal error
        return !in_array($processData['status'], ['Failed', 'Error'], true);
    }

    /**
     * Kills the process manually. Can be used to cancel a process mid-execution.
     *
     * @param   string  $processID
     * @return  bool    Process was successfully killed.
     */
    public function killProcess($processID) : bool
    {
        if ($this->session->get('backgroundProcessing') == 'N') {
            return false;
        }

        if ($processData = $this->getProcess($processID)) {
            try {
                exec('kill -9 '.$processData['pID']);
                return $this->endProcess($processID, ['status' => 'Stopped'] + $processData);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if a process is running by polling the pID. Does not work on Windows.
     *
     * @param   string  $processID
     * @return  bool    Process is running.
     */
    public function isProcessRunning($processID) : bool
    {
        if ($processData = $this->getProcess($processID)) {
            if ($processData['status'] != 'Ready' && $processData['status'] != 'Running') {
                return false;
            }

            if ($this->session->get('backgroundProcessing') == 'N') {
                return $processData['status'] == 'Running';
            }

            try {
                $checkProcess = exec('ps '.$processData['pID']);
                if (stripos($checkProcess, $processData['pID']) !== false) {
                    return true;
                } else {
                    $this->endProcess($processID, ['status' => 'Stopped'] + $processData);
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Handle a fatal error during background process execution. Runs when the script
     * ends, and records an error if it ended because of a fatal error.
     *
     * @param string $processID
     * @param array $processData
     * @param array|null $lastError The last PHP error, as error_get_last() returns it. Read when not given.
     * @return void
     */
    protected function handleShutdown($processID, $processData, ?array $lastError = null)
    {
        $lastError ??= error_get_last();
        if ($lastError && ($lastError['type'] & self::FATAL_ERRORS)) {
            $this->endProcess($processID, [
                'status' => 'Error',
                'output' => $lastError['message'],
            ] + $processData);
        }
    }

    /**
     * Handle an exception during background process execution.
     *
     * @param string $processID
     * @param array $processData
     * @param \Throwable $e
     * @return bool
     */
    protected function handleException($processID, $processData, $e)
    {
        return $this->endProcess($processID, [
            'status' => 'Error',
            'output' => $e->getMessage(),
        ] + $processData);
    }

    /**
     * Determine the current OS. Used to call the correct system command to start a process.
     *
     * @return int
     */
    protected function getOS()
    {
        $os = strtoupper(PHP_OS);
        if (substr($os, 0, 3) === 'WIN') {
            return self::OS_WINDOWS;
        } else if ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
            return self::OS_NIX;
        }

        return self::OS_OTHER;
    }
}
