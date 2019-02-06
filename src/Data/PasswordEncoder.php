<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Date: 4/01/2019
 * Time: 15:22
 */
namespace Gibbon\Data;

/**
 * Class PasswordEncoder
 *
 * This class will always try to encode the password at the encryption level defined.
 * @package Gibbon\Data
 */
class PasswordEncoder
{
    /**
     * @var array
     */
    CONST ENCRYPTION = [
        'Argon2i' => 8,
        'BCrypt' => 4,
        'SHA256' => 2,
        'MD5' => 1,
    ];

    /**
     * @return string
     */
    public function getHighestAvailableEncryption()
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
            return 'BCrypt';
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
        if ($this->getHighestAvailableEncryption() !== 'Argon2i')
            return false;
        if (\PHP_VERSION_ID >= 70200) {
            self::$currentEncryption = 'Argon2i';
            return !$this->isPasswordTooLong($raw) && password_verify($raw, $encoded);
        }
        if (\function_exists('sodium_crypto_pwhash_str_verify')) {
            self::$currentEncryption = 'Argon2i';
            $valid = !$this->isPasswordTooLong($raw) && \sodium_crypto_pwhash_str_verify($encoded, $raw);
            \sodium_memzero($raw);

            return $valid;
        }
        if (\extension_loaded('libsodium')) {
            $valid = !$this->isPasswordTooLong($raw) && \Sodium\crypto_pwhash_str_verify($encoded, $raw);
            \Sodium\memzero($raw);
            self::$currentEncryption = 'Argon2i';

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
    public function encodeArgon2iPassword($raw, $salt = null)
    {
        if ($this->getHighestAvailableEncryption() !== 'Argon2i')
            return null;
        if ($this->isPasswordTooLong($raw)) {
            throw new \Exception('Invalid password.');
        }

        if (\function_exists('sodium_crypto_pwhash_str')) {
            return $this->encodePasswordSodiumFunction($raw);
        }
        if (\extension_loaded('libsodium')) {
            return $this->encodePasswordSodiumExtension($raw);
        }
        if (\PHP_VERSION_ID >= 50500) {
            return $this->encodePasswordNative($raw);
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
     *
     * @return string The encoded password
     *
     * @throws \Exception when the given password is too long
     *
     * @see http://lxr.php.net/xref/PHP_5_5/ext/standard/password.c#111
     */
    private function encodePasswordNative($raw)
    {
        if (!in_array($this->getHighestAvailableEncryption(), ['Argon2i', 'BCrypt']))
            return null;
        if ($this->isPasswordTooLong($raw)) {
            throw new \Exception('Invalid password.');
        }

        if (\PHP_VERSION_ID >= 70300) {
            $options = ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3];
            return password_hash($raw, PASSWORD_ARGON2ID, $options);
        }
        if (\PHP_VERSION_ID >= 70200) {
            $options = ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3];
            return password_hash($raw, PASSWORD_ARGON2I, $options);
        }

        $options = array('cost' => 15);
        return password_hash($raw, PASSWORD_BCRYPT, $options);
    }

    /**
     * @param $encoded
     * @param $raw
     * @return bool
     */
    private function isBCryptPasswordValid($encoded, $raw)
    {
        if (!in_array($this->getHighestAvailableEncryption(), ['Argon2i', 'BCrypt']))
            return false;
        self::$currentEncryption = 'BCrypt';
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
            self::$currentEncryption = 'SHA256';
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
    private function isMD5PasswordValid($encoded, $raw): bool
    {
        if (strtolower($encoded) === md5($raw))
        {
            self::$currentEncryption = 'MD5';
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
        self::$currentEncryption = null;
        if ($this->isArgon2iPasswordValid($encoded, $raw, $salt))
            return true;
        self::$currentEncryption = null;
        if ($this->isBCryptPasswordValid($encoded, $raw, $salt))
            return true;
        self::$currentEncryption = null;
        if ($this->isSHA256PasswordValid($encoded, $raw, $salt))
            return true;
        self::$currentEncryption = null;
        if ($this->isMD5PasswordValid($encoded, $raw))
            return true;
        return false;
    }

    /**
     * Encode Password
     *
     * @param $raw
     * @param null $useHighestEncryption
     * @return string|null
     * @throws \Exception
     */
    public function encodePassword($raw, $useHighestEncryption = null)
    {
        $highestAvailableEncryption = $this->getHighestAvailableEncryption();
        if (empty($useHighestEncryption))
            $useHighestEncryption = $highestAvailableEncryption;
        if (self::ENCRYPTION[$highestAvailableEncryption] > self::ENCRYPTION[$useHighestEncryption])
            $highestAvailableEncryption = $useHighestEncryption;
        switch(strtoupper($highestAvailableEncryption)){
            case 'ARGON2I':
                self::$currentEncryption = "Argon2i";
                return $this->encodeArgon2iPassword($raw);
            case 'BCRYPT':
                self::$currentEncryption = "BCrypt";
                return $this->encodePasswordNative($raw);
        }
        self::$currentEncryption = "SHA256"; //Need to set this so that salt is correctly generated for SHA256
        return $this->encodeSHA256Password($raw, self::getSalt());
    }

    /**
     * @var string|null
     */
    private static $currentEncryption;

    /**
     * @return string|null
     */
    public function getCurrentEncryption()
    {
        return self::$currentEncryption ;
    }

    /**
     * @var string
     */
    private static $salt = '';

    /**
     * getSalt
     *
     * @param bool $refresh
     * @return string
     */
    public static function getSalt(bool $refresh = false)
    {
        if (!$refresh && ! empty(self::$salt))
            return self::$salt;
        if (self::$currentEncryption !== 'SHA256')
            return self::$salt = '';
        $c = explode(' ', '. / a A b B c C d D e E f F g G h H i I j J k K l L m M n N o O p P q Q r R s S t T u U v V w W x X y Y z Z 0 1 2 3 4 5 6 7 8 9');
        $ks = array_rand($c, 22);
        $s = '';
        foreach ($ks as $k) {
            $s .= $c[$k];
        }

        return self::$salt = $s;
    }
}