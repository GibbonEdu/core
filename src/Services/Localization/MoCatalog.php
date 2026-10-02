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

namespace Gibbon\Services\Localization;

/**
 *
 * Used when the system locale cannot be activated for native gettext
 *
 * @version v30
 * @since   v30
 */
class MoCatalog
{
    /**
     * 
     * .mo once, and automatically reload if the file is updated on disk.
     *
     * @var array<string, self>
     */
    protected static $cache = [];

    /**
     * @var array<string, string|array<int, string>>
     */
    protected $entries = [];

    /**
     * @var int
     */
    protected $nplurals = 2;

    /**
     * @var callable|null
     */
    protected $pluralFunc;

    /**
     * Load a .mo file from disk
     *
     * @param string
     *
     * @return static|null
     */
    public static function load(string $moFile): ?self
    {
        if ($moFile === '' || !is_readable($moFile)) {
            return null;
        }

        $mtime = filemtime($moFile);
        if ($mtime === false) {
            return null;
        }

        $cacheKey = $moFile.'@'.$mtime;
        if (isset(static::$cache[$cacheKey])) {
            return static::$cache[$cacheKey];
        }

        $data = file_get_contents($moFile);
        if ($data === false || strlen($data) < 28) {
            return null;
        }

        $catalog = new self();
        if (!$catalog->parse($data)) {
            return null;
        }

        // Drop older mtimes for this path so memory does not grow after updates.
        foreach (array_keys(static::$cache) as $key) {
            if (strpos($key, $moFile.'@') === 0) {
                unset(static::$cache[$key]);
            }
        }

        static::$cache[$cacheKey] = $catalog;

        return $catalog;
    }

    /**
     * Clear the process-lifetime cache
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }

    /**
     * Translate a singular message id.
     */
    public function translate(string $msgid): string
    {
        if ($msgid === '') {
            return $msgid;
        }

        if (!array_key_exists($msgid, $this->entries)) {
            return $msgid;
        }

        $value = $this->entries[$msgid];
        if (is_array($value)) {
            return $value[0] ?? $msgid;
        }

        return $value !== '' ? $value : $msgid;
    }

