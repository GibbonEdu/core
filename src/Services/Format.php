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

namespace Gibbon\Services;

use DateTime;
use DateTimeImmutable;
use Gibbon\Http\Url;
use Gibbon\Contracts\Services\Session;

/**
 * Format values based on locale and system settings.
 *
 * @version v16
 * @since   v16
 */
class Format
{
    use FormatResolver;

    public const NONE = -1;
    public const FULL = 0;
    public const LONG = 1;
    public const MEDIUM = 2;
    public const SHORT = 3;
    
    public const FULL_NO_YEAR = 100;
    public const LONG_NO_YEAR = 101;
    public const MEDIUM_NO_YEAR = 102;
    public const SHORT_NO_YEAR = 103;

    protected static $settings = [
        'dateFormatPHP'        => 'd/m/Y',
        'dateTimeFormatPHP'    => 'd/m/Y H:i',
        'timeFormatPHP'        => 'H:i',
        'dateFormatFull'       => 'l, F j',
        'dateFormatLong'       => 'F j',
        'dateFormatMedium'     => 'M j',
        'dateFormatIntlFull'   => 'EEEE, d MMMM',
        'dateFormatIntlLong'   => 'd MMMM',
        'dateFormatIntlMedium' => 'd MMM',
        'dateFormatGenerate'   => true,
    ];

    public static $intlFormatterAvailable = false;

    /**
     * Sets the internal formatting options from an array.
     *
     * @param array $settings
     */
    public static function setup(array $settings)
    {
        static::$settings = array_replace(static::$settings, $settings);
        static::$intlFormatterAvailable = class_exists('IntlDateFormatter');

        // Generate best-fit date formats for this locale, if possible
        if (static::$settings['dateFormatGenerate'] && class_exists('IntlDatePatternGenerator')) {
            $intlPatternGenerator = new \IntlDatePatternGenerator(static::$settings['code']);
            static::$settings['dateFormatIntlFull'] = $intlPatternGenerator->getBestPattern('EEEEMMMMd');
            static::$settings['dateFormatIntlLong'] = $intlPatternGenerator->getBestPattern('MMMMd');
            static::$settings['dateFormatIntlMedium'] = $intlPatternGenerator->getBestPattern('MMMd');
        } else {
            static::$settings['dateFormatIntlFull'] = static::$settings['code'] == 'en_GB' ? 'EEEE, d MMMM' : 'EEEE, MMMM d';
            static::$settings['dateFormatIntlLong'] = static::$settings['code'] == 'en_GB' ? 'd MMMM' : 'MMMM d';
            static::$settings['dateFormatIntlMedium'] = static::$settings['code'] == 'en_GB' ? 'd MMM' : 'MMM d';
        }
    }

    /**
     * Sets the formatting options from session i18n and database settings.
     *
     * @param Session $session
     */
    public static function setupFromSession(Session $session)
    {
        $settings = $session->get('i18n');

        $settings['absolutePath'] = $session->get('absolutePath');
        $settings['absoluteURL'] = $session->get('absoluteURL');
        $settings['gibbonThemeName'] = $session->get('gibbonThemeName');
        $settings['currency'] = $session->get('currency') ?? '';
        $settings['currencySymbol'] = !empty(substr($settings['currency'], 4)) ? substr($settings['currency'], 4) : '';
        $settings['currencyName'] = substr($settings['currency'], 0, 3);
        $settings['nameFormatStaffInformal'] = $session->get('nameFormatStaffInformal');
        $settings['nameFormatStaffInformalReversed'] = $session->get('nameFormatStaffInformalReversed');
        $settings['nameFormatStaffFormal'] = $session->get('nameFormatStaffFormal');
        $settings['nameFormatStaffFormalReversed'] = $session->get('nameFormatStaffFormalReversed');

        static::setup($settings);
    }

    /**
     * Formats a YYYY-MM-DD date with the language-specific format. Optionally provide a format string to use instead.
     *
     * @param DateTime|string $dateString
     * @param string $format
     * @return string
     */
    public static function date($dateString, $format = false)
    {
        if (empty($dateString)) {
            return '';
        }
        $date = static::createDateTime($dateString, is_string($dateString) && strlen($dateString) == 10 ? 'Y-m-d' : null);
        return $date ? $date->format($format ? $format : static::$settings['dateFormatPHP']) : $dateString;
    }

    /**
     * Converts a date in the language-specific format to YYYY-MM-DD.
     *
     * @param DateTime|string $dateString
     * @return string
     */
    public static function dateConvert($dateString)
    {
        if (empty($dateString)) {
            return '';
        }
        $date = static::createDateTime($dateString, static::$settings['dateFormatPHP']);
        return $date ? $date->format('Y-m-d') : $dateString;
    }

