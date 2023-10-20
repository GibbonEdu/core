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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Data\PasswordPolicy;
use Gibbon\Data\UsernameGenerator;
use Gibbon\Domain\User\FamilyAdultGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\UserStatusLogGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Process\CreateStudent;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateParentsView;

class CreateParents extends CreateStudent implements ViewableProcess
{
    protected $requiredFields = ['parent1preferredName', 'parent1surname', 'parent1relationship'];

    protected $familyAdultGateway;

    public function __construct(
        UserGateway $userGateway,
        UserStatusLogGateway $userStatusLogGateway,
        UsernameGenerator $usernameGenerator,
        CustomFieldGateway $customFieldGateway,
        PersonalDocumentGateway $personalDocumentGateway,
        FamilyAdultGateway $familyAdultGateway,
        PasswordPolicy $passwordPolicy
    )
    {
        $this->familyAdultGateway = $familyAdultGateway;

        parent::__construct(
            $userGateway,
            $userStatusLogGateway,
            $usernameGenerator,
            $customFieldGateway,
            $personalDocumentGateway,
            $passwordPolicy
        );
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
        if (!$formData->has('gibbonPersonIDParent1') && $formData->has('parent1surname') && $formData->hasAny(['parent1preferredName','parent1firstName'])) {
            $this->createParentAccount($builder, $formData, '1');
        }

        // Update new or existing Parent 1
        if ($formData->has('gibbonPersonIDParent1')) {
            $this->updateParentRole($formData, '1');
            $this->updateParentData($formData, '1');
            $this->addParentToFamily($formData, '1');
        }

        // Create Parent 2
        if (!$formData->has('gibbonPersonIDParent2') && $formData->has('parent2surname')&& $formData->hasAny(['parent2preferredName','parent2firstName'])) {
            $this->createParentAccount($builder, $formData, '2');
        }

        // Update new or existing Parent 2
        if ($formData->has('gibbonPersonIDParent2')) {
            $this->updateParentRole($formData, '2');
            $this->updateParentData($formData, '2');
            $this->addParentToFamily($formData, '2');
        }

        $this->setResult(true);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDParent1')) return;

        // Remove the relationships, they are always new
        $this->familyAdultGateway->deleteFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent1'), $formData->get('gibbonPersonIDStudent'));
        $this->familyAdultGateway->deleteFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent2'), $formData->get('gibbonPersonIDStudent'));

        // Only disconnect family if they were connected during this process
        if ($formData->has('parent1adultAdded')) {
            $this->familyAdultGateway->deleteFamilyAdult($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent1'));
        }

        if ($formData->has('parent2adultAdded')) {
            $this->familyAdultGateway->deleteFamilyAdult($formData->get('gibbonFamilyID'), $formData->get('gibbonPersonIDParent2'));
        }

        // Only remove roles if they were added during this process
        if ($formData->has('parent1roleChanged')) {
            $this->userGateway->removeRoleFromUser($formData->get('gibbonPersonIDParent1'), '004');
        }

        if ($formData->has('parent2roleChanged')) {
            $this->userGateway->removeRoleFromUser($formData->get('gibbonPersonIDParent2'), '004');
        }

        // Only remove users if they were created during this process
        if ($formData->has('parent1created')) {
            $this->userGateway->delete($formData->get('gibbonPersonIDParent1'));
            $formData->set('gibbonPersonIDParent1', null);
        }

        if ($formData->has('parent2created')) {
            $this->userGateway->delete($formData->get('gibbonPersonIDParent2'));
            $formData->set('gibbonPersonIDParent2', null);
        }
    }

    protected function createParentAccount(FormBuilderInterface $builder, FormDataInterface $formData, $i)
    {
        // Generate user details
        $this->generateUsername($formData, '004', "parent{$i}");
        $this->generatePassword($formData, "parent{$i}");

        // Set and assign default values
        $this->setStatus($formData, "parent{$i}");
        $this->setDefaults($formData, "parent{$i}");
        $this->setCustomFields($formData, "parent{$i}");

        // Create and store the new parent account
        $gibbonPersonID = $this->userGateway->insert($this->getUserData($formData, '004', "parent{$i}"));
        $formData->set("gibbonPersonIDParent{$i}", $gibbonPersonID);
        $formData->set("parent{$i}created", !empty($gibbonPersonID));

        // Create the status log
        $this->userStatusLogGateway->insert(['gibbonPersonID' => $gibbonPersonID, 'statusOld' => '', 'statusNew' => $formData->get("parent{$i}status"), 'reason' => __('Created')]);

        // Update existing data
        $this->transferPersonalDocuments($builder, $formData, $gibbonPersonID);
    }

    protected function updateParentRole(FormDataInterface $formData, $i)
    {
        $updated = $this->userGateway->addRoleToUser($formData->get("gibbonPersonIDParent{$i}"), '004');
        $formData->set("parent{$i}roleChanged", $updated);
    }

    protected function updateParentData(FormDataInterface $formData, $i)
    {
        $excludeFields = ['gibbonRoleIDPrimary', 'gibbonRoleIDAll', 'username', 'passwordStrong', 'passwordStrongSalt'];

        $userData = $this->getUserData($formData, '004', "parent{$i}");
        $userData = array_diff_key($userData, array_flip($excludeFields));

        $person = $this->userGateway->getByID($formData->get("gibbonPersonIDParent{$i}"), array_keys($userData));

        if ($person['status'] != 'Left') return;

        $updatedData = array_merge($person, $userData);

        $updated = $this->userGateway->update($formData->get("gibbonPersonIDParent{$i}"), $updatedData);
        $formData->set("parent{$i}updated", $updated);
    }

    protected function addParentToFamily(FormDataInterface $formData, $i)
    {
        if (!$formData->hasAll(["gibbonFamilyID", "parent{$i}relationship", "gibbonPersonIDParent{$i}", "gibbonPersonIDStudent"])) {
            return;
        }

        $existing = $this->familyAdultGateway->selectBy(['gibbonFamilyID' => $formData->get('gibbonFamilyID'), 'gibbonPersonID' => $formData->get("gibbonPersonIDParent{$i}")])->fetch();

        if (empty($existing)) {
            $gibbonFamilyAdultID = $this->familyAdultGateway->insert([
                'gibbonFamilyID'  => $formData->get('gibbonFamilyID'),
                'gibbonPersonID'  => $formData->get("gibbonPersonIDParent{$i}"),
                'childDataAccess' => 'Y',
                'contactPriority' => $i,
                'contactCall'     => 'Y',
                'contactSMS'      => 'Y',
                'contactEmail'    => 'Y',
                'contactMail'     => 'Y',
            ]);
            $formData->set("parent{$i}adultAdded", !empty($gibbonFamilyAdultID));
        } else {
            $gibbonFamilyAdultID = $existing['gibbonFamilyAdultID'];
        }

        $this->familyAdultGateway->insertFamilyRelationship($formData->get('gibbonFamilyID'), $formData->get("gibbonPersonIDParent{$i}"), $formData->get('gibbonPersonIDStudent'), $formData->get("parent{$i}relationship"));

        $formData->set("parent{$i}adultLinked", !empty($gibbonFamilyAdultID));
    }
}
