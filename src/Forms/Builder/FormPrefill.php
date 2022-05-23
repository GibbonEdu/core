<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Forms\Builder;

use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

class FormPrefill
{
    protected $session;
    protected $userGateway;
    protected $familyGateway;

    protected $prefillable = [];

    public function __construct(Session $session, UserGateway $userGateway, FamilyGateway $familyGateway)
    {
        $this->session = $session;
        $this->userGateway = $userGateway;
        $this->familyGateway = $familyGateway;
    }

    /**
     * Prefill values from the most recent application form attached to this account
     *
     * @param AdmissionsApplicationGateway $admissionsApplicationGateway
     * @param array $recentApplication
     * @return self
     */
    public function loadApplicationData(AdmissionsApplicationGateway $admissionsApplicationGateway, string $gibbonFormID, string $gibbonAdmissionsAccountID)
    {
        $recentApplication = $admissionsApplicationGateway->selectMostRecentApplicationByContext($gibbonFormID, 'gibbonAdmissionsAccount', $gibbonAdmissionsAccountID)->fetch();

        if (!empty($recentApplication)) {
            $this->prefillable = json_decode($recentApplication['data'] ?? '', true);
        }

        return $this;
    }

    /**
     * Prefill parent and family data, if this account has access to it.
     *
     * @param AdmissionsAccountGateway $admissionsAccountGateway
     * @param string $gibbonPersonID
     * @param string $accessID
     * @param string $accessToken
     * @return self
     */
    public function loadPersonalData(AdmissionsAccountGateway $admissionsAccountGateway, string $gibbonPersonID, string $accessID, string $accessToken)
    {
        // Check if this account can prefill user data
        $canAccessAccount = $canAccessData = false;
        if (!empty($gibbonPersonID)) {
            $accountCheck = $admissionsAccountGateway->getAccountByAccessToken($accessID, $accessToken);
            $canAccessAccount = !empty($accountCheck) && $gibbonPersonID == $accountCheck['gibbonPersonID'];
            $canAccessData = (!empty($accountCheck)) || ($this->session->get('gibbonPersonID') == $gibbonPersonID);
        }

        if ($canAccessAccount && $canAccessData) {
            // Load and prefill values for Parent 1
            $person = $this->userGateway->getSafeUserData($account['gibbonPersonID'] ?? '');
            foreach ($person ?? [] as $fieldName => $value) {
                $this->prefillable['parent1'.$fieldName] = $value;
            }

            // Load and prefill values for the family
            $family = $this->familyGateway->getByID($account['gibbonFamilyID'] ?? '');
            foreach ($family ?? [] as $fieldName => $value) {
                if ($fieldName == 'name') $fieldName = 'familyName';
                $this->prefillable[$fieldName] = $value;
            }
        }

        return $this;
    }

    /**
     * Only prefill fields that are marked as prefillable, and don't already exist.
     *
     * @param FormBuilderInterface $formBuilder
     * @param FormDataInterface $formData
     * @param array $values
     * @return self
     */
    public function prefill(FormBuilderInterface $formBuilder, FormDataInterface $formData, $pageNumber, &$values)
    {
        // Only prefill fields that are marked as prefillable, and don't already exist
        foreach ($this->prefillable as $fieldName => $value) {
            if ($formData->exists($fieldName)) continue;

            $field = $formBuilder->getField($fieldName);
            if ($field['pageNumber'] != $pageNumber && $pageNumber > 0) continue;
            if (empty($field['prefill']) || $field['prefill'] == 'N') continue;

            $values[$fieldName] = $value;
        }

        return $this;
    }
}
