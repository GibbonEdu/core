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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Attendance\StudentHistoryData;
use Gibbon\Module\Attendance\StudentHistoryView;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * AttendancePage
 * 
 * Displays student attendance history and statistics.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class AttendancePage extends ProfilePage
{
    private StudentGateway $studentGateway;
    private StudentHistoryData $attendanceData;
    private StudentHistoryView $attendanceView;

    public function __construct(
        Session $session,
        StudentGateway $studentGateway,
        StudentHistoryData $attendanceData,
        StudentHistoryView $attendanceView
    ) {
        parent::__construct($session);
        $this->studentGateway = $studentGateway;
        $this->attendanceData = $attendanceData;
        $this->attendanceView = $attendanceView;
    }

    /**
     * Check if the current user has permission to view attendance
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Attendance', 'report_studentHistory');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Attendance');
    }


    /**
     * Generate HTML output for the attendance page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        // Fetch student data
        $student = $this->fetchStudentData();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        // Include module functions
        include './modules/Attendance/moduleFunctions.php';

        // ATTENDANCE DATA
        $attendanceData = $this->attendanceData->getAttendanceData(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID,
            $student['dateStart'],
            $student['dateEnd']
        );

        // DATA TABLE
        $this->attendanceView->addData('canTakeAttendanceByPerson', Access::allows('Attendance', 'attendance_take_byPerson'));
        $table = DataTable::create('studentHistory', $this->attendanceView);
        
        return $table->render($attendanceData);
    }

    /**
     * Fetch student data from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentData(): array
    {
        $result = $this->studentGateway->selectActiveStudentByPerson(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }
}
