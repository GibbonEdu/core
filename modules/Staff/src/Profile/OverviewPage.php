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
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Module\Staff\StaffAttendanceStatus;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * OverviewPage
 * 
 * Displays staff overview with photo, attendance status, basic information,
 * biography, custom fields, and embedded timetable.
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class OverviewPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private StaffGateway $staffGateway;
    private HouseGateway $houseGateway;
    private CustomFieldHandler $customFieldHandler;
    private StaffAttendanceStatus $staffAttendanceStatus;

    public function __construct(
        Session $session,
        StaffGateway $staffGateway,
        HouseGateway $houseGateway,
        CustomFieldHandler $customFieldHandler,
        StaffAttendanceStatus $staffAttendanceStatus
    ) {
        parent::__construct($session);
        $this->staffGateway = $staffGateway;
        $this->houseGateway = $houseGateway;
        $this->customFieldHandler = $customFieldHandler;
        $this->staffAttendanceStatus = $staffAttendanceStatus;
    }

    public function getPageName(): string
    {
        return 'Overview';
    }

    public function checkAccess(): bool
    {
        return Access::allows('Staff', 'staff_view_details', 'Staff Directory_full');
    }

    public function getOutput(): string
    {
        // Guard: validate staff ID
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'), 'error');
        }

        // Fetch staff data
        $staff = $this->fetchStaffData();

        // Guard: check data exists
        if (empty($staff)) {
            return Format::alert(__('The selected record does not exist.'), 'error');
        }

        $output = '';

        // Display attendance status
        $output .= $this->renderAttendanceStatus($staff);

        // Display overview table
        $output .= $this->renderOverviewTable($staff);

        // Display embedded timetable
        $output .= $this->renderEmbeddedTimetable();

        return $output;
    }

    /**
     * Fetch staff data including person and staff information
     * 
     * @return array Staff data or empty array if not found
     */
    protected function fetchStaffData(): array
    {
        return $this->staffGateway->getStaffDetailsByID($this->gibbonPersonID);
    }

    /**
     * Render staff attendance status if absent today
     * 
     * @param array $staff Staff data
     * @return string HTML output
     */
    protected function renderAttendanceStatus(array $staff): string
    {
        return $this->staffAttendanceStatus->getCurrentAttendanceStatus(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID,
            $staff['title'] ?? '',
            $staff['preferredName'] ?? '',
            $staff['surname'] ?? ''
        );
    }

    /**
     * Render overview table with basic information, biography, and custom fields
     * 
     * @param array $staff Staff data
     * @return string HTML output
     */
    protected function renderOverviewTable(array $staff): string
    {
        $table = DataTable::createDetails('overview');

        // Add header actions for editing
        if (Access::allows('User Admin', 'user_manage_edit', 'Manage Users_edit')) {
            $table->addHeaderAction('edit', __('Edit User'))
                ->setURL('/modules/User Admin/user_manage_edit.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->displayLabel();
        }

        if (Access::allows('Staff', 'staff_manage_edit', 'Manage Staff_edit')) {
            $table->addHeaderAction('edit2', __('Edit Staff'))
                ->setIcon('config')
                ->setURL('/modules/Staff/staff_manage_edit.php')
                ->addParam('gibbonStaffID', $staff['gibbonStaffID'] ?? '')
                ->displayLabel();
        }

        // Basic Information column
        $col = $table->addColumn('Basic Information');

        $col->addColumn('preferredName', __('Name'))
            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));
        $col->addColumn('type', __('Staff Type'))->translatable();
        $col->addColumn('jobTitle', __('Job Title'));
        $col->addColumn('username', __('Username'));
        $col->addColumn('email', __('Email'))->format(Format::using('link', 'email'));
        
        if (!empty($staff['website'])) {
            $col->addColumn('website', __('Website'))->format(Format::using('link', 'website'));
        }

        if (!empty($staff['gibbonHouseID'])) {
            $house = $this->houseGateway->getByID($staff['gibbonHouseID'], ['name']);
            $staff['houseName'] = $house['name'] ?? '';
            $col->addColumn('houseName', __('House'));
        }

        // Biography column
        $col = $table->addColumn('Biography', __('Biography'));

        $col->addColumn('countryOfOrigin', __('Country Of Origin'));
        $col->addColumn('qualifications', __('Qualifications'))->addClass('col-span-2');
        $col->addColumn('biography', __('Biography'))->addClass('col-span-3');

        // Custom Fields
        $this->customFieldHandler->addCustomFieldsToTable(
            $table, 
            'Staff', 
            ['heading' => 'Other Information', 'withHeading' => ['Basic Information', 'Biography']], 
            $staff['fieldsStaff'] ?? ''
        );

        // Append first aid details
        $headingCol = $table->getColumn('Basic Information');
        $headingCol->addColumn('firstAidQualified', __('First Aid Qualified'))
            ->addClass('grid')
            ->format(Format::using('yesNo', 'firstAidQualified'));

        return $table->render([$staff]);
    }

    /**
     * Render embedded timetable with permission check
     * 
     * @return string HTML output
     */
    protected function renderEmbeddedTimetable(): string
    {
        // Guard: check timetable access permission
        if (!Access::allows('Timetable', 'tt_view')) {
            return '';
        }

        $output = '';
        
        // Add anchor for direct linking
        $output .= "<a name='timetable'></a>";
        $output .= '<h4>' . __('Timetable') . '</h4>';

        $table = DataTable::createDetails('timetable');

        // Add edit action if user has permission
        if (Access::allows('Timetable Admin', 'courseEnrolment_manage_byPerson_edit', 'Course Enrolment by Person_edit')) {
            $table->addHeaderAction('edit', __('Edit'))
                ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->addParam('gibbonSchoolYearID', $this->gibbonSchoolYearID)
                ->addParam('type', 'Staff')
                ->addParam('allUsers', '')
                ->displayLabel();
        }

        // Add export action if viewing own profile
        if ($this->gibbonPersonID == $this->session->get('gibbonPersonID')) {
            $table->addHeaderAction('export', __('Export'))
                ->modalWindow()
                ->setURL('/modules/Timetable/tt_manage_subscription.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->setIcon('download')
                ->displayLabel();
        }

        $output .= $table->render(['' => '']);

        // Get URL parameters for timetable navigation
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

        return $output;
    }
}
