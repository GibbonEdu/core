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

    protected $session;
    protected $logGateway;

    /**
     * Create a background processor, tracking processes using the gibbonLog table (for now?)
     *
     * @param  Session     $session
     * @param  LogGateway  $logGateway
     */
    public function __construct(Session $session, LogGateway $logGateway)
    {
        $this->session = $session;
        $this->logGateway = $logGateway;
    }

    /**
     * Starts a new background process with a given class name. If the exec command
     * fails, it falls back to running the process immediately in the current runtime.
     *
     * @param   string  $processClassName
     * @param   string  $processMethodName
     * @param   array   $arguments
     * @return  bool    Process was successfully started.
     */
    public function startProcess($processClassName, $processMethodName, array $arguments = []) : int
    {
        $phpFile = $this->session->get('absolutePath').'/cli/system_backgroundProcess.php';
        $phpOutput = '/dev/null';

        if (!file_exists($phpFile)) {
            throw new \RuntimeException('File not found: '.$phpFile);
        }

        if (empty($processClassName) || !is_array($arguments)) {
            throw new \InvalidArgumentException();
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

        // Allow systems to disable background processing
        if ($this->session->get('backgroundProcessing') == 'N') {
            $this->runProcess($processID, $processData['key']);
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
        } catch (\Exception $e) {
            // If we can't run the process in the background, still try to run it in this thread.
            $this->runProcess($processID, $processData['key']);
        }

        return $processID;
    }

    /**
     * Runs the process by instantiating the process class via the DI container
     * and calling the process method using the stored argument data.
     *
     * @param string    $processID
     * @param string    $processKey
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

        // Handle PHP fatal errors which are not caught by a try-catch
        register_shutdown_function(function () use ($processID, $processData) {
            $this->handleShutdown($processID, $processData);
        });
        set_exception_handler(function ($e) use ($processID, $processData) {
            $this->handleException($processID, $processData, $e);
        });

        // Create the process via the DI container
        try {
            $process = $this->getContainer()->get($processData['class']);
            $this->updateProcess($processID, [
                'status' => 'Running',
            ] + $processData);
        } catch (\Exception $e) {
            return $this->handleException($processID, $processData, $e);
        }

        // Run the process
        try {
            $output = $process->execute($processData['method'], $processData['data']);
            return $this->endProcess($processID, [
                'status' => 'Complete',
                'output' => $output,
            ] + $processData);
        } catch (\Exception $e) {
            return $this->handleException($processID, $processData, $e);
        }
    }

    /**
     * Gets the stored process data from the database using it's id.
     *
     * @param string $processID
     * @return array
     */
    public function getProcess($processID) : array
    {
        $log = $this->logGateway->getByID($processID);

        $processData = isset($log['serialisedArray'])
            ? unserialize($log['serialisedArray'])
            : [];

        if (empty($processData)) return false;
        if (empty($processData['class'])) return false;

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
     * Ends a process by clearing the pID and updating it's data. Returns true if
     * the process was successfull.
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

        return $processData['status'] != 'Failed';
    }

    /**
     * Kills the process manually. Can be used to cancel a process mid-execution.
     *
     * @param   string  $processID
     * @return  bool    Process was successfully killed.
     */
    public function killProcess($processID) : bool
    {
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

    protected function handleShutdown($processID, $processData)
    {
        $lastError = error_get_last();
        if ($lastError && $lastError['type'] === E_ERROR) {
            $this->endProcess($processID, [
                'status' => 'Error',
                'output' => $lastError['message'],
            ] + $processData);
        }
    }

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
     * @return string
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
