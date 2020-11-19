<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Session;
use DateTimeImmutable;

/**
 * Format values based on locale and system settings.
 *
 * @version v16
 * @since   v16
 */
class Format
{
    use FormatResolver;

    protected static $settings = [
        'dateFormatPHP'     => 'd/m/Y',
        'dateTimeFormatPHP' => 'd/m/Y H:i',
        'timeFormatPHP'     => 'H:i',
    ];

    /**
     * Sets the internal formatting options from an array.
     *
     * @param array $settings
     */
    public static function setup(array $settings)
    {
        static::$settings = array_replace(static::$settings, $settings);
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
        $settings['currency'] = $session->get('currency');
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
        $date = static::createDateTime($dateString);
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
        $date = static::createDateTime($dateString, 'Y-m-d H:i:s');
        return $date ? $date->format($format ? $format : static::$settings['dateTimeFormatPHP']) : $dateString;
    }
    
    /**
     * Formats a YYYY-MM-DD date as a readable string with month names.
     *
     * @param DateTime|string $dateString
     * @return string
     */
    public static function dateReadable($dateString, $format = '%b %e, %Y')
    {
        if (empty($dateString)) {
            return '';
        }
        $date = static::createDateTime($dateString);
        return mb_convert_case(strftime($format, $date->format('U')), MB_CASE_TITLE);
    }

    /**
     * Formats a YYYY-MM-DD date as a readable string with month names and times.
     *
     * @param DateTime|string $dateString
     * @return string
     */
    public static function dateTimeReadable($dateString, $format = '%b %e, %Y %H:%M')
    {
        if (empty($dateString)) {
            return '';
        }
        $date = static::createDateTime($dateString);
        return mb_convert_case(strftime($format, $date->format('U')), MB_CASE_TITLE);
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
            $output = strftime('%b %e, %Y', $startTime);
        } elseif ($startDate->format('Y-m') == $endDate->format('Y-m')) {
            $output = strftime('%b %e', $startTime).' - '.strftime('%e, %Y', $endTime);
        } elseif ($startDate->format('Y') == $endDate->format('Y')) {
            $output = strftime('%b %e', $startTime).' - '.strftime('%b %e, %Y', $endTime);
        } else {
            $output = strftime('%b %e, %Y', $startTime).' - '.strftime('%b %e, %Y', $endTime);
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
        $date = static::createDateTime($timestamp, 'U');
        return $date ? $date->format($format ? $format : static::$settings['dateFormatPHP']) : $timestamp;
    }

    /**
     * Formats a Date or DateTime string relative to the current time. Eg: 1 hr ago, 3 mins from now.
     *
     * @param DateTime|string $dateString
     * @return string
     */
    public static function relativeTime($dateString, $tooltip = true)
    {
        if (empty($dateString)) {
            return '';
        }
        if (strlen($dateString) == 10) {
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
            case ($seconds >= 3600 && $seconds < 86400):
                $hours = floor($seconds / 3600);
                $time = __n('{count} hr', '{count} hrs', $hours);
                break;
            case ($seconds >= 86400 && $seconds < 1209600):
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

        if ($timeDifference > 0) {
            $time = __('{time} ago', ['time' => $time]);
        } elseif ($timeDifference < 0) {
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
        if (strlen($dateString) == 10) {
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
        $convertFormat = strlen($timeString) == 8? 'H:i:s' : 'Y-m-d H:i:s';
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
        return static::time($timeFrom, $format) . ' - ' . static::time($timeTo, $format);
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
    public static function currency($value, $includeName = false)
    {
        return static::$settings['currencySymbol'] . number_format($value, 2) . ( $includeName ? ' ('.static::$settings['currencyName'].')' : '');
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
        return strlen($value) > $length
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
     * @param string $title
     * @return string
     */
    public static function link($url, $text = '', $attr = [])
    {
        if (empty($url)) {
            return $text;
        }
        if (!$text) {
            $text = $url;
        }
        if (!is_array($attr)) {
            $attr = ['title' => $attr];
        }

        if (substr($url, 0, 2) == './') {
            $url = static::$settings['absoluteURL'].substr($url, 1);
        }

        if (stripos($url, static::$settings['absoluteURL']) === false) {
            return '<a href="'.$url.'" '.self::attributes($attr).' target="_blank">'.$text.'</a>';
        } else {
            return '<a href="'.$url.'" '.self::attributes($attr).'>'.$text.'</a>';
        }
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
                $number = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 - $2 $3 $4', $number);
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
        if ($roleCategory == 'Staff') {
            $url = static::$settings['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$gibbonPersonID;
            if (!empty($params)) {
                $url .= '&'.http_build_query($params);
            }
            $output = self::link($url, $name);
        } elseif ($roleCategory == 'Student') {
            $url = static::$settings['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID;
            if (!empty($params)) {
                $url .= '&'.http_build_query($params);
            }
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
     * Returns an HTML <img> based on the supplied photo path, using a placeholder image if none exists. Size may be either 75 or 240 at this time. Works using local images or linked images using HTTP(S)
     *
     * @param string $path
     * @param int|string $size
     * @param string $class
     * @return string
     */
    public static function photo($path, $size = 75, $class = '')
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

            default:
                $imageSize = $size;
        }

        if (preg_match('/^http[s]*/', $path)) {
            return sprintf('<img class="%1$s" src="%2$s">', $class, $path);
        } else {
            if (empty($path) or file_exists(static::$settings['absolutePath'].'/'.$path) == false) {
                $path = '/themes/'.static::$settings['gibbonThemeName'].'/img/anonymous_'.$imageSize.'.jpg';
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
        // HEY SHORTY IT'S YOUR BIRTHDAY!
        $daysUntilNextBirthday = daysUntilNextBirthday($dob);
        
        if (empty($dob) || $daysUntilNextBirthday >= 8) {
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

    public static function userStatusInfo($person = [])
    {
        if (!empty($person['status']) && $person['status'] != 'Full') {
            return __($person['status']);
        }
        if (!empty($person['roleCategory']) && $person['roleCategory'] == 'Student') {
            if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) {
                return __('Before Start Date');
            }
            if (!(empty($person['dateEnd']) || $person['dateEnd'] >= date('Y-m-d'))) {
                return __('After End Date');
            }
            if (empty($person['yearGroup'])) {
                return __('Not Enrolled');
            }
        } else {
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

        return !empty($expectedFormat)
            ? DateTime::createFromFormat($expectedFormat, $dateOriginal, $timezone)
            : new DateTime($dateOriginal, $timezone);
    }
}
