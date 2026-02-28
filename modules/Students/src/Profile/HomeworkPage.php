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

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Planner\Tables\HomeworkTable;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * HomeworkPage
 * 
 * Displays homework assignments for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class HomeworkPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected SettingGateway $settingGateway;
    protected PlannerEntryGateway $plannerEntryGateway;

    public function __construct(
        Session $session,
        SettingGateway $settingGateway,
        PlannerEntryGateway $plannerEntryGateway
    ) {
        parent::__construct($session);
        $this->settingGateway = $settingGateway;
        $this->plannerEntryGateway = $plannerEntryGateway;
    }

    /**
     * Check if the current user has permission to view homework
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Planner', 'planner_edit') 
            || Access::allows('Planner', 'planner_view_full');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        $homeworkNamePlural = $this->settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

        return !empty($homeworkNamePlural) ? __($homeworkNamePlural) : __('Homework');
    }


    /**
     * Generate HTML output for the homework page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $output = '';
        $role = $this->session->get('gibbonRoleIDCurrentCategory');
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? null;

        // DEADLINES - Display upcoming homework deadlines
        $deadlines = $this->plannerEntryGateway->selectUpcomingHomeworkByStudent(
            $this->gibbonSchoolYearID, 
            $this->gibbonPersonID, 
            $role == 'Student' ? 'viewableStudents' : 'viewableParents'
        )->fetchAll();

        $page = $this->getContainer()->get('page');
        $output .= $page->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
            'gibbonPersonID' => $this->gibbonPersonID,
            'deadlines' => $deadlines,
            'heading' => 'h4'
        ]);

        // Add planner JavaScript module for interactive features
        $page->scripts->add('planner', '/modules/Planner/js/module.js');

        // HOMEWORK TABLE - Display all homework with submission status
        $homeworkTable = $this->getContainer()->get(HomeworkTable::class)
            ->create(
                $this->gibbonSchoolYearID, 
                $this->gibbonPersonID, 
                $role == 'Student' ? 'Student' : 'Parent',
                $gibbonCourseClassID
            );

        $output .= $homeworkTable->getOutput();

        return $output;
    }
}
