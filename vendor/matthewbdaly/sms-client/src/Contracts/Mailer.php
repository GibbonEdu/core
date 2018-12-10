<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Contracts;

/**
 * Basic mailer interface
 */
interface Mailer
{
    /**
     * Send email
     *
     * @param string $recipient The recipent's email.
     * @param string $message   The message.
     * @return boolean
     */
    public function send(string $recipient, string $message);
}
