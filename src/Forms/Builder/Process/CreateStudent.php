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
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateStudentView;
use Gibbon\Forms\Builder\Exception\FormProcessException;

class CreateStudent extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['preferredName', 'surname'];

    private $userGateway;
    private $usernameGenerator;

    public function __construct(UserGateway $userGateway, UsernameGenerator $usernameGenerator)
    {
        $this->userGateway = $userGateway;
        $this->usernameGenerator = $usernameGenerator;
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
            return;
        }

        // Set and assign default values
        $this->setStatus($formData);
        $this->setDefaults($formData);

        $data = [
            'gibbonRoleIDPrimary' => '003',
            'gibbonRoleIDAll'     => '003',
            'username'            => $formData->get('username'),
            'passwordStrong'      => $formData->get('passwordStrong'),
            'passwordStrongSalt'  => $formData->get('passwordStrongSalt'),
            'status'              => $formData->get('status'),
            'email'               => $formData->get('email'),
            'emailAlternate'      => $formData->get('emailAlternate'),
            'title'               => $formData->get('title', ''),
            'surname'             => $formData->get('surname'),
            'firstName'           => $formData->get('firstName'),
            'preferredName'       => $formData->get('preferredName'),
            'officialName'        => $formData->get('officialName'),
            'nameInCharacters'    => $formData->get('nameInCharacters', ''),
            'gender'              => $formData->get('gender', 'Unspecified'),
            'dob'                 => $formData->get('dob'),
            'languageFirst'       => $formData->get('languageFirst', ''),
            'languageSecond'      => $formData->get('languageSecond', ''),
            'languageThird'       => $formData->get('languageThird', ''),
            'countryOfBirth'      => $formData->get('countryOfBirth', ''),
            'website'             => $formData->get('website', ''),
            'phone1Type'          => $formData->get('phone1Type', ''),
            'phone1CountryCode'   => $formData->get('phone1CountryCode', ''),
            'phone1'              => $formData->get('phone1', ''),
            'phone2Type'          => $formData->get('phone2Type', ''),
            'phone2CountryCode'   => $formData->get('phone2CountryCode', ''),
            'phone2'              => $formData->get('phone2', ''),
            'lastSchool'          => $formData->get('lastSchool', ''),
            'dateStart'           => $formData->get('dateStart'),
            'privacy'             => $formData->get('privacy'),
            'dayType'             => $formData->get('dayType'),
            'studentID'           => $formData->get('studentID', ''),
        ];

        $gibbonPersonIDStudent = $this->userGateway->insert($data);

        if (empty($gibbonPersonIDStudent)) throw new FormProcessException('Failed to insert student into the database');

        $formData->set('gibbonPersonIDStudent', $gibbonPersonIDStudent);
        $this->setResult($gibbonPersonIDStudent);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) return;

        $this->userGateway->delete($formData->get('gibbonPersonIDStudent'));

        $formData->set('gibbonPersonIDStudent', null);
    }

    /**
     * Generate a unique username for the new student, or use the pre-defined one.
     *
     * @param FormDataInterface $formData
     */
    private function generateUsername(FormDataInterface $formData)
    {
        if ($formData->has('username')) {
            return;
        }

        $this->usernameGenerator->addToken('preferredName', $formData->get('preferredName'));
        $this->usernameGenerator->addToken('firstName', $formData->get('firstName'));
        $this->usernameGenerator->addToken('surname', $formData->get('surname'));

        $formData->set('username', $this->usernameGenerator->generateByRole('003'));
    }

    /**
     * Generate a random password
     *
     * @param FormDataInterface $formData
     */
    private function generatePassword(FormDataInterface $formData)
    {
        $formData->set('passwordStrongSalt', getSalt());
        $formData->set('passwordStrong', hash('sha256', $formData->get('passwordStrongSalt').randomPassword(8)));
    }

    /**
     * Set the initial status for the student based on the school year of entry.
     *
     * @param FormDataInterface $formData
     */
    private function setStatus(FormDataInterface $formData)
    {
        // $schoolYearEntry['status'] == 'Upcoming' && $informStudent != 'Y' ? 'Expected' : 'Full'
        $formData->set('status', 'Full');
    }

    /**
     * Set default values for those not provided by the form.
     *
     * @param FormDataInterface $formData
     */
    private function setDefaults(FormDataInterface $formData)
    {
        if (!$formData->has('firstName')) {
            $formData->set('firstName', $formData->get('preferredName'));
        }

        if (!$formData->has('officialName')) {
            $formData->set('officialName', $formData->get('firstName').' '.$formData->get('surname'));
        }
    }
}
