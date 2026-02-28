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
use Gibbon\UI\Timetable\Timetable;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\Services\Format;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * TimetablePage
 * 
 * Displays student timetable view.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class TimetablePage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        Session $session
    ) {
        parent::__construct($session);
    }

    /**
     * Check if the current user has permission to view timetable
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Timetable', 'tt_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Timetable');
    }


    /**
     * Generate HTML output for the timetable page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        $ttDate = !empty($_REQUEST['ttDate']) ? Format::dateConvert($_REQUEST['ttDate']) : null;
        $gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';
        
        // Create timetable context
        $context = $this->getContainer()->get(TimetableContext::class)
            ->set('gibbonSchoolYearID', $this->gibbonSchoolYearID)
            ->set('gibbonPersonID', $this->gibbonPersonID)
            ->set('gibbonTTID', $gibbonTTID);

        // Build and render timetable
        return $this->getContainer()->get(Timetable::class)
            ->setDate($ttDate)
            ->setContext($context)
            ->addCoreLayers($this->getContainer())
            ->getOutput();
    }
}
