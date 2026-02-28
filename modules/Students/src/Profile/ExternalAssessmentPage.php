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

use Gibbon\Database\Connection;
use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Services\Format;

/**
 * ExternalAssessmentPage
 * 
 * Displays external assessment results for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class ExternalAssessmentPage extends ProfilePage
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
     * Check if the current user has permission to view external assessment
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Formal Assessment', 'externalAssessment_details') 
            || Access::allows('Formal Assessment', 'externalAssessment_view');
    }

    /**
     * Generate HTML output for the external assessment page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch student data to get year group
        $student = $this->fetchStudentData();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        ob_start();
        
        // Include module functions and render external assessment
        $gibbonPersonID = $this->gibbonPersonID;
        $connection2 = $this->pdo;
        $gibbonYearGroupID = $student['gibbonYearGroupID'];
        $guid = $this->session->get('guid');
        
        include './modules/Formal Assessment/moduleFunctions.php';
        externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $gibbonYearGroupID);
        
        return ob_get_clean();
    }

    /**
     * Fetch student data from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentData(): array
    {
        $data = [
            'gibbonSchoolYearID' => $this->gibbonSchoolYearID,
            'gibbonPersonID' => $this->gibbonPersonID
        ];
        
        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID 
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND status='Full' 
                AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
                AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') 
                AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
        
        $result = $this->pdo->select($sql, $data);
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }
}
