<?php

namespace Gibbon\Install\Http\Exception;

/**
 * Recoverable issue in installation process.
 *
 * Represents installation issue(s) that can be recovered after
 * installation and does not affect usability of the system.
 */
class RecoverableException extends \Exception {

    /**
     * Get the level of severity of the exception.
     * Should be used to format alert.
     *
     * @see \Gibbon\Services\Format::alert()
     *
     * @return string Either 'warning' or 'error'.
     */
    public function getLevel(): string
    {
        return 'warning';
    }
}
