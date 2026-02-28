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
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * BriefPage
 * 
 * Displays brief student profile information including year group, form group,
 * house, email, and website.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class BriefPage extends ProfilePage
{
    private YearGroupGateway $yearGroupGateway;
    private FormGroupGateway $formGroupGateway;
    private HouseGateway $houseGateway;
    private StudentGateway $studentGateway;

    public function __construct(
        Session $session,
        YearGroupGateway $yearGroupGateway,
        FormGroupGateway $formGroupGateway,
        HouseGateway $houseGateway,
        StudentGateway $studentGateway
    ) {
        parent::__construct($session);
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
        $this->houseGateway = $houseGateway;
        $this->studentGateway = $studentGateway;
    }

    /**
     * Check if the current user has permission to view the brief profile page
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'student_view_details', 'View Student Profile_brief');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Brief Profile');
    }


    /**
     * Generate HTML output for the brief profile page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch student data
        $student = $this->fetchStudentInfo();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        // Set breadcrumbs
        $this->setBreadcrumbs($student);

        // Set sidebar with student photo
        $this->setSidebar($student);

        // Render brief profile information
        return $this->renderBriefProfile($student);
    }

    /**
     * Fetch student information from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentInfo(): array
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

    /**
     * Set breadcrumbs for the page
     * 
     * @param array $student Student data
     */
    protected function setBreadcrumbs(array $student): void
    {
        global $page;
        
        $page->breadcrumbs
            ->add(__('View Student Profiles'), 'student_view.php')
            ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'));
    }

    /**
     * Set sidebar with student photo
     * 
     * @param array $student Student data
     */
    protected function setSidebar(array $student): void
    {
        $sidebarExtra = Format::userPhoto($student['image_240'], 240);
        $this->session->set('sidebarExtra', $sidebarExtra);
    }

    /**
     * Render brief profile information table
     * 
     * @param array $student Student data
     * @return string HTML for brief profile table
     */
    protected function renderBriefProfile(array $student): string
    {
        $table = DataTable::createDetails('briefProfile');

        // Year Group
        $table->addColumn('yearGroup', __('Year Group'))
            ->format(function($row) {
                if (empty($row['gibbonYearGroupID'])) {
                    return '';
                }
                
                $yearGroup = $this->yearGroupGateway->getByID($row['gibbonYearGroupID']);
                if (empty($yearGroup)) {
                    return '';
                }
                
                return __($yearGroup['name']);
            });

        // Form Group
        $table->addColumn('formGroup', __('Form Group'))
            ->format(function($row) {
                if (empty($row['gibbonFormGroupID'])) {
                    return '';
                }
                
                $formGroup = $this->formGroupGateway->getByID($row['gibbonFormGroupID']);
                if (empty($formGroup)) {
                    return '';
                }
                
                return $formGroup['name'];
            });

        // House
        $table->addColumn('house', __('House'))
            ->format(function($row) {
                if (empty($row['gibbonHouseID'])) {
                    return '';
                }
                
                $house = $this->houseGateway->getByID($row['gibbonHouseID']);
                if (empty($house)) {
                    return '';
                }
                
                return $house['name'];
            });

        // Email
        $table->addColumn('email', __('Email'))
            ->format(Format::using('link', ['email']));

        // Website
        $table->addColumn('website', __('Website'))
            ->format(Format::using('link', ['website']));

        return $table->render([$student]);
    }
}
