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
 * Used when the system locale cannot be activated for native gettext.
 *
 * @version v30
 * @since   v30
 */
class TranslationCatalog
{
    /**
     * The next request uses the compiled file cache.
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
    protected $pluralCount = 2;

    /**
     * Sanitised Plural-Forms expression
     * @var string|null
     */
    protected $pluralExpression;

    /**
     * @var callable|null
     */
    protected $pluralFunction;

    /**
     * Plural indexes already computed for this catalog, keyed by absolute $n.
     *
     * @var array<int, int>
     */
    protected $pluralIndexes = [];

    /**
     * Load a .mo file from disk
     *
     * @param string $filePath
     *
     * @return static|null
     */
    public static function load(string $filePath) : ?self
    {
        if ($filePath === '' || !is_readable($filePath)) {
            return null;
        }

        $modifiedTime = filemtime($filePath);
        if ($modifiedTime === false) {
            return null;
        }

        $cacheKey = $filePath.'@'.$modifiedTime;
        if (isset(static::$cache[$cacheKey])) {
            return static::$cache[$cacheKey];
        }

        $catalog = static::readCache($filePath, $modifiedTime);
        if (!$catalog instanceof self) {
            $data = file_get_contents($filePath);
            if ($data === false || strlen($data) < 28) {
                return null;
            }

            $catalog = new self();
            if (!$catalog->parse($data)) {
                return null;
            }

            static::writeCache($filePath, $modifiedTime, $catalog);
        }

        // Drop older mtimes for this path so memory does not grow after updates.
        foreach (array_keys(static::$cache) as $key) {
            if (strpos($key, $filePath.'@') === 0) {
                unset(static::$cache[$key]);
            }
        }

        static::$cache[$cacheKey] = $catalog;

        return $catalog;
    }

