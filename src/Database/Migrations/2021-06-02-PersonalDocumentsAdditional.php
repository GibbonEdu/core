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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\DataUpdater\PersonUpdateGateway;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Domain\Staff\StaffApplicationFormGateway;

/**
 * Personal Document Migration - move additional documents from application forms and data updates.
 */
class PersonalDocumentsAdditional extends Migration
{
    protected $db;
    protected $personalDocumentGateway;
    protected $studentApplicationFormGateway;
    protected $staffApplicationFormGateway;
    protected $personUpdateGateway;


    public function __construct(Connection $db, PersonalDocumentGateway $personalDocumentGateway, ApplicationFormGateway $studentApplicationFormGateway, StaffApplicationFormGateway $staffApplicationFormGateway, PersonUpdateGateway $personUpdateGateway)
    {
        $this->db = $db;
        $this->studentApplicationFormGateway = $studentApplicationFormGateway;
        $this->staffApplicationFormGateway = $staffApplicationFormGateway;
        $this->personUpdateGateway = $personUpdateGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Prevent running this migration if the field has already been removed/does not exist
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonPerson` LIKE 'citizenship1'");
        if (empty($fieldPresent) || $fieldPresent->rowCount() <= 0) return true;

        // STUDENT APPLICATIONS
        $results = $this->studentApplicationFormGateway->selectBy([])->fetchAll();

        foreach ($results as $values) {
            $timestamp = !empty($values['timestamp']) ? $values['timestamp'] : date('Y-m-d H:i:s');

            // Student Citizenship 1
            $data = [
                'country'        => !empty($values['citizenship1']) ? $values['citizenship1'] : null,
                'documentNumber' => !empty($values['citizenship1Passport']) ? $values['citizenship1Passport'] : null,
                'dateExpiry'     => !empty($values['citizenship1PassportExpiry']) ? $values['citizenship1PassportExpiry'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonApplicationForm',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Student National ID Card
            $data = [
                'documentNumber' => !empty($values['nationalIDCardNumber']) ? $values['nationalIDCardNumber'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonApplicationForm',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'ID Card',
                ]);
            }

            // Student Residency/Visa
            $data = [
                'documentType' => !empty($values['residencyStatus']) ? $values['residencyStatus'] : null,
                'dateExpiry'   => !empty($values['visaExpiryDate']) ? $values['visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonApplicationForm',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }

            // Student Birth Certificate
            $data = [
                'country'  => !empty($values['countryOfBirth']) ? $values['countryOfBirth'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 005,
                    'foreignTable'                 => 'gibbonApplicationForm',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Document',
                ]);
            }

            // Parent1 Citizenship 1
            $data = [
                'country'        => !empty($values['parent1citizenship1']) ? $values['parent1citizenship1'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonApplicationFormParent1',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Parent1 National ID Card
            $data = [
                'documentNumber' => !empty($values['parent1nationalIDCardNumber']) ? $values['parent1nationalIDCardNumber'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonApplicationFormParent1',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'ID Card',
                ]);
            }

            // Parent1 Residency/Visa
            $data = [
                'documentType' => !empty($values['parent1residencyStatus']) ? $values['parent1residencyStatus'] : null,
                'dateExpiry'   => !empty($values['parent1visaExpiryDate']) ? $values['parent1visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonApplicationFormParent1',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }

            // Parent2 Citizenship 1
            $data = [
                'country'        => !empty($values['parent2citizenship1']) ? $values['parent2citizenship1'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonApplicationFormParent2',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Parent2 National ID Card
            $data = [
                'documentNumber' => !empty($values['parent2nationalIDCardNumber']) ? $values['parent2nationalIDCardNumber'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonApplicationFormParent2',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'ID Card',
                ]);
            }

            // Parent2 Residency/Visa
            $data = [
                'documentType' => !empty($values['parent2residencyStatus']) ? $values['parent2residencyStatus'] : null,
                'dateExpiry'   => !empty($values['parent2visaExpiryDate']) ? $values['parent2visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonApplicationFormParent2',
                    'foreignTableID'               => $values['gibbonApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }
        }

        // STAFF APPLICATIONS
        $results = $this->staffApplicationFormGateway->selectBy([])->fetchAll();

        foreach ($results as $values) {
            $timestamp = !empty($values['timestamp']) ? $values['timestamp'] : date('Y-m-d H:i:s');

            // Staff Citizenship 1
            $data = [
                'country'        => !empty($values['citizenship1']) ? $values['citizenship1'] : null,
                'documentNumber' => !empty($values['citizenship1Passport']) ? $values['citizenship1Passport'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonStaffApplicationForm',
                    'foreignTableID'               => $values['gibbonStaffApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Staff National ID Card
            $data = [
                'documentNumber' => !empty($values['nationalIDCardNumber']) ? $values['nationalIDCardNumber'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonStaffApplicationForm',
                    'foreignTableID'               => $values['gibbonStaffApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'ID Card',
                ]);
            }

            // Staff Residency/Visa
            $data = [
                'documentType' => !empty($values['residencyStatus']) ? $values['residencyStatus'] : null,
                'dateExpiry'   => !empty($values['visaExpiryDate']) ? $values['visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonStaffApplicationForm',
                    'foreignTableID'               => $values['gibbonStaffApplicationFormID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }
        }

        // PERSONAL DATA UPDATES
        $results = $this->personUpdateGateway->selectBy(['status' => 'Pending'])->fetchAll();

        foreach ($results as $values) {
            $timestamp = !empty($values['timestamp']) ? $values['timestamp'] : date('Y-m-d H:i:s');

            // Updater Citizenship 1
            $data = [
                'country'        => !empty($values['citizenship1']) ? $values['citizenship1'] : null,
                'documentNumber' => !empty($values['citizenship1Passport']) ? $values['citizenship1Passport'] : null,
                'dateExpiry'     => !empty($values['citizenship1PassportExpiry']) ? $values['citizenship1PassportExpiry'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonPersonUpdate',
                    'foreignTableID'               => $values['gibbonPersonUpdateID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Updater Citizenship 2
            $data = [
                'country'        => !empty($values['citizenship2']) ? $values['citizenship2'] : null,
                'documentNumber' => !empty($values['citizenship2Passport']) ? $values['citizenship2Passport'] : null,
                'dateExpiry'     => !empty($values['citizenship2PassportExpiry']) ? $values['citizenship2PassportExpiry'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 002,
                    'foreignTable'                 => 'gibbonPersonUpdate',
                    'foreignTableID'               => $values['gibbonPersonUpdateID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Updater National ID Card
            $data = [
                'documentNumber' => !empty($values['nationalIDCardNumber']) ? $values['nationalIDCardNumber'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonPersonUpdate',
                    'foreignTableID'               => $values['gibbonPersonUpdateID'],
                    'timestamp'                    => $timestamp,
                ]);
            }

            // Updater Residency/Visa
            $data = [
                'documentType' => !empty($values['residencyStatus']) ? $values['residencyStatus'] : null,
                'dateExpiry'   => !empty($values['visaExpiryDate']) ? $values['visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonPersonUpdate',
                    'foreignTableID'               => $values['gibbonPersonUpdateID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }

            // Updater Birth Certificate
            $data = [
                'country'  => !empty($values['countryOfBirth']) ? $values['countryOfBirth'] : null,
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 005,
                    'foreignTable'                 => 'gibbonPersonUpdate',
                    'foreignTableID'               => $values['gibbonPersonUpdateID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Document',
                ]);
            }
        }

        return !$partialFail;
    }
}
