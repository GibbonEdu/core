<?php

namespace Gibbon\Install\Http;

use Gibbon\Install\Http\Exception\ForbiddenException;

/**
 * A simple service to create and verify nonce. To ensure
 * that a form submission is specific to certain time, action,
 * and user session.
 *
 * Example:
 * <code>
 * <?php
 *
 * if (session_status() !== PHP_SESSION_ACTIVE) session_start();
 * if (!isset($_SESSION['nonce_token'])) $_SESSION['nonce_token'] = generate_some_random_token();
 *
 * $nonceService = new \Gibbon\Install\NonceService($_SESSION['nonce_token']);
 * $nonce = $nonceService->create('myForm:submit');
 *
 * // ...
 * // get nonce from a form submission
 * $nonceService = new \Gibbon\Install\NonceService($_SESSION['nonce_token']);
 * if (!$nonceService->verify($_POST['nonce'], 'myForm:submit')) {
 *   // show some warning. do not proceed.
 * }
 * </code>
 */
class NonceService
{
    /**
     * @var int One day, represents in seconds.
     */
    const DAY_IN_SECONDS = 86400;

    /**
     * @var string Token to generate nonce hash with.
     */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Generate a time-based tick number to check with.
     *
     * @return integer A time-based integer. Change every 12 hours.
     */
    protected static function getTick(): int
    {
        return ceil(time() / (static::DAY_IN_SECONDS / 2));
    }

    /**
     * A hash function for internal use.
     *
     * @param string $string String to hash with.
     *
     * @return string The hash.
     */
    protected static function hash(string $string): string
    {
        return hash('sha256', $string);
    }

    /**
     * Generate a nonce hash from time, action and token string.
     *
     * @param string|null $action Optional action string to use with.
     *
     * @return string Nonce string for form submit verification.
     */
    public function generate(?string $action = null, ?int $tick = null): string
    {
        $tick = $tick ?? static::getTick();
        $action = $action ?: -1;
        return substr(static::hash($tick . '|'  . $action . '|' . $this->token), -12, 10);
    }

    /**
     * Verify that the nonce is generated from the token and action.
     *
     * @param string      $nonce        Nonce to verify.
     * @param string|null $action       Optiona action string.
     * @param bool        $throwOnError Throws ForbiddenException when failed to verify. Default: true.
     *
     * @return boolean If the nonce is generated from a token within tick.
     *
     * @throws ForbiddenException  Verification failed and $throwOnError is true.
     */
    public function verify(string $nonce, ?string $action = null, bool $throwOnError = true): bool
    {
        $tick = static::getTick();

        // Nonce generated 0-12 hours ago.
        if ($this->generate($action, $tick) === $nonce) return true;

        // Nonce generated 12-24 hours ago.
        if ($this->generate($action, $tick - 1) === $nonce) return true;

        if ($throwOnError) {
            throw new ForbiddenException('nonce check failed');
        }
        return false;
    }
}
