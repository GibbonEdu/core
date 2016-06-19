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
 *
 * @category   Gibbon
 * @package    Gibbon
 * @copyright  Copyright (c) 2006 - 2014 GNU (http://www.gnu.org/licenses/)
 * @license    GNU  http://www.gnu.org/licenses/
 * @version    ##VERSION##, ##DATE##
 */
 /**
  */
namespace Gibbon;


/**
 * Gibbon Exception
 *
 * @category   Gibbon
 * @package    Gibbon
 * @copyright  Copyright (c) 2006 - 2014 
 */
class Exception extends \Exception {
    /**
     * Error handler callback
     *
     * @param mixed $code
     * @param mixed $string
     * @param mixed $file
     * @param mixed $line
     * @param mixed $context
     */
    public static function errorHandlerCallback($code, $string, $file, $line, $context) {
        $e = new self($string, $code);
        $e->line = $line;
        $e->file = $file;
        throw $e;
    }
}
