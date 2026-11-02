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

namespace Gibbon\Auth\Gate;

use Gibbon\Auth\Access\Action;
use Gibbon\Contracts\Services\Session;

/**
 * Abstract base class for all Gate classes.
 * 
 * Gate classes handle checking access to a specific resource for the current 
 * user. They are more specific than the Access class, which only checks access 
 * to a given action.
 * 
 * Methods that return a bool should use the can* naming convention, 
 * e.g. canView(), canEdit(), etc.
 * 
 * Methods that return a resource or collection of resources should use the 
 * getAllowed* naming convention, e.g. getAllowedResource(), etc.
 *
 * @version  v30
 * @since    v30
 */
abstract class Gate
{
    /**
     * @var Gibbon\Contracts\Services\Session
     */
    protected $session;

    /**
     * @var Gibbon\Auth\Access\Action
     */
    protected Action $action;

    /**
     * @var string
     */
    protected $gibbonPersonID;

    /**
     * Constructor
     *
     * @param Gibbon\Contracts\Services\Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->gibbonPersonID = $this->session->get('gibbonPersonID');
    }

    /**
     * Set the action to check access for.
     *
     * @param Gibbon\Auth\Access\Action $action
     * @return self
     */
    public function withAccess(Action $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the action to check access for.
     *
     * @return Gibbon\Auth\Access\Action
     */
    public function getAccess(): Action
    {
        return $this->action;
    }

    /**
     * Check if any access is present for this gate.
     *
     * @return bool
     */
    public function hasAccess(): bool
    {
        return !empty($this->action) && $this->action->allows();
    }
}
