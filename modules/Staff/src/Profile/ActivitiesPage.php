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

namespace Gibbon\Module\Staff\Profile;

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * ActivitiesPage
 * 
 * Displays staff activity enrollments including activity name, type, role, and status.
 * Provides action links for enrollment management (organizers only), viewing details,
 * and attendance (with appropriate permissions).
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class ActivitiesPage extends ProfilePage
{
    private ActivityGateway $activityGateway;

    public function __construct(
        Session $session,
        ActivityGateway $activityGateway
    ) {
        parent::__construct($session);
        $this->activityGateway = $activityGateway;
    }

    public function getPageName(): string
    {
        return 'Activities';
    }

    /**
     * Check if the current user has permission to view activities information
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        // Allow access if user has either My Activities or Activities view permission
        return Access::allows('Activities', 'activities_my', 'My Activities_view') 
            || Access::allows('Activities', 'activities_view');
    }

    /**
     * Generate HTML output for the activities subpage
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard: validate staff ID
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'), 'error');
        }

        // Guard: validate school year ID
        if (empty($this->gibbonSchoolYearID)) {
            return Format::alert(__('Invalid school year.'), 'error');
        }

        // Fetch activities data
        $activities = $this->fetchActivities();

        // Render activities table
        return $this->renderActivitiesTable($activities);
    }

    /**
     * Fetch activity enrollments for the staff member
     * 
     * @return \Gibbon\Domain\DataSet Activities data
     */
    protected function fetchActivities()
    {
        $criteria = $this->activityGateway->newQueryCriteria()
            ->sortBy('name')
            ->fromArray($_POST);

        return $this->activityGateway->queryActivitiesByParticipant(
            $criteria,
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );
    }

    /**
     * Render activities table with DataTable for consistent formatting
     * 
     * @param \Gibbon\Domain\DataSet $activities Activities data
     * @return string HTML output
     */
    protected function renderActivitiesTable($activities): string
    {
        $criteria = $this->activityGateway->newQueryCriteria()
            ->sortBy('name')
            ->fromArray($_POST);

        $table = DataTable::createPaginated('myActivities', $criteria);

        // Activity name and type column
        $table->addColumn('name', __('Activity'))
            ->format(function ($activity) {
                return $activity['name'].'<br/><span class="text-xs italic">'.$activity['type'].'</span>';
            });

        // Role column
        $table->addColumn('role', __('Role'))
            ->format(function ($activity) {
                return !empty($activity['role']) ? __($activity['role']) : __('Student');
            });

        // Status column
        $table->addColumn('status', __('Status'))
            ->format(function ($activity) {
                return !empty($activity['status']) ? __($activity['status']) : '<i>'.__('N/A').'</i>';
            });

        // Action column with conditional links
        $table->addActionColumn()
            ->addParam('gibbonActivityID')
            ->format(function ($activity, $actions) {
                // Get permission checks
                $canTakeAttendance = $this->canTakeAttendance();
                $canTakeAttendanceAsLeader = $this->canTakeAttendanceAsLeader();
                $canAccessEnrolment = $this->canAccessEnrolment();

                // Enrollment management link (only for organizers)
                if ($activity['role'] == 'Organiser' && $canAccessEnrolment) {
                    $actions->addAction('enrolment', __('Enrolment'))
                        ->addParam('gibbonSchoolYearTermID', '')
                        ->addParam('search', '')
                        ->setIcon('config')
                        ->setURL('/modules/Activities/activities_manage_enrolment.php');
                }

                // View details link (always available)
                $actions->addAction('view', __('View Details'))
                    ->isModal(1000, 550)
                    ->setURL('/modules/Activities/activities_my_full.php');

                // Attendance link (with permission check)
                if ($canTakeAttendance || 
                    ($canTakeAttendanceAsLeader && 
                     ($activity['role'] == 'Organiser' || $activity['role'] == 'Assistant' || $activity['role'] == 'Coach'))) {
                    $actions->addAction('attendance', __('Attendance'))
                        ->setIcon('attendance')
                        ->setURL('/modules/Activities/activities_attendance.php');
                }
            });

        return $table->render($activities);
    }

    /**
     * Check if the user can take attendance for any activity
     * 
     * @return bool True if user has full attendance permission
     */
    protected function canTakeAttendance(): bool
    {
        return Access::allows('Activities', 'activities_attendance', 'Enter Activity Attendance');
    }

    /**
     * Check if the user can take attendance as a leader
     * 
     * @return bool True if user has leader attendance permission
     */
    protected function canTakeAttendanceAsLeader(): bool
    {
        return Access::allows('Activities', 'activities_attendance', 'Enter Activity Attendance_leader');
    }

    /**
     * Check if the current user can access enrollment management
     * 
     * @return bool True if user has access, false otherwise
     */
    protected function canAccessEnrolment(): bool
    {
        return Access::allows('Activities', 'activities_manage_enrolment', 'Manage Activities_manage');
    }
}
