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
     * Constructor.
     *
     * @param Session       $session
     * @param ActionGateway $actionGateway
     */
    public function __construct(
        Session $session,
        ActionGateway $actionGateway
    ) {
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
        return $this->getAction(Resource::fromRoute($module, $routePath, $actionName))->allows();
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
        return !$this->getAction(Resource::fromRoute($module, $routePath, $actionName))->allows();
    }

    /**
     * Get the Action object for a given action by module route.
     *
     * @param string $module
     * @param string $routePath
     * @param string $actionName
     * @return Action
     */
    public function get(string $module, string $routePath, string $actionName = ''): Action
    {
        return $this->getAction(Resource::fromRoute($module, $routePath, $actionName));
    }

    /**
     * Load the access descriptor of a certain resource of the current
     * session user.
     *
     * @param Resource $resource
     *
     * @return Action
     */
    public function getAction(Resource $resource): Action
    {
        // Check user is logged in and currently has a role set.
        if (!$this->session->has('username') || empty($this->session->get('gibbonRoleIDCurrent'))) {
            return new Action($resource); // access of no action.
        }

        // Get all the available access of actions on active modules.
        $results = $this->actionGateway->selectModuleActionsByRole(
            $this->session->get('gibbonRoleIDCurrent'),
            $resource->getModule(),
            $resource->getRoutePath(),
            $resource->getActionName(),
            true
        )->fetchAll();

        $actions = array_map(function ($row) {
            return $row['actionName'];
        }, $results);

        return new Action($resource, $actions);
    }
}
