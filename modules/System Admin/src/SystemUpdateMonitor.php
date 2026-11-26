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

use Gibbon\Services\BackgroundProcessor;

/**
 * Decides whether a background system update is genuinely running.
 *
 * An update records its state in two places: the SystemUpdateProcess log entry
 * (Ready/Running) and the progress snapshot (working). If the PHP process dies
 * without a chance to clean up (OOM kill, restart, fatal error, missing PHP
 * binary), both stay "active" forever. This class uses the progress heartbeat
 * to detect that, and marks the dead update as stopped so a new one can start.
 *
 * @version v30
 * @since   v30
 */
final class SystemUpdateMonitor
{
    public const PROCESS_NAME = 'SystemUpdateProcess';

    /** Seconds without a progress write before a running process is checked. */
    public const HEARTBEAT_TIMEOUT = 300;

    /** Seconds without a progress write before an ended process is reclaimed. */
    public const ENDED_GRACE = 30;

    /** Seconds without a progress write after which an update is always considered dead. */
    public const MAX_RUNTIME = 3600;

    private const ACTIVE_STATUSES = ['Ready', 'Running'];

    /**
     * @param SystemUpdateProgress $progress
     * @param BackgroundProcessor $backgroundProcessor
     * @return void
     */
    public function __construct(
        private SystemUpdateProgress $progress,
        private BackgroundProcessor $backgroundProcessor
    ) {
    }

    /**
     * Get the ID of the most recent system update process log, or an empty string.
     *
     * @return string
     */
    public function getProcessID(): string
    {
        return $this->backgroundProcessor->getProcessIDByName(self::PROCESS_NAME);
    }

    /**
     * Whether a system update is currently running. A dead update is reclaimed
     * as a side effect: its process log is marked Stopped and its progress
     * snapshot is marked failed.
     *
     * @param int|null $now Current Unix time, for testing.
     * @return bool
     */
    public function isBusy(?int $now = null): bool
    {
        $now ??= time();
        $processID = $this->getProcessID();
        $process = $processID !== '' ? $this->backgroundProcessor->getProcess($processID) : [];
        $processActive = in_array($process['status'] ?? '', self::ACTIVE_STATUSES, true);
        $progressActive = $this->progress->get()['status'] === 'working';

        if (!$processActive && !$progressActive) {
            return false;
        }

        if (!$this->isDead($processID, $process, $processActive, $now)) {
            return true;
        }

        $this->reclaim($processID, $process, $processActive);

        return false;
    }

    /**
     * An update is dead when its heartbeat has been quiet too long. An ended process
     * is dead after ENDED_GRACE seconds. A process the log still shows as active is
     * dead after MAX_RUNTIME seconds, or sooner when its PID no longer exists.
     *
     * @param string $processID
     * @param array $process
     * @param bool $processActive
     * @param int $now
     * @return bool
     */
    private function isDead(string $processID, array $process, bool $processActive, int $now): bool
    {
        // Snapshots written before heartbeats existed have no timestamp: treat them as old.
        $updatedAt = $this->progress->getUpdatedAt();
        $age = $updatedAt === null ? PHP_INT_MAX : $now - $updatedAt;

        $timeout = $processActive ? self::HEARTBEAT_TIMEOUT : self::ENDED_GRACE;
        if ($age <= $timeout) {
            return false;
        }

        if ($age > self::MAX_RUNTIME || !$processActive) {
            return true;
        }

        // The log says the process is active but the heartbeat is quiet: look for the PID.
        // Without a known PID (synchronous runs) wait for the maximum runtime instead.
        $pid = (int) ($process['pID'] ?? 0);

        return $pid > 0 && !$this->backgroundProcessor->isProcessRunning($processID);
    }

    /**
     * Mark a dead update as stopped so that a new update can be started.
     *
     * @param string $processID
     * @param array $process
     * @param bool $processActive
     * @return void
     */
    private function reclaim(string $processID, array $process, bool $processActive): void
    {
        error_log('SystemUpdateMonitor: reclaiming a stale system update, process log '.($processID ?: 'none'));

        try {
            if ($processActive) {
                $this->backgroundProcessor->endProcess($processID, ['status' => 'Stopped'] + $process);
            }

            $snapshot = $this->progress->get();
            if ($snapshot['status'] === 'working') {
                $this->progress->fail($snapshot['stage'] ?? 'preparing');
            }
        } catch (\Throwable $error) {
            error_log('SystemUpdateMonitor: failed to reclaim a stale system update: '.$error->getMessage());
        }
    }
}
