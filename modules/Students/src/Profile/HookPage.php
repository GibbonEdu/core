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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Services\Format;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\HookGateway;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * HookPage
 * 
 * Handles third-party hook integrations for the student profile.
 * Hooks allow external modules to extend the student profile with custom pages.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class HookPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private Connection $pdo;
    private HookGateway $hookGateway;

    protected string $hookName;
    protected string $gibbonHookID;

    public function __construct(
        Session $session,
        Connection $pdo,
        HookGateway $hookGateway
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->hookGateway = $hookGateway;
    }

    /**
     * Set the hook name and ID for this page
     * 
     * @param string $hookName The display name of the hook
     * @param string $gibbonHookID The unique identifier for the hook
     * @return self
     */
    public function setHook(string $hookName, string $gibbonHookID): self
    {
        $this->hookName = $hookName;
        $this->gibbonHookID = $gibbonHookID;

        return $this;
    }

    /**
     * Check if the current user has permission to view this hook
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        // Fetch hook data
        $hook = $this->hookGateway->getByID($this->gibbonHookID);
        
        if (empty($hook)) {
            return false;
        }

        $options = unserialize($hook['options']);

        if (empty($options)) {
            return false;
        }

        // Check for permission to hook
        $hookPermission = $this->hookGateway->getHookPermission(
            $hook['gibbonHookID'],
            $this->session->get('gibbonRoleIDCurrent'),
            $options['sourceModuleName'] ?? '',
            $options['sourceModuleAction'] ?? ''
        );

        return !empty($hookPermission);
    }

    /**
     * Get the page name for display
     *
     * @return string The hook name
     */
    public function getPageName(): string
    {
        return $this->hookName;
    }

    /**
     * Generate HTML output for the hook page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Fetch hook data
        $hook = $this->hookGateway->getByID($this->gibbonHookID);
        
        // Guard clause: check if hook exists
        if (empty($hook)) {
            return '';
        }

        $options = unserialize($hook['options']);

        // Guard clause: check for valid options
        if (empty($options)) {
            return Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
        }

        // Check for permission to hook
        $hookPermission = $this->hookGateway->getHookPermission(
            $hook['gibbonHookID'],
            $this->session->get('gibbonRoleIDCurrent'),
            $options['sourceModuleName'] ?? '',
            $options['sourceModuleAction'] ?? ''
        );

        // Guard clause: check permissions
        if (empty($hookPermission)) {
            return Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
        }

        // Build include path
        $include = $this->session->get('absolutePath') . '/modules/' . $options['sourceModuleName'] . '/' . $options['sourceModuleInclude'];
        
        // Guard clause: check if include file exists
        if (!file_exists($include)) {
            return Format::alert(__('The selected page cannot be displayed due to a hook error.'), 'error');
        }

        // Globals for Hooks
        $container =  $this->getContainer();
        $page =  $container->get('page');
        $pdo =  $this->pdo;
        $session =  $this->session;
        $connection2 =  $this->pdo->getConnection();
        $guid =  $this->session->get('guid');
        $gibbonPersonID = $this->gibbonPersonID;
        $gibbonSchoolYearID = $this->gibbonSchoolYearID;
        $hook = $this->hookName;

        // Include the hook file and capture output
        ob_start();
        include $include;
        return ob_get_clean();
    }
}
