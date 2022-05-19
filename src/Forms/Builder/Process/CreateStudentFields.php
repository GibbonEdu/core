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

use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\FormProcessException;

class CreateStudentFields extends AbstractFormProcess
{
    protected $requiredFields = ['preferredName', 'surname'];

    private $userGateway;
    private $customFieldGateway;
    private $personalDocumentGateway;

    public function __construct(UserGateway $userGateway, CustomFieldGateway $customFieldGateway, PersonalDocumentGateway $personalDocumentGateway)
    {
        $this->userGateway = $userGateway;
        $this->customFieldGateway = $customFieldGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }
    
    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createStudent') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) {
            throw new FormProcessException('Failed to generate username or password');
            return;
        }

        // Update custom data
        $this->transferCustomFields($formData);
        $this->transferPersonalDocuments($builder, $formData);

        // Set and assign default values
        $this->setLastSchool($formData);
        $this->setStudentEmail($builder, $formData);
        $this->setStudentWebsite($builder, $formData);

        $data = [
            'email'               => $formData->get('email'),
            'emailAlternate'      => $formData->get('emailAlternate'),
            'website'             => $formData->get('website', ''),
            'lastSchool'          => $formData->get('lastSchool', ''),
            'fields'              => $formData->get('fields', ''),
        ];

        $updated = $this->userGateway->update($formData->get('gibbonPersonIDStudent'), $data);

        $this->setResult($updated);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) return;

        $this->userGateway->update($formData->get('gibbonPersonIDStudent'), [
            'email'               => null,
            'emailAlternate'      => null,
            'website'             => '',
            'lastSchool'          => '',
            'fields'              => '',
        ]);

        $foreignTable = $builder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $builder->getConfig('foreignTableID');

        $this->personalDocumentGateway->updatePersonalDocumentOwnership('gibbonPerson', $formData->get('gibbonPersonIDStudent'), $foreignTable, $foreignTableID);

        $formData->set('gibbonPersonIDStudent', null);
    }

    private function transferCustomFields(FormDataInterface $formData)
    {
        $customFields = $this->customFieldGateway->selectCustomFields('User', [])->fetchAll();
        $fields = [];

        foreach ($customFields as $field) {
            $id = 'custom'.$field['gibbonCustomFieldID'];
            if (!$formData->has($id)) continue;

            $fields[$field['gibbonCustomFieldID']] = $formData->get($id);
        }

        $formData->set('fields', json_encode($fields));
    }

    private function transferPersonalDocuments(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $foreignTable = $builder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $builder->getConfig('foreignTableID');

        $this->personalDocumentGateway->updatePersonalDocumentOwnership($foreignTable, $foreignTableID, 'gibbonPerson', $formData->get('gibbonPersonIDStudent'));
    }

    /**
     * Determine the last school based on dates provided
     *
     * @param FormDataInterface $formData
     */
    private function setLastSchool(FormDataInterface $formData)
    {
        if ($formData->get('schoolDate2', date('Y-m-d')) > $formData->get('schoolDate1', date('Y-m-d'))) {
            $formData->set('lastSchool', $formData->get('schoolName2'));
        } else {
            $formData->set('lastSchool', $formData->get('schoolName1'));
        }
    }

    /**
     * Set default email address for student
     *
     * @param FormBuilderInterface $builder
     * @param FormDataInterface $formData
     */
    private function setStudentEmail(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$builder->hasConfig('studentDefaultEmail')) return;

        $formData->set('emailAlternate', $formData->get('email'));
        $formData->set('email', str_replace('[username]', $formData->get('username'), $builder->getConfig('studentDefaultEmail')));
    }

    /**
     * Set default website address for student
     *
     * @param FormBuilderInterface $builder
     * @param FormDataInterface $formData
     */
    private function setStudentWebsite(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$builder->hasConfig('studentDefaultWebsite'))

        $formData->set('website', str_replace('[username]', $formData->get('username'), $builder->getConfig('studentDefaultWebsite')));
    }
}
