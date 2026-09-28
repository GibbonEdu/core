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
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * FamilyPage
 * 
 * Displays staff family relationships including:
 * - All families the staff member belongs to
 * - Family adults with their information
 * - Family children with their information
 * - Edit link for users with 'Manage Families_view' permission
 * - Full details when viewing own profile
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class FamilyPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private FamilyGateway $familyGateway;

    public function __construct(
        Session $session,
        FamilyGateway $familyGateway
    ) {
        parent::__construct($session);
        $this->familyGateway = $familyGateway;
    }

    public function getPageName(): string
    {
        return 'Family';
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

        $output = '';

        // Fetch family data
        $families = $this->fetchFamilyData();

        // Render edit button if user has permission
        $output .= $this->renderEditButton($families);

        // Render family information using template
        $output .= $this->renderFamilyTemplate($families);

        return $output;
    }

    /**
     * Fetch family data including adults and children
     * 
     * @return \Gibbon\Domain\DataSet Family data
     */
    protected function fetchFamilyData()
    {
        // Create criteria for family query
        $criteria = $this->familyGateway->newQueryCriteria()
            ->sortBy(['gibbonFamily.name'])
            ->fromPOST('family');

        // Query families by adult (staff member)
        $families = $this->familyGateway->queryFamiliesByAdult($criteria, $this->gibbonPersonID);
        $familyIDs = $families->getColumn('gibbonFamilyID');

        // Join children data per family
        $childrenData = $this->familyGateway->selectChildrenByFamily($familyIDs, true)->fetchGrouped();
        $families->joinColumn('gibbonFamilyID', 'children', $childrenData);

        // Join adult data per family
        $adultData = $this->familyGateway->selectAdultsByFamily($familyIDs, true)->fetchGrouped();
        $families->joinColumn('gibbonFamilyID', 'adults', $adultData);

        return $families;
    }

    /**
     * Render edit button for family management
     * 
     * @param \Gibbon\Domain\DataSet $families Family data
     * @return string HTML output
     */
    protected function renderEditButton($families): string
    {
        // Guard: check if user has permission to manage families
        if (!Access::allows('User Admin', 'family_manage_edit', 'Manage Families_view')) {
            return '';
        }

        // Get first family ID
        $familyIDs = $families->getColumn('gibbonFamilyID');
        $gibbonFamilyID = current($familyIDs);

        // Guard: check if family ID exists
        if (empty($gibbonFamilyID)) {
            return '';
        }

        // Create edit button form
        $form = Form::createBlank('buttons');
        $form->addHeaderAction('edit', __('Edit Family'))
            ->setURL('/modules/User Admin/family_manage_edit.php')
            ->addParam('gibbonFamilyID', $gibbonFamilyID)
            ->displayLabel();

        return $form->getOutput();
    }

    /**
     * Render family information using Twig template
     * 
     * @param \Gibbon\Domain\DataSet $families Family data
     * @return string HTML output
     */
    protected function renderFamilyTemplate($families): string
    {
        // Determine if full details should be shown (viewing own profile)
        $fullDetails = $this->gibbonPersonID == $this->session->get('gibbonPersonID');

        // Render using template
        return $this->getContainer()->get(\Gibbon\View\View::class)
            ->fetchFromTemplate('profile/family.twig.html', [
                'families' => $families,
                'fullDetails' => $fullDetails,
            ]);
    }
}
