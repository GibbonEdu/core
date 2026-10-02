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

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Services\Format;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * BehaviourPage
 * 
 * Displays behaviour records for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class BehaviourPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private BehaviourGateway $behaviourGateway;

    public function __construct(
        Session $session,
        BehaviourGateway $behaviourGateway
    ) {
        parent::__construct($session);
        $this->behaviourGateway = $behaviourGateway;
    }

    /**
     * Check if the current user has permission to view behaviour records
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Behaviour', 'behaviour_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Behaviour');
    }


    /**
     * Generate HTML output for the behaviour page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        // Include module functions
        include './modules/Behaviour/moduleFunctions.php';
        
        // Get highest action to determine view permissions
        $highestAction = Access::get('Behaviour', 'behaviour_view');
        
        // Render behaviour records using module function
        if ($highestAction->allows('View Behaviour Records_my')) {
            return \getBehaviourRecord($this->getContainer(), $this->gibbonPersonID, $this->session->get('gibbonPersonID'));
        } elseif ($highestAction->allows('View Behaviour Records_all')) {
            return \getBehaviourRecord($this->getContainer(), $this->gibbonPersonID);
        } else {
            return '';
        }
    }
}
