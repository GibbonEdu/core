<?php

namespace Gibbon\Auth\Access;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\ModuleGateway;

class AccessManager
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
     * @var ModuleGateway
     */
    protected $moduleGateway;

    /**
     * Constructor.
     *
     * @param Session       $session
     * @param ModuleGateway $moduleGateway
     */
    public function __construct(
        Session $session,
        ModuleGateway $moduleGateway
    ) {
        $this->session = $session;
        $this->moduleGateway = $moduleGateway;
    }

    /**
     * Load the access descriptor of a certain resource of the current
     * session user.
     *
     * @param Resource $resource
     *
     * @return Access
     */
    public function getAccessOf(Resource $resource): Access
    {
        // Check user is logged in and currently has a role set.
        if (!$this->session->has('username') || empty($this->session->get('gibbonRoleIDCurrent'))) {
            return new Access($resource); // access of no action.
        }

        // Get all the available access of actions on active modules.
        $results = $this->moduleGateway->selectRoleModuleActionNames(
            $this->session->get('gibbonRoleIDCurrent'),
            $resource->getModule(),
            $resource->getRoutePath(),
            $resource->getActionName(),
            true
        )->fetchAll();
        $actions = array_map(function ($row) {
            return $row['actionName'];
        }, $results);
        return new Access($resource, $actions);
    }

    /**
     * Check if an action is allowed for the current session.
     *
     * A short hand of:
     * ```php
     * $accessManager->getAccessOf($resource)->allow();
     * ```
     *
     * @param Resource $resource
     *
     * @return boolean
     */
    public function allow(Resource $resource): bool
    {
        return $this->getAccessOf($resource)->allow();
    }
}
