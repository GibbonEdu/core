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

namespace Gibbon\Domain\System;

use Gibbon\View\AssetBundle;

/**
 * Gibbon Module Model.
 *
 * @version v17
 * @since   v17
 */
abstract class Module
{
    protected $name;

    protected $stylesheets;
    protected $scripts;

    public function __construct($name)
    {
        $this->name = $name;
        $this->stylesheets = new AssetBundle();
        $this->scripts = new AssetBundle();
    }

    /**
     * Get the module name, used in the folder path and database record.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name = $name;
    }

    /**
     * Returns the collection of stylesheets used by this module.
     *
     * @return AssetBundle
     */
    public function stylesheets()
    {
        return $this->stylesheets;
    }

    /**
     * Returns the collection of scripts used by this module.
     *
     * @return AssetBundle
     */
    public function scripts()
    {
        return $this->scripts;
    }
}