    /**
     * Formats a YYYY-MM-DD H:I:S MySQL timestamp as a language-specific string. Optionally provide a format string to use.
     *
     * @param DateTime|string $dateString
     * @param string $format
     * @return string
     */
    public static function dateTime($dateString, $format = false)
    {
        if (empty($dateString)) {
            return '';
        }
        $date = static::createDateTime($dateString, 'Y-m-d H:i:s');
        return $date ? $date->format($format ? $format : static::$settings['dateTimeFormatPHP']) : $dateString;
    }

    /**
     * Formats a YYYY-MM-DD date as a readable string with month names.
     *
     * @param DateTime|string $dateString   The date string to format.
     * @param int|string     $dateFormat    (Optional) An int to specify the date format used with IntlDateFormatter
     *                                      If a string is passed, it will return the default format.
     *                                      See: https://www.php.net/manual/en/class.intldateformatter.php
     *                                      See: https://unicode-org.github.io/icu/userguide/format_parse/datetime/
     *                                      Default: \IntlDateFormatter::MEDIUM
     * @param int|string     $timeFormat    (Optional) An int to specify the time format used with IntlDateFormatter
     *                                      Default: \IntlDateFormatter::NONE
     *
     * @return string  The formatted date string.
     */
    public static function dateReadable($dateString, $dateFormat = null, $timeFormat = null) : string
    {
        if (empty($dateString)) {
            return '';
        }

        if (!static::$intlFormatterAvailable) {
            return static::date($dateString, static::getDateFallback($dateFormat, $timeFormat));
        }

        $formatter = new \IntlDateFormatter(
            static::$settings['code'],
            is_int($dateFormat) && $dateFormat < 100 ? $dateFormat : \IntlDateFormatter::MEDIUM,
            is_int($timeFormat) ? $timeFormat : \IntlDateFormatter::NONE,
            null,
            null,
            static::getDatePattern($dateFormat)
        );

        return mb_convert_case(
            $formatter->format(static::createDateTime($dateString)),
            MB_CASE_TITLE,
        );
    }

    /**
     * A shortcut for formatting a YYYY-MM-DD date as a readable string with month names and times.
     *
     * @param DateTime|string $dateString  The date string to format.

     * @return string  The formatted date string.
     */
    public static function dateTimeReadable($dateString) : string
    {
        return static::dateReadable($dateString, static::MEDIUM, static::SHORT);
    }

    /**
     * Gets a IntlDateFormatter pattern string for a given format constant.
     * Extends the IntlDateFormatter options by adding NO_YEAR options.
     *
     * @param string|int    $dateFormat
     * @return string       The IntlDateFormatter pattern string.
     */
    protected static function getDatePattern($dateFormat = null)
    {
        if (is_string($dateFormat)) {
            return null;
        }
        
        switch ($dateFormat) {
            case static::FULL_NO_YEAR:
                return static::$settings['dateFormatIntlFull'];
            case static::LONG_NO_YEAR:
                return static::$settings['dateFormatIntlLong'];
            case static::MEDIUM_NO_YEAR:
            case static::SHORT_NO_YEAR:
                return static::$settings['dateFormatIntlMedium'];
        }

        return null;
    }

    /**
     * Gets a generic format for DateTime classes, to be used as a fallback
     * when IntlDateFormatter is not available.
     *
     * @param string|int    $dateFormat
     * @param string|int    $timeFormat
     * @return string       The DateTime format string.
     */
    protected static function getDateFallback($dateFormat = null, $timeFormat = null)
    {
        if (is_null($dateFormat)) {
            $dateFormat = static::MEDIUM;
        }

        switch ($dateFormat) {
            case static::NONE:
                $format = '';
                break;
            case static::FULL:
            case static::FULL_NO_YEAR:
                $format = static::$settings['dateFormatFull'];
                break;
            case static::LONG:
            case static::LONG_NO_YEAR:
                $format = static::$settings['dateFormatLong'];
                break;
            default:
                $format = static::$settings['dateFormatMedium'];
        }

        if ($dateFormat >= 0 && $dateFormat < 100) {
            $format .= ' Y'; 
        }

        if ($timeFormat != null && $timeFormat != static::NONE) {
            $format .= !empty($format) ? ', ' : '';
            $format .= $timeFormat == static::FULL
                ? 'H:i:s'
                : 'H:i';
        }

        return $format;
    }

    /**
     * Formats two YYYY-MM-DD dates with the language-specific format. Optionally provide a format string to use instead.
     *
     * @param DateTime|string $dateFrom
     * @param DateTime|string $dateTo
     * @return string
     */
    public static function dateRange($dateFrom, $dateTo, $format = false)
    {
        if (empty($dateFrom) || empty($dateTo)) {
            return '';
        }

        return static::date($dateFrom, $format) . ' - ' . static::date($dateTo, $format);
    }

