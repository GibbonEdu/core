<?php
/**
 * Created by PhpStorm.
 *
 * Gibbon
 * (c) 2019 Craig Rayner <craig@craigrayner.com>
 *
 * User: craig
 * Date: 4/01/2019
 * Time: 15:22
 */

namespace Gibbon\Data;


class PasswordEncoder
{
    /**
     *
     */
    CONST ENCRYPTION = [
        'Argon2i' => 4,
        'BCRYPT' => 3,
        'SHA256' => 2,
        'MD5' => 1,
    ];

    public function getHighestAvailableExcryption()
    {
        if (\PHP_VERSION_ID >= 70200) {
            return 'Argon2i';
        }
        if (\function_exists('sodium_crypto_pwhash_str_verify')) {
            return 'Argon2i';
        }
        if (\extension_loaded('libsodium')) {
            return 'Argon2i';
        }
        if (\defined('PASSWORD_BCRYPT') && \PHP_VERSION_ID > 50500 )
            return 'BCRYPT';
        return 'SHA256';
    }
    /**
     * @param $encoded
     * @param $raw
     * @param $salt
     * @return bool
     */
    private function isArgon2iPasswordValid($encoded, $raw, $salt): bool
    {
        if ($this->getHighestAvailableExcryption() !== 'Argon2i')
            return false;
        if (\PHP_VERSION_ID >= 70200) {
            $this->currentEncryption = 'Argon2i';
            return !$this->isPasswordTooLong($raw) && password_verify($raw, $encoded);
        }
        if (\function_exists('sodium_crypto_pwhash_str_verify')) {
            $this->currentEncryption = 'Argon2i';
            $valid = !$this->isPasswordTooLong($raw) && \sodium_crypto_pwhash_str_verify($encoded, $raw);
            \sodium_memzero($raw);

            return $valid;
        }
        if (\extension_loaded('libsodium')) {
            $valid = !$this->isPasswordTooLong($raw) && \Sodium\crypto_pwhash_str_verify($encoded, $raw);
            \Sodium\memzero($raw);
            $this->currentEncryption = 'Argon2i';

            return $valid;
        }

        return false;
    }

    /**
     * Checks if the password is too long.
     *
     * @param string $password The password to check
     * @param int $length
     * @return bool true if the password is too long, false otherwise
     */
    protected function isPasswordTooLong($password, int $length = 4096)
    {
        return \strlen($password) > $length;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeArgon2iPassword($raw, $salt = null): ?string
    {
        if ($this->getHighestAvailableExcryption() !== 'Argon2i')
            return null;
        if ($this->isPasswordTooLong($raw)) {
            throw new \Exception('Invalid password.');
        }

        if (\PHP_VERSION_ID >= 70200) {
            return $this->encodePasswordNative($raw);
        }
        if (\function_exists('sodium_crypto_pwhash_str')) {
            return $this->encodePasswordSodiumFunction($raw);
        }
        if (\extension_loaded('libsodium')) {
            return $this->encodePasswordSodiumExtension($raw);
        }

        return null;
    }

    /**
     * @param $raw
     * @return string
     */
    private function encodePasswordSodiumFunction($raw)
    {
        $hash = \sodium_crypto_pwhash_str(
            $raw,
            \SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        \sodium_memzero($raw);

        return $hash;
    }

    /**
     * @param $raw
     * @return string
     */
    private function encodePasswordSodiumExtension($raw)
    {
        $hash = \Sodium\crypto_pwhash_str(
            $raw,
            \Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        \Sodium\memzero($raw);

        return $hash;
    }
    /**
     * Encodes the raw password.
     *
     * It doesn't work with PHP versions lower than 5.3.7, since
     * the password compat library uses CRYPT_BLOWFISH hash type with
     * the "$2y$" salt prefix (which is not available in the early PHP versions).
     *
     * @see https://github.com/ircmaxell/password_compat/issues/10#issuecomment-11203833
     *
     * It is almost best to **not** pass a salt and let PHP generate one for you.
     *
     * @param string $raw  The password to encode
     * @param string $salt The salt
     *
     * @return string The encoded password
     *
     * @throws BadCredentialsException when the given password is too long
     *
     * @see http://lxr.php.net/xref/PHP_5_5/ext/standard/password.c#111
     */
    private function encodeBCryptPassword($raw, $salt = null)
    {
        if (!in_array($this->getHighestAvailableExcryption(), ['Argon2i', 'BCRYPT']))
            return null;
        if ($this->isPasswordTooLong($raw)) {
            throw new \Exception('Invalid password.');
        }

        $options = array('cost' => 15);

        return password_hash($raw, PASSWORD_BCRYPT, $options);
    }

    /**
     * {@inheritdoc}
     */
    private function isBCryptPasswordValid($encoded, $raw, $salt)
    {
        if (!in_array($this->getHighestAvailableExcryption(), ['Argon2i', 'BCRYPT']))
            return false;
        $this->currentEncryption = 'BCRYPT';
        return !$this->isPasswordTooLong($raw) && password_verify($raw, $encoded);
    }
    /**
     * Encodes the raw password.
     *
     * @param string $raw  The password to encode
     * @param string $salt The salt
     *
     * @return string The encoded password
     */
    private function encodeSHA256Password($raw, $salt): string
    {
        return hash('sha256', $salt.$raw);
    }

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $encoded An encoded password
     * @param string $raw     A raw password
     * @param string $salt    The salt
     *
     * @return bool true if the password is valid, false otherwise
     */
    private function isSHA256PasswordValid($encoded, $raw, $salt): bool
    {
        if ($encoded === $this->encodeSHA256Password($raw, $salt))
        {
            $this->currentEncryption = 'SHA256';
            return true;
        }
        return false;
    }

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $encoded An encoded password
     * @param string $raw     A raw password
     * @param string $salt    The salt
     *
     * @return bool true if the password is valid, false otherwise
     */
    private function isMD5PasswordValid($encoded, $raw, $salt): bool
    {
        if ($encoded === md5($raw))
        {
            $this->currentEncryption = 'MD5';
            return true;
        }
        return false;
    }

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $encoded An encoded password
     * @param string $raw     A raw password
     * @param string $salt    The salt
     *
     * @return bool true if the password is valid, false otherwise
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        if ($this->isArgon2iPasswordValid($encoded, $raw, $salt))
            return true;
        if ($this->isBCryptPasswordValid($encoded, $raw, $salt))
            return true;
        if ($this->isSHA256PasswordValid($encoded, $raw, $salt))
            return true;
        if ($this->isMD5PasswordValid($encoded, $raw, $salt))
            return true;
        return false;
    }

    /**
     * @param $raw
     * @param $salt
     * @param string $useHighest
     */
    public function encodePassword($raw, $salt, $useHighestEncryption = null)
    {
        $highestAvailableEncryption = $this->getHighestAvailableExcryption();
        if (empty($useHighestEncryption))
            $useHighestEncryption = $highestAvailableEncryption;
        if (self::ENCRYPTION[$highestAvailableEncryption] > self::ENCRYPTION[$useHighestEncryption])
            $highestAvailableEncryption = $useHighestEncryption;
        switch($highestAvailableEncryption){
            case 'Argon2i':
                return $this->encodeArgon2iPassword($raw, $salt);
            case 'BCRYPT':
                return $this->encodeBCryptPassword($raw, $salt);
        }
        return $this->encodeSHA256Password($raw, $salt);
    }

    /**
     * @var string
     */
    private $currentEncryption;

    /**
     * @return string
     */
    public function getCurrentEncryption(): string
    {
        return $this->currentEncryption;
    }
}