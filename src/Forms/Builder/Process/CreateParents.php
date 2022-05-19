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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Data\UsernameGenerator;
use Gibbon\Domain\User\FamilyAdultGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Process\CreateStudent;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\FormProcessException;
use Gibbon\Forms\Builder\View\CreateParentsView;

class CreateParents extends CreateStudent implements ViewableProcess
{
    protected $requiredFields = ['parent1preferredName', 'parent1surname', 'parent1relationship'];

    private $familyAdultGateway;

    public function __construct(UserGateway $userGateway, UsernameGenerator $usernameGenerator, FamilyAdultGateway $familyAdultGateway, FamilyRe)
    {
        $this->familyAdultGateway = $familyAdultGateway;

        parent::__construct($userGateway, $usernameGenerator);
    }

    public function getViewClass() : string
    {
        return CreateParentsView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createParents') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Create Parent 1
        if (!$formData->has('gibbonPersonIDParent1') && $formData->hasAll(['parent1surname', 'parent1preferredName'])) {
            // Generate user details
            $this->generateUsername($formData, 'parent1');
            $this->generatePassword($formData, 'parent1');

            // Set and assign default values
            $this->setStatus($formData, 'parent1');
            $this->setDefaults($formData, 'parent1');

            // Create and store the new parent account
            $gibbonPersonIDParent1 = $this->userGateway->insert($this->getUserData($formData, '004', 'parent1'));
            $formData->set('gibbonPersonIDParent1', $gibbonPersonIDParent1);
        }

        // Add Parent 1 to family
        if ($formData->hasAll(['gibbonFamilyID', 'parent1relationship', 'gibbonPersonIDParent1', 'gibbonPersonIDStudent'])) {
            $gibbonPersonIDParent1Adult = $this->familyAdultGateway->insert([
                'gibbonFamilyID'  => $formData->get('gibbonFamilyID'),
                'gibbonPersonID'  => $formData->get('gibbonPersonIDParent1'),
                'childDataAccess' => 'Y',
                'contactPriority' => 1,
                'contactCall'     => 'Y',
                'contactSMS'      => 'Y',
                'contactEmail'    => 'Y',
                'contactMail'     => 'Y',
            ]);

            if ($gibbonPersonIDParent1Adult) {
                $this->familyAdultGateway->insertFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent1'),  $formData->get('gibbonPersonIDStudent'), $formData->get('parent1relationship'));
            }
        }

        // Create Parent 2
        if (!$formData->has('gibbonPersonIDParent2') && $formData->hasAll(['parent2surname', 'parent2preferredName'])) {
            // Generate user details
            $this->generateUsername($formData, 'parent2');
            $this->generatePassword($formData, 'parent2');

            // Set and assign default values
            $this->setStatus($formData, 'parent2');
            $this->setDefaults($formData, 'parent2');

            // Create and store the new parent account
            $gibbonPersonIDParent2 = $this->userGateway->insert($this->getUserData($formData, '004', 'parent2'));
            $formData->set('gibbonPersonIDParent2', $gibbonPersonIDParent2);
        }

        // Add Parent 2 to family
        if ($formData->hasAll(['gibbonFamilyID', 'parent2relationship', 'gibbonPersonIDParent2', 'gibbonPersonIDStudent'])) {
            $gibbonPersonIDParent2Adult = $this->familyAdultGateway->insert([
                'gibbonFamilyID'  => $formData->get('gibbonFamilyID'),
                'gibbonPersonID'  => $formData->get('gibbonPersonIDParent2'),
                'childDataAccess' => 'Y',
                'contactPriority' => 2,
                'contactCall'     => 'Y',
                'contactSMS'      => 'Y',
                'contactEmail'    => 'Y',
                'contactMail'     => 'Y',
            ]);

            if ($gibbonPersonIDParent2Adult) {
                $this->familyAdultGateway->insertFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent2'),  $formData->get('gibbonPersonIDStudent'), $formData->get('parent2relationship'));
            }
        }

        $this->setResult(true);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDParent1')) return;

        $this->userGateway->delete($formData->get('gibbonPersonIDParent1'));
        $this->userGateway->delete($formData->get('gibbonPersonIDParent2'));

        $this->familyAdultGateway->deleteFamilyAdult($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent1'));
        $this->familyAdultGateway->deleteFamilyAdult($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent2'));

        $this->familyAdultGateway->deleteFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent1'));
        $this->familyAdultGateway->deleteFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent2'));

        $formData->set('gibbonPersonIDParent1', null);
        $formData->set('gibbonPersonIDParent2', null);
    }
}
