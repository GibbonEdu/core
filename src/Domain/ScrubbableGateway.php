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

namespace Gibbon\Domain;

/**
 * Implementing this interface adds functionality for scrubbing personal data from this gateway.
 * Use with the Scrubbable trait, along with ScrubByPerson, ScrubByTimestamp or ScrubByFamily.
 *
 * @version v21
 * @since   v21
 */
interface ScrubbableGateway
{
    public function getScrubbableKey();

    public function getScrubbableColumns() : array;

    public function getScrubbableRecords(string $cutoffDate, array $context = []) : array;

    public function scrub(string $cutoffDate, array $context = []) : array;
}