    /**
     * Compiled catalogs live in the system temp directory, one file per .mo path.
     */
    protected static function getCacheFilePath(string $filePath) : string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'gibbon-mo'.DIRECTORY_SEPARATOR.md5($filePath).'.ser';
    }

    /**
     * Read a previously parsed catalog. Returns null when missing, stale, or unusable.
     */
    protected static function readCache(string $filePath, int $modifiedTime) : ?self
    {
        $path = static::getCacheFilePath($filePath);
        if (!is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        $cacheData = unserialize($contents, ['allowed_classes' => false]);
        if (!is_array($cacheData) || ($cacheData['mtime'] ?? null) !== $modifiedTime) {
            return null;
        }
        if (!isset($cacheData['entries']) || !is_array($cacheData['entries'])) {
            return null;
        }

        $pluralCount = $cacheData['nplurals'] ?? 2;
        if (!is_int($pluralCount) || $pluralCount < 1 || $pluralCount > 6) {
            return null;
        }

        $pluralExpression = $cacheData['plural'] ?? null;
        if ($pluralExpression !== null && (!is_string($pluralExpression) || !static::isSafePluralExpression($pluralExpression))) {
            return null;
        }

        $catalog = new self();
        $catalog->entries = $cacheData['entries'];
        $catalog->setPluralRule($pluralCount, is_string($pluralExpression) ? $pluralExpression : null);

        return $catalog;
    }

    /**
     * Write the parsed map so the next request skips the binary .mo walk
     */
    protected static function writeCache(string $filePath, int $modifiedTime, self $catalog) : void
    {
        $cacheDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gibbon-mo';
        if (!is_dir($cacheDirectory) && !@mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
            return;
        }

        $cacheData = serialize([
            'mtime' => $modifiedTime,
            'entries' => $catalog->entries,
            'nplurals' => $catalog->pluralCount,
            'plural' => $catalog->pluralExpression,
        ]);

        $path = static::getCacheFilePath($filePath);
        $temporaryPath = $path.'.'.getmypid().'.tmp';
        if (file_put_contents($temporaryPath, $cacheData, LOCK_EX) === false) {
            return;
        }
        if (!@rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
        }
    }

    /**
     * Gettext plural expressions are arithmetic and comparisons on n.
     */
    protected static function isSafePluralExpression(string $expression) : bool
    {
        return (bool) preg_match('/^[n0-9\s?:\-%<=>!&|()]+$/', $expression);
    }

    /**
     * Clear the process-lifetime cache
     */
    public static function clearCache() : void
    {
        static::$cache = [];
    }

    /**
     * Translate a singular message id.
     */
    public function translate(string $text) : string
    {
        if ($text === '') {
            return $text;
        }

        if (!array_key_exists($text, $this->entries)) {
            return $text;
        }

        $value = $this->entries[$text];
        if (is_array($value)) {
            return $value[0] ?? $text;
        }

        return $value !== '' ? $value : $text;
    }

    /**
     * Translate a plural message id.
     */
    public function translateN(string $singular, string $plural, int $n) : string
    {
        $key = $singular."\0".$plural;
        $index = $this->getPluralIndex($n);

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
    protected function parse(string $data) : bool
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

        $header = unpack($unpack.'revision/'.$unpack.'count/'.$unpack.'originals/'.$unpack.'translations', substr($data, 4, 16));
        if ($header === false || ($header['revision'] ?? 1) > 1) {
            return false;
        }

        $count = $header['count'];
        $originals = $header['originals'];
        $translations = $header['translations'];
        $length = strlen($data);

        for ($i = 0; $i < $count; $i++) {
            $originalOffset = $originals + ($i * 8);
            $translationOffset = $translations + ($i * 8);
            if ($originalOffset + 8 > $length || $translationOffset + 8 > $length) {
                return false;
            }

            $original = unpack($unpack.'length/'.$unpack.'offset', substr($data, $originalOffset, 8));
            $translation = unpack($unpack.'length/'.$unpack.'offset', substr($data, $translationOffset, 8));
            if ($original === false || $translation === false) {
                return false;
            }

            if ($original['offset'] + $original['length'] > $length || $translation['offset'] + $translation['length'] > $length) {
                return false;
            }

            $originalText = $original['length'] > 0 ? substr($data, $original['offset'], $original['length']) : '';
            $translatedText = $translation['length'] > 0 ? substr($data, $translation['offset'], $translation['length']) : '';

            if ($originalText === '') {
                $this->parseHeader($translatedText);
                continue;
            }

            if (strpos($originalText, "\0") !== false) {
                $this->entries[$originalText] = explode("\0", $translatedText);
            } else {
                $this->entries[$originalText] = $translatedText;
            }
        }

        if ($this->pluralFunction === null) {
            $this->setPluralRule(2, null);
        }

        return true;
    }

    /**
     * Parse Plural-Forms from the catalog header.
     */
    protected function parseHeader(string $header) : void
    {
        if (!preg_match('/Plural-Forms:\s*nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*([^;\\n]+)/i', $header, $matches)) {
            return;
        }

        $pluralCount = (int) $matches[1];
        $expression = trim($matches[2]);

        // Only allow a safe subset of characters from gettext plural expressions.
        if ($pluralCount < 1 || $pluralCount > 6 || !preg_match('/^[n0-9\s?:\-%<=>!&|()]+$/', $expression)) {
            return;
        }

        $expression = $this->parenthesizeTernaries($expression);
        $this->setPluralRule($pluralCount, $expression);
    }

    /**
     * Install a plural rule. A null expression uses the English two-form rule.
     */
    protected function setPluralRule(int $pluralCount, ?string $expression) : void
    {
        $this->pluralCount = $pluralCount;
        $this->pluralIndexes = [];

        if ($expression === null || $expression === '' || !static::isSafePluralExpression($expression)) {
            $this->pluralExpression = null;
            $this->pluralFunction = static function (int $n) : int {
                return $n == 1 ? 0 : 1;
            };
            return;
        }

        $this->pluralExpression = $expression;
        // Gettext writes the count as n. PHP eval needs $n.
        $phpExpression = str_replace('n', '$n', $expression);
        $this->pluralFunction = static function (int $n) use ($phpExpression) : int {
            try {
                $result = 0;
                eval('$result = (int) ('.$phpExpression.');');
                return $result;
            } catch (\Throwable $e) {
                return $n == 1 ? 0 : 1;
            }
        };
    }

    /**
     * Make nested ternary operators explicitly right-associative for PHP 8+.
     */
    protected function parenthesizeTernaries(string $expression) : string
    {
        $expression = trim($expression);

        // Unwrap balanced outer parentheses so nested ternaries inside
        while ($this->hasBalancedOuterParentheses($expression)) {
            $expression = trim(substr($expression, 1, -1));
        }

        $length = strlen($expression);
        $depth = 0;
        $questionPosition = null;

        for ($i = 0; $i < $length; $i++) {
            $character = $expression[$i];

            if ($character === '(') {
                $depth++;
                continue;
            }
            if ($character === ')') {
                $depth--;
                continue;
            }
            if ($depth !== 0) {
                continue;
            }

            if ($character === '?' && $questionPosition === null) {
                $questionPosition = $i;
                continue;
            }

            if ($character === ':' && $questionPosition !== null) {
                $condition = trim(substr($expression, 0, $questionPosition));
                $valueWhenTrue = $this->parenthesizeTernaries(trim(substr($expression, $questionPosition + 1, $i - $questionPosition - 1)));
                $valueWhenFalse = $this->parenthesizeTernaries(trim(substr($expression, $i + 1)));

                return $condition.' ? '.$valueWhenTrue.' : ('.$valueWhenFalse.')';
            }
        }

        return $expression;
    }

    /**
     * Whether the expression is fully wrapped in one balanced parenthesis pair.
     */
    protected function hasBalancedOuterParentheses(string $expression) : bool
    {
        if ($expression === '' || $expression[0] !== '(' || substr($expression, -1) !== ')') {
            return false;
        }

        $depth = 0;
        $length = strlen($expression);
        for ($i = 0; $i < $length; $i++) {
            if ($expression[$i] === '(') {
                $depth++;
            } elseif ($expression[$i] === ')') {
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

    protected function getPluralIndex(int $n) : int
    {
        $n = abs($n);
        if (isset($this->pluralIndexes[$n])) {
            return $this->pluralIndexes[$n];
        }

        $index = 0;
        if (is_callable($this->pluralFunction)) {
            $index = (int) ($this->pluralFunction)($n);
        }

        if ($index < 0) {
            $index = 0;
        }
        if ($index >= $this->pluralCount) {
            $index = $this->pluralCount - 1;
        }

        // Pages repeat the same counts
        if (count($this->pluralIndexes) < 64) {
            $this->pluralIndexes[$n] = $index;
        }

        return $index;
    }
}