    /**
     * Translate a plural message id.
     */
    public function translatePlural(string $singular, string $plural, int $n): string
    {
        $key = $singular . "\0" . $plural;
        $index = $this->pluralIndex($n);

        if (array_key_exists($key, $this->entries) && is_array($this->entries[$key])) {
            $forms = $this->entries[$key];
            if (isset($forms[$index]) && $forms[$index] !== '') {
                return $forms[$index];
            }
            if (isset($forms[0]) && $forms[0] !== '') {
                return $forms[0];
            }
        }

        // Singular-only entries may still exist under the singular msgid.
        if ($index === 0 && array_key_exists($singular, $this->entries)) {
            $value = $this->entries[$singular];
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $n == 1 ? $singular : $plural;
    }

    /**
     * Parse binary .mo contents.
     */
    protected function parse(string $data): bool
    {
        // Magic 0x950412de: little-endian on disk is de 12 04 95, big-endian is 95 04 12 de.
        $magic = substr($data, 0, 4);
        if ($magic === "\xde\x12\x04\x95") {
            $unpack = 'V'; // little-endian
        } elseif ($magic === "\x95\x04\x12\xde") {
            $unpack = 'N'; // big-endian
        } else {
            return false;
        }

        $header = unpack($unpack . 'revision/' . $unpack . 'count/' . $unpack . 'originals/' . $unpack . 'translations', substr($data, 4, 16));
        if ($header === false || ($header['revision'] ?? 1) > 1) {
            return false;
        }

        $count = $header['count'];
        $originals = $header['originals'];
        $translations = $header['translations'];
        $length = strlen($data);

        for ($i = 0; $i < $count; $i++) {
            $oOffset = $originals + ($i * 8);
            $tOffset = $translations + ($i * 8);
            if ($oOffset + 8 > $length || $tOffset + 8 > $length) {
                return false;
            }

            $oMeta = unpack($unpack . 'length/' . $unpack . 'offset', substr($data, $oOffset, 8));
            $tMeta = unpack($unpack . 'length/' . $unpack . 'offset', substr($data, $tOffset, 8));
            if ($oMeta === false || $tMeta === false) {
                return false;
            }

            if ($oMeta['offset'] + $oMeta['length'] > $length || $tMeta['offset'] + $tMeta['length'] > $length) {
                return false;
            }

            $msgid = $oMeta['length'] > 0 ? substr($data, $oMeta['offset'], $oMeta['length']) : '';
            $msgstr = $tMeta['length'] > 0 ? substr($data, $tMeta['offset'], $tMeta['length']) : '';

            if ($msgid === '') {
                $this->parseHeader($msgstr);
                continue;
            }

            if (strpos($msgid, "\0") !== false) {
                $this->entries[$msgid] = explode("\0", $msgstr);
            } else {
                $this->entries[$msgid] = $msgstr;
            }
        }

        if ($this->pluralFunc === null) {
            $this->nplurals = 2;
            $this->pluralFunc = static function (int $n): int {
                return $n == 1 ? 0 : 1;
            };
        }

        return true;
    }

    /**
     * Parse Plural-Forms from the catalog header.
     */
    protected function parseHeader(string $header): void
    {
        if (!preg_match('/Plural-Forms:\s*nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*([^;\\n]+)/i', $header, $matches)) {
            return;
        }

        $nplurals = (int) $matches[1];
        $expr = trim($matches[2]);

        // Only allow a safe subset of characters from gettext plural expressions.
        if ($nplurals < 1 || $nplurals > 6 || !preg_match('/^[n0-9\s?:\-%<=>!&|()]+$/', $expr)) {
            return;
        }

        $expr = $this->parenthesizeTernaries($expr);

        $this->nplurals = $nplurals;
        $this->pluralFunc = static function (int $n) use ($expr): int {
            try {
                $result = 0;
                eval('$result = (int) (' . $expr . ');');
                return $result;
            } catch (\Throwable $e) {
                return $n == 1 ? 0 : 1;
            }
        };
    }

    /**
     * Make nested ternary operators explicitly right-associative for PHP 8+.
     */
    protected function parenthesizeTernaries(string $expr): string
    {
        $expr = trim($expr);

        // Unwrap balanced outer parentheses so nested ternaries inside
        while ($this->hasBalancedOuterParentheses($expr)) {
            $expr = trim(substr($expr, 1, -1));
        }

        $length = strlen($expr);
        $depth = 0;
        $questionPos = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $expr[$i];

            if ($char === '(') {
                $depth++;
                continue;
            }
            if ($char === ')') {
                $depth--;
                continue;
            }
            if ($depth !== 0) {
                continue;
            }

            if ($char === '?' && $questionPos === null) {
                $questionPos = $i;
                continue;
            }

            if ($char === ':' && $questionPos !== null) {
                $condition = trim(substr($expr, 0, $questionPos));
                $ifTrue = $this->parenthesizeTernaries(trim(substr($expr, $questionPos + 1, $i - $questionPos - 1)));
                $ifFalse = $this->parenthesizeTernaries(trim(substr($expr, $i + 1)));

                return $condition.' ? '.$ifTrue.' : ('.$ifFalse.')';
            }
        }

        return $expr;
    }

    /**
     * Whether the expression is fully wrapped in one balanced parenthesis pair.
     */
    protected function hasBalancedOuterParentheses(string $expr): bool
    {
        if ($expr === '' || $expr[0] !== '(' || substr($expr, -1) !== ')') {
            return false;
        }

        $depth = 0;
        $length = strlen($expr);
        for ($i = 0; $i < $length; $i++) {
            if ($expr[$i] === '(') {
                $depth++;
            } elseif ($expr[$i] === ')') {
                $depth--;
                if ($depth === 0 && $i < $length - 1) {
                    return false;
                }
                if ($depth < 0) {
                    return false;
                }
            }
        }

        return $depth === 0;
    }

    protected function pluralIndex(int $n): int
    {
        $n = abs($n);
        $index = 0;
        if (is_callable($this->pluralFunc)) {
            $index = (int) ($this->pluralFunc)($n);
        }

        if ($index < 0) {
            $index = 0;
        }
        if ($index >= $this->nplurals) {
            $index = $this->nplurals - 1;
        }

        return $index;
    }
}
