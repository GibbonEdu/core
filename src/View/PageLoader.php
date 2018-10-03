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

namespace Gibbon\Contracts\View;

use Psr\Container\ContainerInterface;

/**
 * Loads the necessary objects for a given page.
 *
 * @version v17
 * @since   v17
 */
class PageLoader
{
    /**
     * Determines the module & action from the given address and returns a page object.
     *
     * @param ContainerInterface $container
     * @param string $address
     * @return Page
     */
    public function load(ContainerInterface $container, $address)
    {

    }
}
