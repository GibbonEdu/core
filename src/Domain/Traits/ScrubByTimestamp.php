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

namespace Gibbon\Domain\Traits;

/**
 * Implements the ScrubbableGateway interface.
 */
trait ScrubByTimestamp
{
    /**
     * Gets the table key that identified what can be scrubbed.
     *
     * @return string|array
     */
    public function getScrubbableKey()
    {
        if (empty(static::$scrubbableKey)) {
            throw new \BadMethodCallException(get_called_class().' must define $scrubbableKey');
        }

        return static::$scrubbableKey;
    }

    /**
     * Gets the table columns that can be scrubbed.
     *
     * @return array
     */
    public function getScrubbableColumns() : array
    {
        if (empty(static::$scrubbableColumns)) {
            throw new \BadMethodCallException(get_called_class().' must define $scrubbableColumns');
        }

        return static::$scrubbableColumns;
    }

    public function scrub(string $cutoffDate, array $context = []) : int
    {
        echo get_called_class().' was scrubbed';

        return true;
    }
}
