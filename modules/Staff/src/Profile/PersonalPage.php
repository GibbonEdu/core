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
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * PersonalPage
 * 
 * Displays detailed personal information for staff members including:
 * - Basic information (name, staff type, job title, initials, gender)
 * - Contact information (phone numbers, email, alternate email, website)
 * - First aid qualifications with expiry status
 * - Miscellaneous information (transport, vehicle registration, locker number)
 * - Custom fields for Staff and Person contexts
 * - Personal documents (if user has confidential permission)
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class PersonalPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private StaffGateway $staffGateway;
    private CustomFieldHandler $customFieldHandler;
    private PersonalDocumentGateway $personalDocumentGateway;

    public function __construct(
        Session $session,
        StaffGateway $staffGateway,
        CustomFieldHandler $customFieldHandler,
        PersonalDocumentGateway $personalDocumentGateway
    ) {
        parent::__construct($session);
        $this->staffGateway = $staffGateway;
        $this->customFieldHandler = $customFieldHandler;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }

    public function getPageName(): string
    {
        return 'Personal';
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

        // Display personal information table
        $output .= $this->renderPersonalTable($staff);

        // Display personal documents if user has confidential permission
        $output .= $this->renderPersonalDocuments();

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
     * Render personal information table with all sections
     * 
     * @param array $staff Staff data
     * @return string HTML output
     */
    protected function renderPersonalTable(array $staff): string
    {
        $table = DataTable::createDetails('personal');

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
        $col->addColumn('initials', __('Initials'));
        $col->addColumn('gender', __('Gender'))
            ->format(Format::using('genderName', 'gender'));

        // Contacts column
        $col = $table->addColumn('Contacts', __('Contacts'));

        for ($i = 1; $i < 5; ++$i) {
            if (empty($staff['phone' . $i])) continue;
            if ($staff['phone' . $i] != '') {
                $col->addColumn('phone' . $i, __('Phone') . " $i")
                    ->format(Format::using('phone', ['phone' . $i, 'phone'.$i.'CountryCode', 'phone'.$i.'Type']));
            }
        }

        $col->addColumn('email', __('Email'))
            ->format(Format::using('link', 'email'));

        $col->addColumn('emailAlternate', __('Alternate Email'))
            ->format(function($row) {
                if ($row['emailAlternate'] != '') {
                    return Format::link($row['emailAlternate']);
                }
                return '';
            });

        $col->addColumn('website', __('Website'))
            ->format(Format::using('link', ['website', 'website']));

        // First Aid column
        $col = $table->addColumn('First Aid', __('First Aid'));

        $col->addColumn('firstAidQualified', __('First Aid Qualified'))
            ->addClass('grid')
            ->format(Format::using('yesNo', 'firstAidQualified'));
        
        if ($staff["firstAidQualified"] == "Y") {
            $col->addColumn('firstAidQualification', __('First Aid Qualification'))
                ->addClass('grid')
                ->format(Format::using('truncate', 'firstAidQualification'));
            $col->addColumn('firstAidExpiry', __('Expiry Date'))
                ->format(function($row) {
                    $output = Format::date($row['firstAidExpiry']);
                    if ($row['firstAidExpiry'] <= date('Y-m-d')) {
                        $output .= Format::tag(__('Expired'), 'warning ml-2');
                    }
                    else if ($row['firstAidExpiry'] > date('Y-m-d')) {
                        $output .= Format::tag(__('Current'), 'success ml-2');
                    }
                    return $output;
                });
        }

        // Miscellaneous column
        $col = $table->addColumn('Miscellaneous', __('Miscellaneous'));

        $col->addColumn('transport', __('Transport'));
        $col->addColumn('vehicleRegistration', __('Vehicle Registration'));
        $col->addColumn('lockerNumber', __('Locker Number'));

        // Custom Fields for Staff context
        $this->customFieldHandler->addCustomFieldsToTable(
            $table, 
            'Staff', 
            ['withoutHeading' => ['Biography']], 
            $staff['fieldsStaff'] ?? ''
        );
        
        // Custom Fields for Person context with staff flag
        $this->customFieldHandler->addCustomFieldsToTable(
            $table, 
            'Person', 
            ['staff' => 1], 
            $staff['fields'] ?? ''
        );

        return $table->render([$staff]);
    }

    /**
     * Render personal documents section if user has confidential permission
     * 
     * @return string HTML output
     */
    protected function renderPersonalDocuments(): string
    {
        // Guard: check confidential permission
        if (!Access::allows('Staff', 'staff_manage', 'Manage Staff_confidential')) {
            return '';
        }

        $params = ['staff' => true, 'notEmpty' => true];
        $documents = $this->personalDocumentGateway->selectPersonalDocuments(
            'gibbonPerson', 
            $this->gibbonPersonID, 
            $params
        )->fetchAll();

        return $this->getContainer()->get(\Gibbon\View\View::class)
            ->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents]);
    }
}
