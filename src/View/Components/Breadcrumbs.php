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

namespace Gibbon\View\Components;

use Gibbon\Http\Url;
use Psr\Http\Message\UriInterface;

/**
 * Breadcrumb trail.
 *
 * @version v23
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
        $this->add(__('Home'), Url::fromRoute());
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
        $this->baseURL = rtrim(urldecode($baseURL), '/ ').'/';
        return $this;
    }

    /**
     * Add a named route to the trail.
     *
     * @version v23
     * @since   v17
     *
     * @param string              $title   Name to display on this route's link
     * @param string|UriInterface $route   String URL relative to the trail's BaseURL, or
     *                                     UriInterface.
     * @param array               $params  Additional URL params to append to the route.
     *                                     Only has effect if $route is a string.
     * @return self
     */
    public function add(string $title, $route = '', array $params = [])
    {
        if (!is_string($route) && !$route instanceof UriInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Route should be either a string or an implementation of UriInterface. Got %s',
                var_export($route, true)
            ));
        }

        // backward compatible
        if (is_string($route)) {
            $route = !empty($params)
                ? trim($route, '/ ').'&'.http_build_query($params)
                : trim($route, '/ ');
            $this->items[$title] = !empty($route)? $this->baseURL . $route : '';
        } else {
            // Do not support the query parameter at all because
            // UriInterface should have that covered.
            $this->items[$title] = $route;
        }

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
