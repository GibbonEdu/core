<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS;

use Matthewbdaly\SMS\Contracts\Mailer;

/**
 * Basic mailer interface implementation
 */
class PHPMailAdapter implements Mailer
{
    /**
     * Send email
     *
     * @param string $recipient The recipent's email.
     * @param string $message   The message.
     * @return void
     */
    public function send(string $recipient, string $message)
    {
        mail($recipient, "", $message);
    }
}
