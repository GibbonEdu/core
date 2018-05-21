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

/**
 * Format values based on locale and system settings.
 *
 * @version v16
 * @since   v16
 */
class Format
{
    use FormatResolver;

    protected static $session;
    protected static $i18n;

    public static function setup($session)
    {
        static::$session = $session;
        static::$i18n = $session->get('i18n');
    }

    public static function date($dateString, $format = false)
    {
        $date = DateTime::createFromFormat('Y-m-d', $dateString);
        return $date ? $date->format($format ? $format : static::$i18n['dateFormatPHP']) : '';
    }

    public static function dateTime($dateString)
    {
        return static::date($dateString, 'F j, Y g:i a');
    }

    public static function dateReadable($dateString)
    {
        return static::date($dateString, 'F j, Y');
    }

    public static function dateRange($dateFrom, $dateTo, $format = false)
    {
        return static::date($dateFrom, $format) . ' - ' . static::date($dateTo, $format);
    }

    public static function dateFromTimestamp($timestamp, $format = false)
    {
        $date = DateTime::createFromFormat('U', $timestamp);
        return $date ? $date->format($format ? $format : static::$i18n['dateFormatPHP']) : '';
    }

    public static function dateConvert($dateString)
    {
        $date = DateTime::createFromFormat(static::$i18n['dateFormatPHP'], $dateString);
        return $date ? $date->format('Y-m-d') : '';
    }

    public static function timestamp($dateString)
    {
        $date = DateTime::createFromFormat('Y-m-d', $dateString);
        return $date ? $date->getTimestamp() : '';
    }

    public static function number($value, $decimals = 0)
    {
        return number_format($value, $decimals);
    }

    public static function currency($value, $decimals = 2)
    {
        return number_format($value, $decimals).' ('.static::$session->get('currency').')';
    }

    public static function yesNo($value)
    {
        return ($value == 'Y' || $value == 'Yes') ? __('Yes') : __('No');
    }

    public static function age($dateString, $short = false)
    {
        $date = DateTime::createFromFormat('Y-m-d', $dateString);
        if (!$date) return __('Unknown');

        $date = $date->diff(new DateTime());
        
        return $short 
            ? $date->y . __('y') .', '. $date->m . __('m')
            : $date->y .' '. __('years') .', '. $date->m .' '. __('months');
    }

    public static function phone($number, $countryCode = false, $type = false)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        switch (strlen($number)) {
            case 7:     $number = preg_replace('/([0-9]{3})([0-9]{4})/', '$1 $2', $number); break;
            case 8:     $number = preg_replace('/([0-9]{4})([0-9]{4})/', '$1 $2', $number); break;
            case 9:     $number = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1 - $2 $3 $4', $number); break;
            case 10:    $number = preg_replace('/([0-9]{3})([0-9]{2})([0-9]{2})([0-9]{3})/', '$1 - $2 $3 $4', $number); break;
        }

        return ($type? $type.': ' : '') . ($countryCode? '+'.$countryCode.' ' : '') . $number;
    }

    public static function name($title, $preferredName, $surname, $roleCategory = 'Staff', $reverse = false, $informal = false)
    {
        $output = '';

        if ($roleCategory == 'Staff' or $roleCategory == 'Other') {
            $setting = 'nameFormatStaff' . ($informal? 'Informal' : 'Formal') . ($reverse? 'Reversed' : '');
            $format = static::$session->get($setting, '[title] [preferredName:1]. [surname]');

            $output = preg_replace_callback('/\[+([^\]]*)\]+/u',
                function ($matches) use ($title, $preferredName, $surname) {
                    list($token, $length) = array_pad(explode(':', $matches[1], 2), 2, false);
                    return isset($$token)
                        ? (!empty($length)? mb_substr($$token, 0, intval($length)) : $$token)
                        : $matches[0];
                },
            $format);

        } elseif ($roleCategory == 'Parent') {
            $format = ($informal? '%1$s ' : '') . ($reverse? '%3$s, %2$s' : '%2$s %3$s');
            $output = sprintf($format, $title, $preferredName, $surname);
        } elseif ($roleCategory == 'Student') {
            $format = $reverse ? '%2$s, %1$s' : '%1$s %2$s';
            $output = sprintf($format, $preferredName, $surname);
        }

        return trim($output);
    }

    public static function userPhoto($path, $size = 75)
    {   
        $sizeStyle = $size == 240 ? "width: 240px; height: 320px" : "width: 75px; height: 100px";

        if (empty($path) or file_exists(static::$session->get('absolutePath').'/'.$path) == false) {
            $path = '/themes/'.static::$session->get('gibbonThemeName').'/img/anonymous_'.$size.'.jpg';
        }

        return sprintf('<img class="user" style="%1$s" src="%2$s"><br/>', $sizeStyle, static::$session->get('absoluteURL').'/'.$path);
    }

    public static function courseClassName($courseName, $className)
    {
        return $courseName .'.'. $className;
    }
}
