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

/**
 * A descriptor of access of a session user to certain resource.
 *
 * Includes a Resource instance and a list of allowed actions.
 */
class Action
{
    /**
     * Resource this access is concerning.
     *
     * @var Resource
     */
    protected $resource;

    /**
     * Actions allowed for the resource.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Constructor
     *
     * @param Resource $resource  The resource this access instance is concerning.
     * @param array    $actions   An array of actions allowed for the resource.
     */
    public function __construct(Resource $resource, array $actions = [])
    {
        $this->resource = $resource;
        $this->actions = $actions;
    }

    /**
     * Check if an the resource access allow certain action.
     *
     * If the action is not provided, then simply check if any action is allowed
     * for the resource access.
     *
     * @param string|null $action  The action string to check. Null if no specific action.
     *                             Default: null.
     *
     * @return bool
     */
    public function allows(string $action = ''): bool
    {
        if ($action === '') {
            return !empty($this->actions);
        }
        return in_array($action, $this->actions);
    }

    /**
     * Check if any of the given actions are allowed.
     *
     * @param string ...$actions
     *
     * @return bool
     */
    public function allowsAny(string ...$actions): bool
    {
        if (empty($actions)) {
            throw new \InvalidArgumentException('Must at least provide 1 action');
        }
        foreach ($actions as $action) {
            if (in_array($action, $this->actions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all of the given actions are allowed.
     *
     * @param string ...$actions
     *
     * @return bool
     */
    public function allowsAll(string ...$actions): bool
    {
        if (empty($actions)) {
            throw new \InvalidArgumentException('Must at least provide 1 action');
        }
        foreach ($actions as $action) {
            if (!in_array($action, $this->actions)) {
                return false;
            }
        }
        return true;
    }
}
