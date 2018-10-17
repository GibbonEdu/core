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

namespace Gibbon\View\Components;

/**
 * Breadcrumb trail.
 *
 * @version v17
 * @since   v17
 */
class Breadcrumbs
{
    protected $baseURL = '';
    protected $items = [];

    /**
     * Start the trail at Home.
     */
    public function __construct()
    {
        $this->add(__('Home'));
        $this->setBaseURL('index.php?q=');
    }

    /**
     * Set the URL that will be prepended to all following routes.
     *
     * @param string $baseURL
     * @return self
     */
    public function setBaseURL(string $baseURL)
    {
        $this->baseURL = trim($baseURL, '/ ').'/';
        
        return $this;
    }

    /**
     * Add a named route to the trail.
     *
     * @param string $title   Name to display on this route's link
     * @param string $route   URL relative to the trail's BaseURL
     * @param array  $params  Additional URL params to append to the route
     * @return self
     */
    public function add(string $title, string $route = '', array $params = [])
    {
        $route = !empty($params)
            ? trim($route, '/ ').'&'.http_build_query($params)
            : trim($route, '/ ');

        $this->items[$title] = !empty($route)? $this->baseURL . $route : '';

        return $this;
    }

    /**
     * Get all items in the trail. Don't return 'Home' if there's no other items.
     *
     * @return array
     */
    public function getItems() : array
    {
        return count($this->items) > 1 ? $this->items : [];
    }
}
