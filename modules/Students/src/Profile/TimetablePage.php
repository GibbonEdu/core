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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\Timetable\CourseClassPersonGateway;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * TimetablePage
 * 
 * Displays student timetable view with edit/export actions and class list fallback.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class TimetablePage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private RoleGateway $roleGateway;
    private CourseClassPersonGateway $courseClassPersonGateway;

    public function __construct(
        Session $session,
        RoleGateway $roleGateway,
        CourseClassPersonGateway $courseClassPersonGateway
    ) {
        parent::__construct($session);
        $this->roleGateway = $roleGateway;
        $this->courseClassPersonGateway = $courseClassPersonGateway;
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
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $output = '';

        // Check if user has access to timetable
        if (Access::allows('Timetable', 'tt_view')) {
            // Add header actions for edit and export
            $output .= $this->renderHeaderActions();

            $ttDate = !empty($_REQUEST['ttDate']) ? Format::dateConvert($_REQUEST['ttDate']) : null;
            $gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';
            
            // Create timetable context
            $context = $this->getContainer()->get(TimetableContext::class)
                ->set('gibbonSchoolYearID', $this->gibbonSchoolYearID)
                ->set('gibbonPersonID', $this->gibbonPersonID)
                ->set('gibbonTTID', $gibbonTTID);

            // Build and render timetable
            $output .= $this->getContainer()->get(Timetable::class)
                ->setDate($ttDate)
                ->setContext($context)
                ->addCoreLayers($this->getContainer())
                ->getOutput();
        } else {
            // Display class list fallback when timetable access not available
            $output .= $this->renderClassListFallback();
        }

        return $output;
    }

    /**
     * Render header actions for edit and export
     * 
     * @return string HTML for header actions
     */
    protected function renderHeaderActions(): string
    {
        $table = DataTable::createDetails('timetable');

        // Add edit action if user has permission
        if (Access::allows('Timetable Admin', 'courseEnrolment_manage_byPerson', 'Course Enrolment by Person_view')) {
            // Get student data to determine role
            $data = ['gibbonPersonID' => $this->gibbonPersonID];
            $sql = "SELECT gibbonRoleIDPrimary FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
            $result = $this->getContainer()->get(\Gibbon\Contracts\Database\Connection::class)->select($sql, $data);
            
            if ($result->rowCount() == 1) {
                $student = $result->fetch();
                $role = $this->roleGateway->getRoleCategory($student['gibbonRoleIDPrimary']);
                
                if ($role == 'Student' || $role == 'Staff') {
                    $table->addHeaderAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
                        ->addParam('gibbonPersonID', $this->gibbonPersonID)
                        ->addParam('gibbonSchoolYearID', $this->gibbonSchoolYearID)
                        ->addParam('type', $role)
                        ->addParam('allUsers', $_GET['allStudents'] ?? '')
                        ->displayLabel();
                }
            }
        }

        // Add export action if viewing own timetable
        if ($this->gibbonPersonID == $this->session->get('gibbonPersonID')) {
            $table->addHeaderAction('export', __('Export'))
                ->modalWindow()
                ->setURL('/modules/Timetable/tt_manage_subscription.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->setIcon('download')
                ->displayLabel();
        }

        return $table->render([['' => '']]);
    }

    /**
     * Render class list fallback when timetable access is not available
     * 
     * @return string HTML for class list
     */
    protected function renderClassListFallback(): string
    {
        $output = '<h4>';
        $output .= __('Class List');
        $output .= '</h4>';

        $resultDetail = $this->courseClassPersonGateway->selectClassesByStudent(
            $this->gibbonPersonID,
            $this->gibbonSchoolYearID
        );
        
        if ($resultDetail->rowCount() < 1) {
            $output .= '<div class="warning">'.__('There are no records to display.').'</div>';
        } else {
            $output .= '<ul>';
            while ($rowDetail = $resultDetail->fetch()) {
                $output .= '<li>';
                $output .= htmlPrep($rowDetail['courseFull'].' ('.$rowDetail['course'].'.'.$rowDetail['class'].')');
                $output .= '</li>';
            }
            $output .= '</ul>';
        }
        
        return $output;
    }
}
