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

/**
 * Stores the current background update progress snapshot.
 *
 * @version v30
 * @since   v30
 */
final class SystemUpdateProgress
{
    private const SCOPE = 'System Admin';
    private const NAME = 'backgroundUpdateProgress';

    private const TYPES = ['file', 'database', 'restore'];
    private const STAGES = ['preparing', 'downloading', 'installing', 'restoring', 'finalizing'];
    private const STATUSES = ['idle', 'working', 'failed', 'complete'];

    private const DEFAULT_SNAPSHOT = [
        'type' => null,
        'stage' => null,
        'status' => 'idle',
        'progress' => null,
    ];

    /** @var array<string, string> */
    private array $statusLabels;

    /** @var array<string, string> */
    private array $typeLabels;

    /** @var array<string, string> */
    private array $stageLabels;

    /**
     * @param SettingGateway $settingGateway
     * @return void
     */
    public function __construct(private SettingGateway $settingGateway)
    {
        $this->statusLabels = [
            'working' => __('Running'),
            'failed'  => __('Failed'),
        ];
        $this->typeLabels = [
            'file'     => __('File system update in progress.'),
            'database' => __('Database update in progress.'),
            'restore'  => __('System restore in progress.'),
        ];
        $this->stageLabels = [
            'preparing'   => __('Preparing update…'),
            'downloading' => __('Downloading update…'),
            'installing'  => __('Installing update…'),
            'restoring'   => __('Restoring files…'),
            'finalizing'  => __('Finalizing update…'),
        ];
    }

    /**
     * Get the translated label for a status code.
     *
     * @param string|null $status
     * @return string|null
     */
    public function getStatusLabel(?string $status): ?string
    {
        return $status === null ? null : ($this->statusLabels[$status] ?? null);
    }

    /**
     * Get the translated label for an update type.
     *
     * @param string|null $type
     * @return string|null
     */
    public function getTypeLabel(?string $type): ?string
    {
        return $type === null ? null : ($this->typeLabels[$type] ?? null);
    }

    /**
     * Get the translated label for an update stage.
     *
     * @param string|null $stage
     * @return string|null
     */
    public function getStageLabel(?string $stage): ?string
    {
        return $stage === null ? null : ($this->stageLabels[$stage] ?? null);
    }

    /**
     * Replace the current snapshot when an accepted update starts.
     *
     * @param string $type
     * @return void
     */
    public function begin(string $type): void
    {
        $this->assertAllowed($type, self::TYPES);

        $this->write([
            'type' => $type,
            'stage' => null,
            'status' => 'working',
            'progress' => null,
        ]);
    }

    /**
     * Store the current update stage and progress percentage.
     *
     * @param string $stage
     * @param string $status
     * @param int|null $progress
     * @return void
     */
    public function update(string $stage, string $status, ?int $progress): void
    {
        $this->assertAllowed($stage, self::STAGES);
        $this->assertAllowed($status, self::STATUSES);
        $this->assertProgress($progress);

        $snapshot = $this->get();
        $this->write([
            'type' => $snapshot['type'],
            'stage' => $stage,
            'status' => $status,
            'progress' => $progress,
        ]);
    }

    /**
     * Record a failure without storing technical error details.
     *
     * @param string $stage
     * @return void
     */
    public function fail(string $stage): void
    {
        $this->assertAllowed($stage, self::STAGES);

        $snapshot = $this->get();
        $this->write([
            'type' => $snapshot['type'],
            'stage' => $stage,
            'status' => 'failed',
            'progress' => $snapshot['progress'],
        ]);
    }

    /**
     * Return a normalized snapshot that is safe for presentation.
     *
     * @return array{type: ?string, stage: ?string, status: string, progress: ?int}
     */
    public function get(): array
    {
        $value = $this->settingGateway->getSettingByScope(self::SCOPE, self::NAME);
        if (!is_string($value)) {
            return self::DEFAULT_SNAPSHOT;
        }

        try {
            $snapshot = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::DEFAULT_SNAPSHOT;
        }

        if (!is_array($snapshot)) {
            return self::DEFAULT_SNAPSHOT;
        }

        $type = $snapshot['type'] ?? null;
        $stage = $snapshot['stage'] ?? null;
        $status = $snapshot['status'] ?? null;

        if (
            !$this->isAllowedOrNull($type, self::TYPES)
            || !$this->isAllowedOrNull($stage, self::STAGES)
            || !is_string($status)
            || !in_array($status, self::STATUSES, true)
        ) {
            return self::DEFAULT_SNAPSHOT;
        }

        return [
            'type' => $type,
            'stage' => $stage,
            'status' => $status,
            'progress' => $this->normalizeProgress($snapshot['progress'] ?? null),
        ];
    }

    /**
     * Write the snapshot to the database.
     *
     * @param array{type: ?string, stage: ?string, status: string, progress: ?int} $snapshot
     * @return void
     */
    private function write(array $snapshot): void
    {
        $this->settingGateway->updateSettingByScope(
            self::SCOPE,
            self::NAME,
            json_encode($snapshot, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Assert that a value is in the allowed list.
     *
     * @param string $value
     * @param string[] $allowed
     * @return void
     */
    private function assertAllowed(string $value, array $allowed): void
    {
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Assert that progress is a valid percentage or null.
     *
     * @param int|null $progress
     * @return void
     */
    private function assertProgress(?int $progress): void
    {
        if ($progress !== null && ($progress < 0 || $progress > 100)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Check if value is null or in the allowed list.
     *
     * @param mixed $value
     * @param string[] $allowed
     * @return bool
     */
    private function isAllowedOrNull(mixed $value, array $allowed): bool
    {
        return $value === null || (is_string($value) && in_array($value, $allowed, true));
    }

    /**
     * Normalize progress to an integer between 0 and 100.
     *
     * @param mixed $progress
     * @return int|null
     */
    private function normalizeProgress(mixed $progress): ?int
    {
        return is_int($progress) && $progress >= 0 && $progress <= 100 ? $progress : null;
    }
}
