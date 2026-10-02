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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Services\Format;

/**
 * InternalAssessmentPage
 * 
 * Displays internal assessment data for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class InternalAssessmentPage extends ProfilePage
{
    private Connection $pdo;

    public function __construct(
        Session $session,
        Connection $pdo
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
    }

    /**
     * Check if the current user has permission to view internal assessment
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Formal Assessment', 'internalAssessment_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Internal Assessment');
    }


    /**
     * Generate HTML output for the internal assessment page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $highestAction = Access::get('Formal Assessment', 'internalAssessment_view');
        
        // Include module functions
        include './modules/Formal Assessment/moduleFunctions.php';
        
        // Determine which version to call based on permission level
        if ($highestAction->allows('View Internal Assessments_all')) {
            return \getInternalAssessmentRecord($this->session->get('guid'), $this->pdo->getConnection(), $this->gibbonPersonID);
        } elseif ($highestAction->allows('View Internal Assessments_myChildrens')) {
            return \getInternalAssessmentRecord($this->session->get('guid'), $this->pdo->getConnection(), $this->gibbonPersonID, 'parent');
        } elseif ($highestAction->allows('View Internal Assessments_mine')) {
            return \getInternalAssessmentRecord($this->session->get('guid'), $this->pdo->getConnection(), $this->session->get('gibbonPersonID'), 'student');
        }
        
        return '';
    }
}
