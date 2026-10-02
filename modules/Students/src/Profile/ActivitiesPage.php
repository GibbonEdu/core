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
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * ActivitiesPage
 * 
 * Displays activity enrollments for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class ActivitiesPage extends ProfilePage
{
    private SettingGateway $settingGateway;
    private ActivityGateway $activityGateway;
    private StudentGateway $studentGateway;
    private SchoolYearTermGateway $schoolYearTermGateway;

    public function __construct(
        Session $session,
        SettingGateway $settingGateway,
        ActivityGateway $activityGateway,
        StudentGateway $studentGateway,
        SchoolYearTermGateway $schoolYearTermGateway
    ) {
        parent::__construct($session);
        $this->settingGateway = $settingGateway;
        $this->activityGateway = $activityGateway;
        $this->studentGateway = $studentGateway;
        $this->schoolYearTermGateway = $schoolYearTermGateway;
    }

    /**
     * Check if the current user has permission to view activities
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Activities', 'report_activityChoices_byStudent') 
            || Access::allows('Activities', 'activities_view_myChildren') 
            || Access::allows('Activities', 'activities_my');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Activities');
    }


    /**
     * Generate HTML output for the activities page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $dateType = $this->settingGateway->getSettingByScope('Activities', 'dateType');

        $schoolYears = $this->studentGateway->selectStudentEnrolmentHistory($this->gibbonPersonID)->fetchAll();
        $schoolYears = array_reverse($schoolYears);
        $output = Format::paragraph(__('This report shows the current and historical activities that a student has enrolled in.'));

        foreach ($schoolYears as $schoolYear) {

            $result = $this->activityGateway->selectActivityEnrolmentByStudent(
                $schoolYear['gibbonSchoolYearID'],
                $this->gibbonPersonID
            );

            $table = DataTable::create('activities');
            $table->setTitle($schoolYear['schoolYear']);

            $table->modifyRows(function ($values, $row) {
                if ($values['status'] == 'Pending') $row->addClass('warning');
                if ($values['status'] == 'Waiting List') $row->addClass('warning');
                if ($values['status'] == 'Not Accepted') $row->addClass('dull');
                if ($values['status'] == 'Left') $row->addClass('dull');
                return $row;
            });

            $table->addColumn('name', __('Activity'));
            
            $table->addColumn('type', __('Type'));

            $table->addColumn('date', $dateType != 'Date'? __('Term') : __('Dates'))
                ->description(__('Days'))
                ->context('secondary')
                ->width('18%')
                ->sortable($dateType != 'Date' ? ['gibbonSchoolYearTermIDList'] : ['programStart', 'programEnd'])
                ->format(function ($activity) use ($dateType) {
                    $output = '';
                    if ($dateType != 'Date') {
                        $schoolTerms = $this->schoolYearTermGateway->selectTermsBySchoolYear((int) $this->gibbonSchoolYearID)->fetchKeyPair();
                        $termList = array_intersect_key($schoolTerms, array_flip(explode(',', $activity['gibbonSchoolYearTermIDList'] ?? '')));
                        if (!empty($termList)) {
                            $output .= implode('<br/>', $termList);
                        }
                    } else {
                        $output .= Format::dateRangeReadable($activity['programStart'], $activity['programEnd']);
                    }

                    $output .= '<br/><span class="text-xs italic">';
                    $output .= implode(', ', $this->activityGateway->selectWeekdayNamesByActivity($activity['gibbonActivityID'])->fetchAll(\PDO::FETCH_COLUMN));
                    $output .= '</span>';

                    return $output;
                });

            $table->addColumn('timestamp', __('Registered'))
                ->format(Format::using('date', 'timestamp'));

            $canViewActivities = Access::allows('Activities', 'activities_view_full');
            $table->addActionColumn()
                    ->format(function ($activity, $actions) use ($canViewActivities) {
                    $role = $this->session->get('gibbonRoleIDCurrentCategory');
                    
                    if ($canViewActivities) {
                        $actions->addAction('view', __('View Details'))
                            ->setURL('/modules/Activities/activities_view_full.php')
                            ->addParam('gibbonActivityID', $activity['gibbonActivityID'])
                            ->modalWindow(1000, 500);
                    } else if ($role == 'Student' && $activity['gibbonSchoolYearID'] == $this->session->get('gibbonSchoolYearID')) { 
                        $actions->addAction('view', __('View Details'))
                            ->setURL('/modules/Activities/explore_activity.php')
                            ->addParam('sidebar', 'false')
                            ->addParam('gibbonActivityID', $activity['gibbonActivityID'])
                            ->modalWindow(1200, 600);
                    }
                    });

            $output .= $table->render($result->toDataSet());

        }

        return $output;
    }
}
