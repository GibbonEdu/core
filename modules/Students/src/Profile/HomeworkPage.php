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
use Gibbon\Module\Planner\Tables\HomeworkTable;
use Gibbon\Services\Format;
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

    public function __construct(
        Session $session
    ) {
        parent::__construct($session);
    }

    /**
     * Check if the current user has permission to view homework
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Planner', 'planner_edit') 
            || Access::allows('Planner', 'planner_view_full');
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
            return Format::alert(__('Invalid student ID.'));
        }

        $viewBy = $_GET['viewBy'] ?? 'date';
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? null;

        $homeworkTable = $this->getContainer()->get(HomeworkTable::class)
            ->create($this->gibbonSchoolYearID, $this->gibbonPersonID, $viewBy, $gibbonCourseClassID);

        return $homeworkTable->getOutput();
    }
}
