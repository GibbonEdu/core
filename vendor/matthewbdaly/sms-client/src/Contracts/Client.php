<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Contracts;

/**
 * SMS client.
 */
interface Client
{
    /**
     * Get the driver name.
     *
     * @return string
     */
    public function getDriver(): string;

    /**
     * Send the message.
     *
     * @param array $msg The message array.
     *
     * @return boolean
     */
    public function send(array $msg): bool;
}
