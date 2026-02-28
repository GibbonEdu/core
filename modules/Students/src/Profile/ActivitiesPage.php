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
    private ActivityGateway $activityGateway;

    public function __construct(
        Session $session,
        ActivityGateway $activityGateway
    ) {
        parent::__construct($session);
        $this->activityGateway = $activityGateway;
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
            return Format::alert(__('Invalid student ID.'));
        }

        $dateType = $_REQUEST['dateType'] ?? $this->session->get('gibbonSchoolYearIDCurrent') == $this->gibbonSchoolYearID ? 'Term' : 'Year';

        $result = $this->activityGateway->selectActivitiesByStudentForProfile(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );

        $table = DataTable::create('activities');
        $table->setTitle(__('Activities'));

        $table->addColumn('name', __('Activity'));
        
        $table->addColumn('type', __('Type'));

        if ($dateType == 'Date') {
            $table->addColumn('listingDates', __('Dates'))
                ->format(function ($activity) {
                    $output = '';
                    if ($activity['programStart'] != '') {
                        $output .= Format::date($activity['programStart']);
                    }
                    if ($activity['programEnd'] != '' && $activity['programEnd'] != $activity['programStart']) {
                        $output .= ' - '.Format::date($activity['programEnd']);
                    }
                    return $output;
                });
        } else {
            $table->addColumn('listingTerms', __('Terms'));
        }

        $table->addColumn('timestamp', __('Registered'))
            ->format(Format::using('date', 'timestamp'));

        return $table->render($result->toDataSet());
    }
}