    /**
     * Formats two YYYY-MM-DD dates as a readable string, collapsing same months and same years.
     *
     * @param DateTime|string $dateFrom
     * @param DateTime|string $dateTo
     * @return string
     */
    public static function dateRangeReadable($dateFrom, $dateTo)
    {
        $output = '';
        if (empty($dateFrom) || empty($dateTo)) {
            return $output;
        }

        $startDate = static::createDateTime($dateFrom);
        $endDate = static::createDateTime($dateTo);

        $startTime = $startDate->getTimestamp();
        $endTime = $endDate->getTimestamp();

        if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
            $output = static::dateReadable($startTime, static::MEDIUM);
        } elseif ($startDate->format('Y') == $endDate->format('Y')) {
            $output = static::dateReadable($startTime, static::MEDIUM_NO_YEAR) . ' - ';
            $output .= static::dateReadable($endTime, static::MEDIUM);
        } else {
            $output = static::dateReadable($startTime, static::MEDIUM) . ' - ';
            $output .= static::dateReadable($endTime, static::MEDIUM);
        }

        return mb_convert_case($output, MB_CASE_TITLE);
    }

    /**
     * Formats a Unix timestamp as the language-specific format. Optionally provide a format string to use instead.
     *
     * @param DateTime|string|int $timestamp
     * @param string $format
     * @return string
     */
    public static function dateFromTimestamp($timestamp, $format = false)
    {
        if (empty($timestamp)) {
            return '';
        }

        $date = static::createDateTime($timestamp, 'U');
        return $date ? $date->format($format ? $format : static::$settings['dateFormatPHP']) : $timestamp;
    }

    /**
     * Formats a Date or DateTime string relative to the current time. Eg: 1 hr ago, 3 mins from now.
     *
     * @param DateTime|string $dateString
     * @return string
     */
    public static function relativeTime($dateString, $tooltip = true, $relativeString = true)
    {
        if (empty($dateString)) {
            return '';
        }
        if (is_string($dateString) && strlen($dateString) == 10) {
            $dateString .= ' 00:00:00';
        }
        $date = static::createDateTime($dateString, 'Y-m-d H:i:s');

        $timeDifference = time() - $date->format('U');
        $seconds = intval(abs($timeDifference));

        switch ($seconds) {
            case ($seconds <= 60):
                $time = __('Less than 1 min');
                break;
            case ($seconds > 60 && $seconds < 3600):
                $minutes = floor($seconds / 60);
                $time = __n('{count} min', '{count} mins', $minutes);
                break;
            case ($seconds >= 3600 && $seconds < 172800):
                $hours = floor($seconds / 3600);
                $time = __n('{count} hr', '{count} hrs', $hours);
                break;
            case ($seconds >= 172800 && $seconds < 1209600):
                $days = floor($seconds / 86400);
                $time = __n('{count} day', '{count} days', $days);
                break;
            case ($seconds >= 1209600 && $seconds < 4838400):
                $days = floor($seconds / 604800);
                $time = __n('{count} week', '{count} weeks', $days);
                break;
            default:
                $timeDifference = 0;
                $time = static::dateReadable($dateString);
        }

        if ($relativeString && $timeDifference > 0) {
            $time = __('{time} ago', ['time' => $time]);
        } elseif ($relativeString && $timeDifference < 0) {
            $time = __('in {time}', ['time' => $time]);
        }

        return $tooltip
            ? self::tooltip($time, static::dateTime($dateString))
            : $time;
    }

    /**
     * Converts a YYYY-MM-DD date to a Unix timestamp.
     *
     * @param DateTime|string $dateString
     * @param string $timezone
     * @return int
     */
    public static function timestamp($dateString, $timezone = null)
    {
        if (empty($dateString)) {
            return '';
        }

        if (is_string($dateString) && strlen($dateString) == 10) {
            $dateString .= ' 00:00:00';
        }
        $date = static::createDateTime($dateString, 'Y-m-d H:i:s', $timezone);
        return $date ? $date->getTimestamp() : 0;
    }

    /**
     * Formats a time from a given MySQL time or timestamp value.
     *
     * @param DateTime|string $timeString
     * @param string|bool $format
     * @return string
     */
    public static function time($timeString, $format = false)
    {
        if (empty($timeString)) {
            return '';
        }

        $convertFormat = is_string($timeString) && strlen($timeString) == 8? 'H:i:s' : 'Y-m-d H:i:s';
        $date = static::createDateTime($timeString, $convertFormat);
        return $date ? $date->format($format ? $format : static::$settings['timeFormatPHP']) : $timeString;
    }

    /**
     * Formats a range of times from two given MySQL time or timestamp values.
     *
     * @param DateTime|string $timeFrom
     * @param DateTime|string $timeTo
     * @param string|bool $format
     * @return string
     */
    public static function timeRange($timeFrom, $timeTo, $format = false)
    {
        return !empty($timeFrom) && !empty($timeTo)
            ? static::time($timeFrom, $format) . ' - ' . static::time($timeTo, $format)
            : static::time($timeFrom, $format);
    }

    /**
     * Formats a number to an optional decimal points.
     *
     * @param int|string $value
     * @param int $decimals
     * @return string
     */
    public static function number($value, $decimals = 0)
    {
        return number_format($value, $decimals);
    }

    /**
     * Formats a currency with a symbol and two decimals, optionally displaying the currency name in brackets.
     *
     * @param string|int $value
     * @param bool $includeName
     * @return string
     */
    public static function currency($value, $includeName = false, $decimals = 2)
    {
        return static::$settings['currencySymbol'] . number_format($value, $decimals) . ( $includeName ? ' ('.static::$settings['currencyName'].')' : '');
    }

    /**
     * Formats a Y/N value as Yes or No in the current language.
     *
     * @param string $value
     * @param bool   $translate
     * @return string
     */
    public static function yesNo($value, $translate = true)
    {
        $value = ($value == 'Y' || $value == 'Yes') ? 'Yes' : 'No';

        return $translate ? __($value) : $value;
    }

    /**
     * Formats a F/M/Other/Unspecified value as Female/Male/Other/Unspecified in the current language.
     *
     * @param string $value
     * @param bool   $translate
     * @return string
     */
    public static function genderName($value, $translate = true)
    {
        if (empty($value)) return '';

        $genderNames = [
            'F'           => __('Female'),
            'M'           => __('Male'),
            'Other'       => __('Other'),
            'Unspecified' => __('Unspecified')
            ];

        return $translate ? __($genderNames[$value]) : $genderNames[$value];
    }

    /**
     * Formats a filesize in bytes to display in KB, MB, etc.
     *
     * @param int $bytes
     * @return string
     */
    public static function filesize($bytes)
    {
        $unit = ['bytes','KB','MB','GB','TB','PB'];
        return !empty($bytes)
            ? @round($bytes/pow(1024, ($i=floor(log($bytes, 1024)))), 2).' '.$unit[$i]
            : '0 KB';
    }

    /**
     * Formats a long string by truncating after $length characters
     * and displaying the full string on hover.
     *
     * @param string $value
     * @param int $length
     * @return string
     */
    public static function truncate($value, $length = 40)
    {
        return is_string($value) && strlen($value) > $length
            ? "<span title='".$value."'>".substr($value, 0, $length).'...</span>'
            : $value;
    }

    /**
     * Formats a string of additional details in a smaller font.
     *
     * @param string $value
     * @return string
     */
    public static function small($value)
    {
        return '<span class="text-xxs italic">'.$value.'</span>';
    }

    /**
     * Formats a string in a larger font
     *
     * @param string $value
     * @return string
     */
    public static function bold($value)
    {
        return '<b>'.$value.'</b>';
    }

    /**
     * Formats a string as a tag
     *
     * @param string $value
     * @return string
     */
    public static function tag($value, $class, $title = '')
    {
        return '<span class="tag '.$class.'" title="'.$title.'">'.$value.'</span>';
    }

    /**
     * Formats a string of additional details for a hover-over tooltip.
     *
     * @param string $value
     * @return string
     */
    public static function tooltip($value, $tooltip = '')
    {
        return '<span title="'.$tooltip.'">'.$value.'</span>';
    }

    /**
     * Formats a link from a url. Automatically adds target _blank to external links.
     * Automatically resolves relative URLs starting with ./ into absolute URLs.
     *
     * @param string $url
     * @param string $text
     * @param array $attr
     * @return string
     */
    public static function link($url, $text = '', $attr = [])
    {
        if (empty($url)) {
            return $text;
        }
        if ($text === '') {
            $text = $url;
        }
        if (!is_array($attr)) {
            $attr = ['title' => $attr];
        }

        if (stripos($url, '@') !== false) {
            $url = 'mailto:'.$url;
        }
        if (substr($url, 0, 2) == './') {
            $url = static::$settings['absoluteURL'].substr($url, 1);
        }

        if (stripos($url, static::$settings['absoluteURL']) === false && !$url instanceof Url) {
            return '<a href="'.$url.'" '.self::attributes($attr).' target="_blank" rel="noopener noreferrer">'.$text.'</a>';
        } else {
            return '<a href="'.$url.'" '.self::attributes($attr).'>'.$text.'</a>';
        }
    }

    /**
     * Replaces all URLs with active hyperlinks
     *
     * @param string $value
     * @return string
     */
    public static function hyperlinkAll(string $value)
    {
        $pattern = '/([^">]|^)(https?:\/\/[^"<>\s]+)/';
        return preg_replace($pattern, '$1<a target="_blank" rel="noopener noreferrer" href="$2">$2</a>', $value);
    }

    /**
     * Formats a key => value array of HTML attributes into a string of key="value".
     *
     * @param array $attributes
     * @return string
     */
    public static function attributes(array $attributes)
    {
        return implode(' ', array_map(
            function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key]? $key : '';
                }
                if (isset($attributes[$key]) && $attributes[$key] != '') {
                    return $key.'="'.htmlentities($attributes[$key], ENT_QUOTES, 'UTF-8').'"';
                }
                return '';
            },
            array_keys($attributes)
        ));
    }

    /**
     * Formats a YYYY-MM-DD date as a relative age with years and months.
     *
     * @param string $dateString
     * @param bool $short
     * @return string
     */
    public static function age($dateString, $short = false)
    {
        if (empty($dateString)) {
            return '';
        }
        
        $date = DateTime::createFromFormat('Y-m-d', $dateString);
        
        if (!$date) {
            return __('Unknown');
        }

        $date = $date->diff(new DateTime());

        return $short
            ? $date->y . __('y') .', '. $date->m . __('m')
            : $date->y .' '. __('years') .', '. $date->m .' '. __('months');
    }

    /**
     * Formats phone numbers, optionally including countrt code and types.
     * Adds spaces to 7-10 digit numbers based on the most common global formats.
     *
     * @param string|int $number
     * @param bool $countryCode
     * @param bool $type
     * @return string
     */
    public static function phone($number, $countryCode = false, $type = false)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        switch (strlen($number)) {
            case 7:
                $number = preg_replace('/([0-9]{3})([0-9]{4})/', '$1 $2', $number);
                break;
            case 8:
                $number = preg_replace('/([0-9]{4})([0-9]{4})/', '$1 $2', $number);
                break;
            case 9:
                $number = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1 - $2 $3 $4', $number);
                break;
            case 10:
                $number = preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', '($1) $2 $3', $number);
                break;
        }

        return ($type? $type.': ' : '') . ($countryCode? '+'.$countryCode.' ' : '') . $number;
    }

    /**
     * Formats an address including optional district and country.
     *
     * @param string $address
     * @param string $addressDistrict
     * @param string $addressCountry
     * @return string
     */
    public static function address($address, $addressDistrict, $addressCountry)
    {
        if (stripos($address, PHP_EOL) === false) {
            // If the address has no line breaks, collapse lines by comma separation,
            // breaking up long address lines over 30 characters.
            $collapseAddress = function ($list, $line = '') use (&$collapseAddress) {
                $line .= array_shift($list);

                if (empty($list)) {
                    return $line;
                }

                return strlen($line.', '.current($list)) > 30
                    ? $line.'<br/>'.$collapseAddress($list, '')
                    : $collapseAddress($list, $line.', ');
            };

            $addressLines = array_filter(array_map('trim', explode(',', $address)));
            $address = $collapseAddress($addressLines);
        } else {
            $address = nl2br($address);
        }

        return ($address? $address.'<br/>' : '') . ($addressDistrict? $addressDistrict.'<br/>' : '') . ($addressCountry? $addressCountry.'<br/>' : '');
    }

    public static function list(array $items, $tag = 'ul', $listClass = '', $itemClass = 'leading-normal')
    {
        $output = "<$tag class='$listClass'>";
        foreach ($items as $item) {
            $output .= "<li class='$itemClass'>".$item.'</li>';
        }
        $output .= "</$tag>";

        return $output;
    }

    public static function listDetails(array $items, $tag = 'ul', $listClass = '', $itemClass = 'leading-normal')
    {
        $output = "<$tag class='$listClass'>";
        foreach ($items as $label => $value) {
            if ($label == 'heading' || $label == 'subheading') {
                $hTag = $label == 'heading' ? 'h3' : 'h4';
                $output .= "<li class='{$itemClass}'><{$hTag}>".$value."</{$hTag}></li>";
            } else {
                $output .= "<li class='{$itemClass}'><strong>".$label.'</strong>: '.$value.'</li>';
            }
        }
        $output .= "</$tag>";

        return $output;
    }

    public static function table(array $items, $class = 'w-full', $rowClass = '', $cellClass = '')
    {
        if (empty($items)) return '';

        $headings =  array_unique(array_merge(...array_map(function ($item) {
            return array_keys($item);
        }, $items)));

        $output = "<table class='$class'>";

        $output .= "<thead>";
        foreach ($headings as $key => $label) {
            $output .= "<th>".$label.'</th>';
        }
        $output .= "</thead>";

        foreach ($items as $index => $item) {
            $output .= "<tr class='$rowClass'>";
            foreach ($headings as $key) {
                $output .= "<td class='$cellClass'>".($item[$key] ?? '').'</td>';
            }
            $output .= "</tr>";
        }
        $output .= "</table>";

        return $output;
    }

    /**
     * Formats a name based on the provided Role Category. Optionally reverses the name (surname first) or uses an informal format (no title).
     *
     * @param string $title
     * @param string $preferredName
     * @param string $surname
     * @param string $roleCategory
     * @param bool $reverse
     * @param bool $informal
     * @return string
     */
    public static function name($title, $preferredName, $surname, $roleCategory = 'Staff', $reverse = false, $informal = false)
    {
        $output = '';

        if (empty($preferredName) && empty($surname)) {
            return '';
        }

        if ($roleCategory == 'Staff' or $roleCategory == 'Other') {
            $setting = 'nameFormatStaff' . ($informal? 'Informal' : 'Formal') . ($reverse? 'Reversed' : '');
            $format = isset(static::$settings[$setting])? static::$settings[$setting] : '[title] [preferredName:1]. [surname]';

            $output = preg_replace_callback(
                '/\[+([^\]]*)\]+/u',
                function ($matches) use ($title, $preferredName, $surname) {
                    list($token, $length) = array_pad(explode(':', $matches[1], 2), 2, false);
                    if ($$token) {
                        return (!empty($length)? mb_substr($$token, 0, intval($length)) :
                            (($token == 'title') ? __($$token) : $$token));
                    } else {
                        return '';
                    }
                },
                $format
            );
        } elseif ($roleCategory == 'Parent') {
            $format = (!$informal? '%1$s ' : '') . ($reverse? '%3$s, %2$s' : '%2$s %3$s');
            $output = sprintf($format, __($title), $preferredName, $surname);
        } elseif ($roleCategory == 'Student') {
            $format = $reverse ? '%2$s, %1$s' : '%1$s %2$s';
            $output = sprintf($format, $preferredName, $surname);
        }

        return trim($output, ' ');
    }

    /**
     * Formats a linked name based on roleCategory
     * @param string $gibbonPersonID
     * @param string $title
     * @param string $preferredName
     * @param string $surname
     * @param string $roleCategory
     * @param bool $reverse
     * @param bool $informal
     * @return string
     */
    public static function nameLinked($gibbonPersonID, $title, $preferredName, $surname, $roleCategory = 'Other', $reverse = false, $informal = false, $params = [])
    {
        $name = self::name($title, $preferredName, $surname, $roleCategory, $reverse, $informal);
        if (empty($name)) return __('Unknown');

        if ($roleCategory == 'Parent' || $roleCategory == 'Other') {
            $url = Url::fromModuleRoute('User Admin', 'user_manage_edit')
                ->withAbsoluteUrl()
                ->withQueryParams(['gibbonPersonID' => $gibbonPersonID] + $params);
            $output = self::link($url, $name);
        } elseif ($roleCategory == 'Staff') {
            $url = Url::fromModuleRoute('Staff', 'staff_view_details')
                ->withAbsoluteUrl()
                ->withQueryParams(['gibbonPersonID' => $gibbonPersonID] + $params);
            $output = self::link($url, $name);
        } elseif ($roleCategory == 'Student') {
            $url = Url::fromModuleRoute('Students', 'student_view_details')
                ->withAbsoluteUrl()
                ->withQueryParams(['gibbonPersonID' => $gibbonPersonID] + $params);
            $output = self::link($url, $name);
        } else {
            $output = $name;
        }
        return $output;
    }

    /**
     * Formats a list of names from an array containing standard title, preferredName & surname fields.
     *
     * @param array $list
     * @param string $roleCategory
     * @param bool $reverse
     * @param bool $informal
     * @return string
     */
    public static function nameList($list, $roleCategory = 'Staff', $reverse = false, $informal = false, $separator = '<br/>')
    {
        $listFormatted = array_map(function ($person) use ($roleCategory, $reverse, $informal) {
            return static::name($person['title'], $person['preferredName'], $person['surname'], $roleCategory, $reverse, $informal);
        }, $list);

        return implode($separator, $listFormatted);
    }

    /**
     * Formats a list of names from an array into a key-value array of id => name.
     *
     * @param array $list
     * @param string $roleCategory
     * @param bool $reverse
     * @param bool $informal
     * @param string $id
     * @return array
     */
    public static function nameListArray($list, $roleCategory = 'Staff', $reverse = false, $informal = false, $id = 'gibbonPersonID')
    {
        $listFormatted = array_reduce($list, function ($group, $person) use ($roleCategory, $reverse, $informal, $id) {
            $group[$person[$id]] = static::name($person['title'] ?? '', $person['preferredName'], $person['surname'], $roleCategory, $reverse, $informal);

            return $group;
        }, []);

        return $listFormatted;
    }

    /**
     * Renders an HTML <img> for a theme-based icon, based on the icon name.
     *
     * @param string $icon
     * @param string $title
     * @return string
     */
    public static function icon(string $icon, string $title)
    {
        $icon .= stripos($icon, '.') === false ? '.png' : '';
        return "<img title='{$title}' src='./themes/".static::$settings['gibbonThemeName']."/img/{$icon}'/>";
    }

    /**
     * Returns an HTML <img> based on the supplied photo path, using a placeholder image if none exists. Size may be either 75 or 240 at this time. Works using local images or linked images using HTTP(S)
     *
     * @param string $path
     * @param int|string $size
     * @param string $class
     * @return string
     */
    public static function photo($path, $size = 75, $class = 'inline-block shadow bg-white border border-gray-600')
    {
        switch ($size) {
            case 240:
            case 'lg':
                $class .= 'w-48 sm:w-64 max-w-full p-1 mx-auto';
                        $imageSize = 240;
                break;
            case 75:
            case 'md':
                $class .= 'w-20 lg:w-24 p-1';
                        $imageSize = 75;
                break;

            case 'sm':
                $class .= 'w-12 sm:w-20 p-px sm:p-1';
                        $imageSize = 75;
                break;

            default:
                $imageSize = $size;
        }

        $path = (string) $path;
        if (preg_match('/^http[s]*/', $path)) {
            return sprintf('<img class="%1$s" src="%2$s">', $class, $path);
        } else {
            if (empty($path) or file_exists(static::$settings['absolutePath'].'/'.$path) == false) {
                $path = '/themes/'.static::$settings['gibbonThemeName'].'/img/anonymous_240_square.jpg';
            }

            return sprintf('<img class="%1$s" src="%2$s">', $class, static::$settings['absoluteURL'].'/'.$path);
        }
    }

    /**
     * Returns an HTML <img> based on the supplied photo path, using a placeholder image if none exists. Size may be either 75 or 240 at this time.
     *
     * @param string $path
     * @param int|string $size
     * @param string $class
     * @return string
     */
    public static function userPhoto($path, $size = 75, $class = '')
    {
        $class .= ' inline-block shadow bg-white border border-gray-600 ';

        switch ($size) {
            case 240:
            case 'lg':
                $class .= 'w-48 sm:w-64 max-w-full p-1 mx-auto';
                $imageSize = 240;
                break;
            case 75:
            case 'md':
                $class .= 'w-20 lg:w-24 p-1';
                $imageSize = 75;
                break;

            case 'sm':
                $class .= 'w-12 sm:w-20 p-px sm:p-1';
                $imageSize = 75;
                break;

            case 'xs':
                $class .= 'w-8 sm:w-12 p-px sm:p-1';
                $imageSize = 75;
                break;

            default:
                $imageSize = $size;
        }

        if (empty($path) or file_exists(static::$settings['absolutePath'].'/'.$path) == false) {
            $path = '/themes/'.static::$settings['gibbonThemeName'].'/img/anonymous_'.$imageSize.'.jpg';
        }

        return sprintf('<img class="%1$s" src="%2$s">', $class, static::$settings['absoluteURL'].'/'.$path);
    }

    /**
     * Display an icon if this user's birthday is within the next week.
     *
     * @param string $dob YYYY-MM-DD
     * @param string $preferredName
     * @return string
     */
    public static function userBirthdayIcon($dob, $preferredName)
    {
        if (empty($dob)) {
            return '';
        }

        // HEY SHORTY IT'S YOUR BIRTHDAY!
        $daysUntilNextBirthday = static::daysUntilNextBirthday($dob);

        if ($daysUntilNextBirthday >= 8) {
            return '';
        }

        if ($daysUntilNextBirthday == 0) {
            $title = __("{name}'s birthday today!", ['name' => $preferredName]);
            $icon = 'gift_pink.png';
        } else {
            $title = __n(
                "{count} day until {name}'s birthday!",
                "{count} days until {name}'s birthday!",
                $daysUntilNextBirthday,
                ['name' => $preferredName]
            );
            $icon = 'gift.png';
        }

        return sprintf('<img class="absolute bottom-0 -ml-4" title="%1$s" src="%2$s">', $title, static::$settings['absoluteURL'].'/themes/'.static::$settings['gibbonThemeName'].'/img/'.$icon);
    }

    /**
     * Calculate the number of days before next birthday.
     *
     * @version v25
     * @since   v25
     *
     * @param string $birthday  Accepts birthday in mysql date (YYYY-MM-DD).
     *
     * @return int  Number of days before the next birthday. If today is a birthday, returns 0.
     */
    protected static function daysUntilNextBirthday(string $birthday): int
    {
        if (empty($birthday)) {
            return '';
        }

        // DateTime of 00:00:00 today
        $today = new \DateTime('today');

        // DateTime of 00:00:00 on this year birthday's date.
        $nextBirthday = \DateTime::createFromFormat('m-d H:i:s', substr($birthday, 5) . ' 00:00:00');

        // If birthday this year has past, increment for a 1 year period.
        if ($nextBirthday < $today) {
            $nextBirthday->add(new \DateInterval('P1Y'));
        }

        // Return the absolute difference between 2 DateTime formatted as number of days.
        return (int) $nextBirthday->diff($today, true)->format('%a');
    }

    public static function userStatusInfo($person = [])
    {
        if (!empty($person['roleCategory']) && $person['roleCategory'] == 'Student') {
            $departureReason = !empty($person['departureReason']) ? ', '.$person['departureReason'] : '';

            if (!empty($person['status']) && $person['status'] != 'Full') {
                return __($person['status']) . $departureReason;
            }
            if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) {
                return __('Before Start Date');
            }
            if (!(empty($person['dateEnd']) || $person['dateEnd'] >= date('Y-m-d'))) {
                return __('After End Date');
            }
            if (!empty($person['dateEnd']) && $person['dateEnd'] <= date('Y-m-d', strtotime('today + 60 days'))) {
                return __('Leaving') . $departureReason;
            }
            if (empty($person['yearGroup'])) {
                return __('Not Enrolled');
            }
        } else {
            if (!empty($person['status']) && $person['status'] != 'Full') {
                return __($person['status']);
            }
            if (!empty($person['staffType'])) {
                return __($person['staffType']);
            }
        }
        return '';
    }

    /**
     * Returns the course and class name concatenated with a . (dot). The separator could become a setting at some point?
     *
     * @param string $courseName
     * @param string $className
     * @return string
     */
    public static function courseClassName($courseName, $className)
    {
        return $courseName .'.'. $className;
    }

    public static function alert($message, $level = 'error')
    {
        return '<div class="'.$level.'">'.$message.'</div>';
    }

    private static function createDateTime($dateOriginal, $expectedFormat = null, $timezone = null)
    {
        if ($dateOriginal instanceof DateTime || $dateOriginal instanceof DateTimeImmutable) {
            return $dateOriginal;
        }

        if (is_int($dateOriginal)) {
            $expectedFormat = 'U';
        }

        return !empty($expectedFormat)
            ? DateTime::createFromFormat($expectedFormat, $dateOriginal, $timezone)
            : new DateTime($dateOriginal, $timezone);
    }

    /**
     * Format a given datetime / timestamp into localized day of week name.
     *
     * @param IntlCalendar|DateTimeInterface|array|string|int|float $datetime
     * @param bool $short
     *
     * @return string|false
     */
    public static function dayOfWeekName($datetime, $short = false)
    {
        if (!static::$intlFormatterAvailable) {
            return static::createDateTime($datetime)->format($short ? 'D' : 'l');
        }

        return static::getIntlFormatter($short ? 'EEE' : 'EEEE')->format(static::createDateTime($datetime));
    }

    /**
     * Format a given datetime / timestamp into abbrivated localized month name.
     * (i.e. from Jan to Sep).
     *
     * @param IntlCalendar|DateTimeInterface|array|string|int|float $datetime
     * @param bool $short
     *
     * @return string|false
     */
    public static function monthName($datetime, $short = false)
    {
        if (!static::$intlFormatterAvailable) {
            return static::createDateTime($datetime)->format($short ? 'M' : 'F');
        }

        return static::getIntlFormatter($short ? 'MMM' : 'MMMM')->format(static::createDateTime($datetime));
    }

    /**
     * Format a given datetime / timestamp into a 2 digits representation
     * of the month (i.e. from 01 to 12).
     *
     * @param IntlCalendar|DateTimeInterface|array|string|int|float $datetime
     *
     * @return string|false
     */
    public static function monthDigits($datetime)
    {
        if (!static::$intlFormatterAvailable) {
            return static::createDateTime($datetime)->format('m');
        }

        return static::getIntlFormatter('MM')->format(static::createDateTime($datetime));
    }

    protected static function getIntlFormatter($pattern = null)
    {
        static $formatter;

        if (!isset($formatter)) {
            $formatter = new \IntlDateFormatter(
                static::$settings['code'],
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL
            );
        }

        $formatter->setPattern($pattern);

        return $formatter;
    }
}
