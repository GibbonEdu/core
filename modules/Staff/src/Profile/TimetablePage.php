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
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * TimetablePage
 * 
 * Displays staff timetable with navigation controls and edit link for users
 * with appropriate permissions. Supports ttDate and gibbonTTID URL parameters
 * for timetable navigation.
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class TimetablePage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(Session $session)
    {
        parent::__construct($session);
    }

    public function getPageName(): string
    {
        return 'Timetable';
    }

    public function checkAccess(): bool
    {
        // Check if user has access to view timetables
        return Access::allows('Timetable', 'tt_view');
    }

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

        $output = '';

        // Display edit link for users with Course Enrolment by Person_edit permission
        $output .= $this->renderEditLink();

        // Render the timetable
        $output .= $this->renderTimetable();

        return $output;
    }

    /**
     * Render edit link for users with Course Enrolment by Person_edit permission
     * 
     * @return string HTML output
     */
    protected function renderEditLink(): string
    {
        // Guard: check if user has edit permission
        if (!Access::allows('Timetable Admin', 'courseEnrolment_manage_byPerson_edit', 'Course Enrolment by Person_edit')) {
            return '';
        }

        $form = Form::createBlank('buttons');
        $form->addHeaderAction('edit', __('Edit'))
            ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('gibbonSchoolYearID', $this->gibbonSchoolYearID)
            ->addParam('type', 'Staff')
            ->addParam('allUsers', 'on')
            ->displayLabel();

        return $form->getOutput();
    }

    /**
     * Render the timetable using TimetableContext and Timetable service
     * 
     * @return string HTML output
     */
    protected function renderTimetable(): string
    {
        // Get URL parameters for timetable navigation
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
