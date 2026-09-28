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
use Gibbon\Domain\Staff\StaffFacilityGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * FacilitiesPage
 * 
 * Displays staff facility assignments including room names, phone extensions,
 * and usage types. This is a staff-specific subpage not present in student profiles.
 * Requires full profile access permissions.
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class FacilitiesPage extends ProfilePage
{
    private StaffFacilityGateway $staffFacilityGateway;

    public function __construct(
        Session $session,
        StaffFacilityGateway $staffFacilityGateway
    ) {
        parent::__construct($session);
        $this->staffFacilityGateway = $staffFacilityGateway;
    }

    public function getPageName(): string
    {
        return 'Facilities';
    }

    /**
     * Check if the current user has permission to view facility information
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Staff', 'staff_view_details', 'Staff Directory_full');
    }

    /**
     * Generate HTML output for the facilities subpage
     * 
     * @return string HTML content for display
     */
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

        // Fetch facilities data
        $facilities = $this->fetchFacilities();

        // Render facilities table
        return $this->renderFacilitiesTable($facilities);
    }

    /**
     * Fetch facility assignments for the staff member filtered by current school year
     * 
     * @return \Gibbon\Domain\DataSet Facilities data
     */
    protected function fetchFacilities()
    {
        $criteria = $this->staffFacilityGateway->newQueryCriteria();
        
        return $this->staffFacilityGateway->queryFacilitiesByPerson(
            $criteria,
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );
    }

    /**
     * Render facilities table with DataTable for consistent formatting
     * 
     * @param \Gibbon\Domain\DataSet $facilities Facilities data
     * @return string HTML output
     */
    protected function renderFacilitiesTable($facilities): string
    {
        $table = DataTable::create('facilities');

        $table->addColumn('name', __('Name'));
        $table->addColumn('phoneInternal', __('Extension'));
        $table->addColumn('usageType', __("Usage"))
            ->format(function($row) {
                return __($row['usageType']);
            });

        return $table->render($facilities);
    }
}
