<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Exception\DumpException;

/**
 * Inline implements a YAML parser/dumper for the YAML inline syntax.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
class Inline
{
    const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\']*(?:\'\'[^\']*)*)\')';

    private static $exceptionOnInvalidType = false;
    private static $objectSupport = false;
    private static $objectForMap = false;

    /**
     * Converts a YAML string to a PHP array.
     *
     * @param string $value      A YAML string
     * @param int    $flags      A bit field of PARSE_* constants to customize the YAML parser behavior
     * @param array  $references Mapping of variable names to values
     *
     * @return array A PHP array representing the YAML string
     *
     * @throws ParseException
     */
    public static function parse($value, $flags = 0, $references = array())
    {
        if (is_bool($flags)) {
            @trigger_error('Passing a boolean flag to toggle exception handling is deprecated since version 3.1 and will be removed in 4.0. Use the Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE flag instead.', E_USER_DEPRECATED);

            if ($flags) {
                $flags = Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;
            } else {
                $flags = 0;
            }
        }

        if (func_num_args() >= 3 && !is_array($references)) {
            @trigger_error('Passing a boolean flag to toggle object support is deprecated since version 3.1 and will be removed in 4.0. Use the Yaml::PARSE_OBJECT flag instead.', E_USER_DEPRECATED);

            if ($references) {
                $flags |= Yaml::PARSE_OBJECT;
            }

            if (func_num_args() >= 4) {
                @trigger_error('Passing a boolean flag to toggle object for map support is deprecated since version 3.1 and will be removed in 4.0. Use the Yaml::PARSE_OBJECT_FOR_MAP flag instead.', E_USER_DEPRECATED);

                if (func_get_arg(3)) {
                    $flags |= Yaml::PARSE_OBJECT_FOR_MAP;
                }
            }

            if (func_num_args() >= 5) {
                $references = func_get_arg(4);
            } else {
                $references = array();
            }
        }

        self::$exceptionOnInvalidType = (bool) (Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE & $flags);
        self::$objectSupport = (bool) (Yaml::PARSE_OBJECT & $flags);
        self::$objectForMap = (bool) (Yaml::PARSE_OBJECT_FOR_MAP & $flags);

        $value = trim($value);

        if ('' === $value) {
            return '';
        }

        if (2 /* MB_OVERLOAD_STRING */ & (int) ini_get('mbstring.func_overload')) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        $i = 0;
        switch ($value[0]) {
            case '[':
                $result = self::parseSequence($value, $flags, $i, $references);
                ++$i;
                break;
            case '{':
                $result = self::parseMapping($value, $flags, $i, $references);
                ++$i;
                break;
            default:
                $result = self::parseScalar($value, $flags, null, array('"', "'"), $i, true, $references);
        }

        // some comments are allowed at the end
        if (preg_replace('/\s+#.*$/A', '', substr($value, $i))) {
            throw new ParseException(sprintf('Unexpected characters near "%s".', substr($value, $i)));
        }

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return $result;
    }

    /**
     * Dumps a given PHP variable to a YAML string.
     *
     * @param mixed $value The PHP variable to convert
     * @param int   $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string
     *
     * @return string The YAML string representing the PHP array
     *
     * @throws DumpException When trying to dump PHP resource
     */
    public static function dump($value, $flags = 0)
    {
        if (is_bool($flags)) {
            @trigger_error('Passing a boolean flag to toggle exception handling is deprecated since version 3.1 and will be removed in 4.0. Use the Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE flag instead.', E_USER_DEPRECATED);

            if ($flags) {
                $flags = Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE;
            } else {
                $flags = 0;
            }
        }

        if (func_num_args() >= 3) {
            @trigger_error('Passing a boolean flag to toggle object support is deprecated since version 3.1 and will be removed in 4.0. Use the Yaml::DUMP_OBJECT flag instead.', E_USER_DEPRECATED);

            if (func_get_arg(2)) {
                $flags |= Yaml::DUMP_OBJECT;
            }
        }

        switch (true) {
            case is_resource($value):
                if (Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE & $flags) {
                    throw new DumpException(sprintf('Unable to dump PHP resources in a YAML file ("%s").', get_resource_type($value)));
                }

                return 'null';
            case $value instanceof \DateTimeInterface:
                return $value->format('c');
            case is_object($value):
                if (Yaml::DUMP_OBJECT & $flags) {
                    return '!php/object:'.serialize($value);
                }

                if (Yaml::DUMP_OBJECT_AS_MAP & $flags && ($value instanceof \stdClass || $value instanceof \ArrayObject)) {
                    return self::dumpArray((array) $value, $flags);
                }

                if (Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE & $flags) {
                    throw new DumpException('Object support when dumping a YAML file has been disabled.');
                }

                return 'null';
            case is_array($value):
                return self::dumpArray($value, $flags);
            case null === $value:
                return 'null';
            case true === $value:
                return 'true';
            case false === $value:
                return 'false';
            case ctype_digit($value):
                return is_string($value) ? "'$value'" : (int) $value;
            case is_numeric($value):
                $locale = setlocale(LC_NUMERIC, 0);
                if (false !== $locale) {
                    setlocale(LC_NUMERIC, 'C');
                }
                if (is_float($value)) {
                    $repr = (string) $value;
                    if (is_infinite($value)) {
                        $repr = str_ireplace('INF', '.Inf', $repr);
                    } elseif (floor($value) == $value && $repr == $value) {
                        // Preserve float data type since storing a whole number will result in integer value.
                        $repr = '!!float '.$repr;
                    }
                } else {
                    $repr = is_string($value) ? "'$value'" : (string) $value;
                }
                if (false !== $locale) {
                    setlocale(LC_NUMERIC, $locale);
                }

                return $repr;
            case '' == $value:
                return "''";
            case self::isBinaryString($value):
                return '!!binary '.base64_encode($value);
            case Escaper::requiresDoubleQuoting($value):
                return Escaper::escapeWithDoubleQuotes($value);
            case Escaper::requiresSingleQuoting($value):
            case preg_match(self::getHexRegex(), $value):
            case preg_match(self::getTimestampRegex(), $value):
                return Escaper::escapeWithSingleQuotes($value);
            default:
                return $value;
        }
    }

    /**
     * Check if given array is hash or just normal indexed array.
     *
     * @internal
     *
     * @param array $value The PHP array to check
     *
     * @return bool true if value is hash array, false otherwise
     */
    public static function isHash(array $value)
    {
        $expectedKey = 0;

        foreach ($value as $key => $val) {
            if ($key !== $expectedKey++) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * @param array $value The PHP array to dump
     * @param int   $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string
     *
     * @return string The YAML string representing the PHP array
     */
    private static function dumpArray($value, $flags)
    {
        // array
        if ($value && !self::isHash($value)) {
            $output = array();
            foreach ($value as $val) {
                $output[] = self::dump($val, $flags);
            }

            return sprintf('[%s]', implode(', ', $output));
        }

        // hash
        $output = array();
        foreach ($value as $key => $val) {
            $output[] = sprintf('%s: %s', self::dump($key, $flags), self::dump($val, $flags));
        }

        return sprintf('{ %s }', implode(', ', $output));
    }

    /**
     * Parses a scalar to a YAML string.
     *
     * @param string $scalar
     * @param int    $flags
     * @param string $delimiters
     * @param array  $stringDelimiters
     * @param int    &$i
     * @param bool   $evaluate
     * @param array  $references
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     *
     * @internal
     */
    public static function parseScalar($scalar, $flags = 0, $delimiters = null, $stringDelimiters = array('"', "'"), &$i = 0, $evaluate = true, $references = array())
    {
        if (in_array($scalar[$i], $stringDelimiters)) {
            // quoted scalar
            $output = self::parseQuotedScalar($scalar, $i);

            if (null !== $delimiters) {
                $tmp = ltrim(substr($scalar, $i), ' ');
                if (!in_array($tmp[0], $delimiters)) {
                    throw new ParseException(sprintf('Unexpected characters (%s).', substr($scalar, $i)));
                }
            }
        } else {
            // "normal" string
            if (!$delimiters) {
                $output = substr($scalar, $i);
                $i += strlen($output);

                // remove comments
                if (preg_match('/[ \t]+#/', $output, $match, PREG_OFFSET_CAPTURE)) {
                    $output = substr($output, 0, $match[0][1]);
                }
            } elseif (preg_match('/^(.+?)('.implode('|', $delimiters).')/', substr($scalar, $i), $match)) {
                $output = $match[1];
                $i += strlen($output);
            } else {
                throw new ParseException(sprintf('Malformed inline YAML string (%s).', $scalar));
            }

            // a non-quoted string cannot start with @ or ` (reserved) nor with a scalar indicator (| or >)
            if ($output && ('@' === $output[0] || '`' === $output[0] || '|' === $output[0] || '>' === $output[0])) {
                throw new ParseException(sprintf('The reserved indicator "%s" cannot start a plain scalar; you need to quote the scalar.', $output[0]));
            }

            if ($output && '%' === $output[0]) {
                @trigger_error(sprintf('Not quoting the scalar "%s" starting with the "%%" indicator character is deprecated since Symfony 3.1 and will throw a ParseException in 4.0.' , $output), E_USER_DEPRECATED);
            }

            if ($evaluate) {
                $output = self::evaluateScalar($output, $flags, $references);
            }
        }

        return $output;
    }

    /**
     * Parses a quoted scalar to YAML.
     *
     * @param string $scalar
     * @param int    &$i
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseQuotedScalar($scalar, &$i)
    {
        if (!preg_match('/'.self::REGEX_QUOTED_STRING.'/Au', substr($scalar, $i), $match)) {
            throw new ParseException(sprintf('Malformed inline YAML string (%s).', substr($scalar, $i)));
        }

        $output = substr($match[0], 1, strlen($match[0]) - 2);

        $unescaper = new Unescaper();
        if ('"' == $scalar[$i]) {
            $output = $unescaper->unescapeDoubleQuotedString($output);
        } else {
            $output = $unescaper->unescapeSingleQuotedString($output);
        }

        $i += strlen($match[0]);

        return $output;
    }

    /**
     * Parses a sequence to a YAML string.
     *
     * @param string $sequence
     * @param int    $flags
     * @param int    &$i
     * @param array  $references
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseSequence($sequence, $flags, &$i = 0, $references = array())
    {
        $output = array();
        $len = strlen($sequence);
        ++$i;

        // [foo, bar, ...]
        while ($i < $len) {
            switch ($sequence[$i]) {
                case '[':
                    // nested sequence
                    $output[] = self::parseSequence($sequence, $flags, $i, $references);
                    break;
                case '{':
                    // nested mapping
                    $output[] = self::parseMapping($sequence, $flags, $i, $references);
                    break;
                case ']':
                    return $output;
                case ',':
                case ' ':
                    break;
                default:
                    $isQuoted = in_array($sequence[$i], array('"', "'"));
                    $value = self::parseScalar($sequence, $flags, array(',', ']'), array('"', "'"), $i, true, $references);

                    // the value can be an array if a reference has been resolved to an array var
                    if (is_string($value) && !$isQuoted && false !== strpos($value, ': ')) {
                        // embedded mapping?
                        try {
                            $pos = 0;
                            $value = self::parseMapping('{'.$value.'}', $flags, $pos, $references);
                        } catch (\InvalidArgumentException $e) {
                            // no, it's not
                        }
                    }

                    $output[] = $value;

                    --$i;
            }

            ++$i;
        }

        throw new ParseException(sprintf('Malformed inline YAML string %s', $sequence));
    }

    /**
     * Parses a mapping to a YAML string.
     *
     * @param string $mapping
     * @param int    $flags
     * @param int    &$i
     * @param array  $references
     *
     * @return string A YAML string
     *
     * @throws ParseException When malformed inline YAML string is parsed
     */
    private static function parseMapping($mapping, $flags, &$i = 0, $references = array())
    {
        $output = array();
        $len = strlen($mapping);
        ++$i;

        // {foo: bar, bar:foo, ...}
        while ($i < $len) {
            switch ($mapping[$i]) {
                case ' ':
                case ',':
                    ++$i;
                    continue 2;
                case '}':
                    if (self::$objectForMap) {
                        return (object) $output;
                    }

                    return $output;
            }

            // key
            $key = self::parseScalar($mapping, $flags, array(':', ' '), array('"', "'"), $i, false);

            // value
            $done = false;

            while ($i < $len) {
                switch ($mapping[$i]) {
                    case '[':
                        // nested sequence
                        $value = self::parseSequence($mapping, $flags, $i, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        if (!isset($output[$key])) {
                            $output[$key] = $value;
                        }
                        $done = true;
                        break;
                    case '{':
                        // nested mapping
                        $value = self::parseMapping($mapping, $flags, $i, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        if (!isset($output[$key])) {
                            $output[$key] = $value;
                        }
                        $done = true;
                        break;
                    case ':':
                    case ' ':
                        break;
                    default:
                        $value = self::parseScalar($mapping, $flags, array(',', '}'), array('"', "'"), $i, true, $references);
                        // Spec: Keys MUST be unique; first one wins.
                        // Parser cannot abort this mapping earlier, since lines
                        // are processed sequentially.
                        if (!isset($output[$key])) {
                            $output[$key] = $value;
                        }
                        $done = true;
                        --$i;
                }

                ++$i;

                if ($done) {
                    continue 2;
                }
            }
        }

        throw new ParseException(sprintf('Malformed inline YAML string %s', $mapping));
    }

    /**
     * Evaluates scalars and replaces magic values.
     *
     * @param string $scalar
     * @param int    $flags
     * @param array  $references
     *
     * @return string A YAML string
     *
     * @throws ParseException when object parsing support was disabled and the parser detected a PHP object or when a reference could not be resolved
     */
    private static function evaluateScalar($scalar, $flags, $references = array())
    {
        $scalar = trim($scalar);
        $scalarLower = strtolower($scalar);

        if (0 === strpos($scalar, '*')) {
            if (false !== $pos = strpos($scalar, '#')) {
                $value = substr($scalar, 1, $pos - 2);
            } else {
                $value = substr($scalar, 1);
            }

            // an unquoted *
            if (false === $value || '' === $value) {
                throw new ParseException('A reference must contain at least one character.');
            }

            if (!array_key_exists($value, $references)) {
                throw new ParseException(sprintf('Reference "%s" does not exist.', $value));
            }

            return $references[$value];
        }

        switch (true) {
            case 'null' === $scalarLower:
            case '' === $scalar:
            case '~' === $scalar:
                return;
            case 'true' === $scalarLower:
                return true;
            case 'false' === $scalarLower:
                return false;
            // Optimise for returning strings.
            case $scalar[0] === '+' || $scalar[0] === '-' || $scalar[0] === '.' || $scalar[0] === '!' || is_numeric($scalar[0]):
                switch (true) {
                    case 0 === strpos($scalar, '!str'):
                        return (string) substr($scalar, 5);
                    case 0 === strpos($scalar, '! '):
                        return (int) self::parseScalar(substr($scalar, 2), $flags);
                    case 0 === strpos($scalar, '!php/object:'):
                        if (self::$objectSupport) {
                            return unserialize(substr($scalar, 12));
                        }

                        if (self::$exceptionOnInvalidType) {
                            throw new ParseException('Object support when parsing a YAML file has been disabled.');
                        }

                        return;
                    case 0 === strpos($scalar, '!!php/object:'):
                        if (self::$objectSupport) {
                            @trigger_error('The !!php/object tag to indicate dumped PHP objects is deprecated since version 3.1 and will be removed in 4.0. Use the !php/object tag instead.', E_USER_DEPRECATED);

                            return unserialize(substr($scalar, 13));
                        }

                        if (self::$exceptionOnInvalidType) {
                            throw new ParseException('Object support when parsing a YAML file has been disabled.');
                        }

                        return;
                    case 0 === strpos($scalar, '!!float '):
                        return (float) substr($scalar, 8);
                    case ctype_digit($scalar):
                        $raw = $scalar;
                        $cast = (int) $scalar;

                        return '0' == $scalar[0] ? octdec($scalar) : (((string) $raw == (string) $cast) ? $cast : $raw);
                    case '-' === $scalar[0] && ctype_digit(substr($scalar, 1)):
                        $raw = $scalar;
                        $cast = (int) $scalar;

                        return '0' == $scalar[1] ? octdec($scalar) : (((string) $raw === (string) $cast) ? $cast : $raw);
                    case is_numeric($scalar):
                    case preg_match(self::getHexRegex(), $scalar):
                        return '0x' === $scalar[0].$scalar[1] ? hexdec($scalar) : (float) $scalar;
                    case '.inf' === $scalarLower:
                    case '.nan' === $scalarLower:
                        return -log(0);
                    case '-.inf' === $scalarLower:
                        return log(0);
                    case 0 === strpos($scalar, '!!binary '):
                        return self::evaluateBinaryScalar(substr($scalar, 9));
                    case preg_match('/^(-|\+)?[0-9,]+(\.[0-9]+)?$/', $scalar):
                        return (float) str_replace(',', '', $scalar);
                    case preg_match(self::getTimestampRegex(), $scalar):
                        if (Yaml::PARSE_DATETIME & $flags) {
                            return new \DateTime($scalar, new \DateTimeZone('UTC'));
                        }

                        $timeZone = date_default_timezone_get();
                        date_default_timezone_set('UTC');
                        $time = strtotime($scalar);
                        date_default_timezone_set($timeZone);

                        return $time;
                }
            default:
                return (string) $scalar;
        }
    }

    /**
     * @param string $scalar
     *
     * @return string
     *
     * @internal
     */
    public static function evaluateBinaryScalar($scalar)
    {
        $parsedBinaryData = self::parseScalar(preg_replace('/\s/', '', $scalar));

        if (0 !== (strlen($parsedBinaryData) % 4)) {
            throw new ParseException(sprintf('The normalized base64 encoded data (data without whitespace characters) length must be a multiple of four (%d bytes given).', strlen($parsedBinaryData)));
        }

        if (!preg_match('#^[A-Z0-9+/]+={0,2}$#i', $parsedBinaryData)) {
            throw new ParseException(sprintf('The base64 encoded data (%s) contains invalid characters.', $parsedBinaryData));
        }

        return base64_decode($parsedBinaryData, true);
    }

    private static function isBinaryString($value)
    {
        return !preg_match('//u', $value) || preg_match('/[^\x09-\x0d\x20-\xff]/', $value);
    }

    /**
     * Gets a regex that matches a YAML date.
     *
     * @return string The regular expression
     *
     * @see http://www.yaml.org/spec/1.2/spec.html#id2761573
     */
    private static function getTimestampRegex()
    {
        return <<<EOF
        ~^
        (?P<year>[0-9][0-9][0-9][0-9])
        -(?P<month>[0-9][0-9]?)
        -(?P<day>[0-9][0-9]?)
        (?:(?:[Tt]|[ \t]+)
        (?P<hour>[0-9][0-9]?)
        :(?P<minute>[0-9][0-9])
        :(?P<second>[0-9][0-9])
        (?:\.(?P<fraction>[0-9]*))?
        (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
        (?::(?P<tz_minute>[0-9][0-9]))?))?)?
        $~x
EOF;
    }

    /**
     * Gets a regex that matches a YAML number in hexadecimal notation.
     *
     * @return string
     */
    private static function getHexRegex()
    {
        return '~^0x[0-9a-f]++$~i';
    }
}
