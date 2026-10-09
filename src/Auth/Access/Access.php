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

namespace Gibbon\Auth\Access;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\ActionGateway;

/**
 * Service to get load access descriptor of the current session to
 * certain Gibbon resource.
 */
class Access
{
    /**
     * Session instance.
     *
     * @var Session
     */
    protected $session;

    /**
     * Module gateway for database accesses.
     *
     * @var actionGateway
     */
    protected $actionGateway;

    /**
     * Cache resources that have been loaded to avoid repeated database queries.
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * Constructor.
     *
     * @param Session       $session
     * @param ActionGateway $actionGateway
     */
    public function __construct(Session $session, ActionGateway $actionGateway) {
        $this->session = $session;
        $this->actionGateway = $actionGateway;
    }

    /**
     * Check if an action is allowed for the current session.
     *
     * A short hand of:
     * `Access::getAction($resource)->allows();`
     * 
     * @param string $module
     * @param string $routePath
     * @param string $actionName
     * @return bool
     */
    public function allows(string $module, string $routePath, string $actionName = ''): bool
    {
        return $this->getAction(Resource::fromRoute($module, $routePath))->allows($actionName);
    }

    /**
     * Check if an action is denied for the current session.
     *
     * @param string $module
     * @param string $routePath
     * @param string $actionName
     * @return bool
     */
    public function denies(string $module, string $routePath, string $actionName = ''): bool
    {
        return !$this->getAction(Resource::fromRoute($module, $routePath))->allows($actionName);
    }

    /**
     * Get the Action object for a given action by module route.
     *
     * @param string $module
     * @param string $routePath
     * @return Action
     */
    public function get(string $module, string $routePath): Action
    {
        return $this->getAction(Resource::fromRoute($module, $routePath));
    }

    /**
     * Get the highest action allowed for a given action by module route.
     *
     * @param string $module
     * @param string $routePath
     * @return string|null
     */
    public function getHighestAction(string $module, string $routePath): ?string
    {
        return $this->getAction(Resource::fromRoute($module, $routePath))->getHighestAction();
    }

    /**
     * Get the number of cached resources.
     *
     * @return int
     */
    public function getCacheCount(): int
    {
        return count($this->cache);
    }

    /**
     * Get the highest action allowed for a given action by module route.
     *
     * @param string $module
     * @param string $routePath
     * @return string|null
     */
    public function getHighestAction(string $module, string $routePath): ?string
    {
        return $this->getAction(Resource::fromRoute($module, $routePath))->getHighestAction();
    }

    /**
     * Load the access descriptor of a certain resource of the current
     * session user.
     *
     * @param Resource $resource
     * @return Action
     */
    public function getAction(Resource $resource): Action
    {
        // Check if the resource has already been loaded and cached.
        if (isset($this->cache[$resource->toString()])) {
            return $this->cache[$resource->toString()];
        }

        // Check user is logged in and currently has a role set.
        if (!$this->session->has('username') || empty($this->session->get('gibbonRoleIDCurrent'))) {
            return new Action($resource);
        }

        // Otherwise create a new Action object for the resource and cache it.
        return $this->createAction($resource);
    }

    /**
     * Create and cache access checks by resource to prevent duplicate database queries.
     *
     * @param Resource $resource
     * @return Action
     */
    protected function createAction(Resource $resource): Action
    {
        // Get all the available access of actions on active modules.
        $results = $this->actionGateway->selectModuleActionsByRole(
            $this->session->get('gibbonRoleIDCurrent'),
            $resource->getModule(),
            $resource->getRoutePath()
        )->fetchAll();

        $actions = array_map(function ($row) {
            return $row['actionName'];
        }, $results);

        // Create and cache the action object
        $action = new Action($resource, $actions);
        $this->cache[$resource->toString()] = $action;

        return $action;
    }
}
