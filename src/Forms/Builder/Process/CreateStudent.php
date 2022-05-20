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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateStudentView;
use Gibbon\Forms\Builder\Exception\FormProcessException;

class CreateStudent extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['preferredName', 'surname'];

    protected $userGateway;
    protected $usernameGenerator;
    protected $customFieldGateway;
    protected $personalDocumentGateway;

    public function __construct(UserGateway $userGateway, UsernameGenerator $usernameGenerator, CustomFieldGateway $customFieldGateway, PersonalDocumentGateway $personalDocumentGateway)
    {
        $this->userGateway = $userGateway;
        $this->usernameGenerator = $usernameGenerator;
        $this->customFieldGateway = $customFieldGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }

    public function getViewClass() : string
    {
        return CreateStudentView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createStudent') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Generate user details
        $this->generateUsername($formData);
        $this->generatePassword($formData);

        if (!$formData->has('username') || !$formData->has('passwordStrong')) {
            throw new FormProcessException('Failed to generate username or password');
        }

        // Set and assign default values
        $this->setStatus($formData);
        $this->setDefaults($formData);
        $this->setCustomFields($formData);

        // Create new student account
        $gibbonPersonIDStudent = $this->userGateway->insert($this->getUserData($formData, '003'));
        if (empty($gibbonPersonIDStudent)) {
            throw new FormProcessException('Failed to insert student into the database');
        }

        // Update existing data
        $this->transferPersonalDocuments($builder, $formData, $gibbonPersonIDStudent);

        $formData->set('gibbonPersonIDStudent', $gibbonPersonIDStudent);
        $this->setResult($gibbonPersonIDStudent);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) return;

        $this->userGateway->delete($formData->get('gibbonPersonIDStudent'));

        $foreignTable = $builder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $builder->getConfig('foreignTableID');

        $this->personalDocumentGateway->updatePersonalDocumentOwnership('gibbonPerson', $formData->get('gibbonPersonIDStudent'), $foreignTable, $foreignTableID);
        
        $formData->set('gibbonPersonIDStudent', null);
    }

    protected function getUserData(FormDataInterface $formData, $gibbonRoleID, $prefix = '')
    {
        return [
            'gibbonRoleIDPrimary' => $gibbonRoleID,
            'gibbonRoleIDAll'     => $gibbonRoleID,
            'username'            => $formData->get($prefix.'username'),
            'passwordStrong'      => $formData->get($prefix.'passwordStrong'),
            'passwordStrongSalt'  => $formData->get($prefix.'passwordStrongSalt'),
            'status'              => $formData->get($prefix.'status'),
            'email'               => $formData->get($prefix.'email'),
            'emailAlternate'      => $formData->get($prefix.'emailAlternate'),
            'title'               => $formData->get($prefix.'title', ''),
            'surname'             => $formData->get($prefix.'surname'),
            'firstName'           => $formData->get($prefix.'firstName'),
            'preferredName'       => $formData->get($prefix.'preferredName'),
            'officialName'        => $formData->get($prefix.'officialName'),
            'nameInCharacters'    => $formData->get($prefix.'nameInCharacters', ''),
            'gender'              => $formData->get($prefix.'gender', 'Unspecified'),
            'dob'                 => $formData->get($prefix.'dob'),
            'languageFirst'       => $formData->get($prefix.'languageFirst', ''),
            'languageSecond'      => $formData->get($prefix.'languageSecond', ''),
            'languageThird'       => $formData->get($prefix.'languageThird', ''),
            'countryOfBirth'      => $formData->get($prefix.'countryOfBirth', ''),
            'website'             => $formData->get($prefix.'website', ''),
            'phone1Type'          => $formData->get($prefix.'phone1Type', ''),
            'phone1CountryCode'   => $formData->get($prefix.'phone1CountryCode', ''),
            'phone1'              => $formData->get($prefix.'phone1', ''),
            'phone2Type'          => $formData->get($prefix.'phone2Type', ''),
            'phone2CountryCode'   => $formData->get($prefix.'phone2CountryCode', ''),
            'phone2'              => $formData->get($prefix.'phone2', ''),
            'lastSchool'          => $formData->get($prefix.'lastSchool', ''),
            'dateStart'           => $formData->get($prefix.'dateStart'),
            'privacy'             => $formData->get($prefix.'privacy'),
            'dayType'             => $formData->get($prefix.'dayType'),
            'studentID'           => $formData->get($prefix.'studentID', ''),
            'fields'              => $formData->get($prefix.'fields', ''),
        ];
    }

    /**
     * Generate a unique username for the new student, or use the pre-defined one.
     *
     * @param FormDataInterface $formData
     */
    protected function generateUsername(FormDataInterface $formData, $prefix = '')
    {
        if ($formData->has($prefix.'username')) {
            return;
        }

        $this->usernameGenerator->addToken('preferredName', $formData->get('preferredName'));
        $this->usernameGenerator->addToken('firstName', $formData->get('firstName'));
        $this->usernameGenerator->addToken('surname', $formData->get('surname'));

        $formData->set($prefix.'username', $this->usernameGenerator->generateByRole('003'));
    }

    /**
     * Generate a random password
     *
     * @param FormDataInterface $formData
     */
    protected function generatePassword(FormDataInterface $formData, $prefix = '')
    {
        $formData->set($prefix.'password', randomPassword(8));
        $formData->set($prefix.'passwordStrongSalt', getSalt());
        $formData->set($prefix.'passwordStrong', hash('sha256', $formData->get('passwordStrongSalt').$formData->get('password')));
    }

    /**
     * Set the initial status for the student based on the school year of entry.
     *
     * @param FormDataInterface $formData
     */
    protected function setStatus(FormDataInterface $formData, $prefix = '')
    {
        // $schoolYearEntry['status'] == 'Upcoming' && $informStudent != 'Y' ? 'Expected' : 'Full'
        $formData->set($prefix.'status', 'Full');
    }

    /**
     * Set default values for those not provided by the form.
     *
     * @param FormDataInterface $formData
     */
    protected function setDefaults(FormDataInterface $formData, $prefix = '')
    {
        if (!$formData->has($prefix.'firstName')) {
            $formData->set($prefix.'firstName', $formData->get($prefix.'preferredName'));
        }

        if (!$formData->has($prefix.'officialName')) {
            $formData->set($prefix.'officialName', $formData->get($prefix.'firstName').' '.$formData->get($prefix.'surname'));
        }
    }

    /**
     * Transfer values from form data into json custom field data
     *
     * @param FormDataInterface $formData
     * @param string $prefix
     */
    protected function setCustomFields(FormDataInterface $formData, $prefix = '')
    {
        $customFields = $this->customFieldGateway->selectCustomFields('User', [])->fetchAll();
        $fields = [];

        foreach ($customFields as $field) {
            $id = 'custom'.$field['gibbonCustomFieldID'];
            if (!$formData->has($id)) continue;

            $fields[$field['gibbonCustomFieldID']] = $formData->get($id);
        }

        $formData->set($prefix.'fields', json_encode($fields));
    }

    /**
     * Transfer ownership of personal documents by updating the foreign table
     *
     * @param FormBuilderInterface $builder
     * @param FormDataInterface $formData
     * @param string $gibbonPersonID
     */
    protected function transferPersonalDocuments(FormBuilderInterface $builder, FormDataInterface $formData, string $gibbonPersonID)
    {
        $foreignTable = $builder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $builder->getConfig('foreignTableID');

        $this->personalDocumentGateway->updatePersonalDocumentOwnership($foreignTable, $foreignTableID, 'gibbonPerson', $gibbonPersonID);
    }
}
