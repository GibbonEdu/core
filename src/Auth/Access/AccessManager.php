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
     * Check if an action is allowed for the current session.
     *
     * @param Resource $resource
     *
     * @return boolean
     */
    public function allow(Resource $resource): bool
    {
        // Check user is logged in and currently has a role set.
        if (!$this->session->has('username') || empty($this->session->get('gibbonRoleIDCurrent'))) {
            return false;
        }

        // Check module ready.
        if (empty($resource->getModule())) {
            return false;
        }

        // Check if the specified user role has access to the module action specified.
        return $this->moduleGateway->selectRoleModuleActionNames(
            $this->session->get('gibbonRoleIDCurrent'),
            $resource->getModule(),
            $resource->getRoutePath(),
            $resource->getActionName(),
        )->isNotEmpty();
    }
}
