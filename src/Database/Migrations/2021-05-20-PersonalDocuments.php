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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\User\PersonalDocumentGateway;

/**
 * Personal Document Migration - move document data into normalized table entries.
 */
class PersonalDocuments extends Migration
{
    protected $db;
    protected $userGateway;
    protected $personalDocumentGateway;

    public function __construct(Connection $db, UserGateway $userGateway, PersonalDocumentGateway $personalDocumentGateway)
    {
        $this->db = $db;
        $this->userGateway = $userGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Prevent running this migration if the field has already been removed/does not exist
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonPerson` LIKE 'citizenship1'");
        if (empty($fieldPresent) || $fieldPresent->rowCount() <= 0) return true;

        $users = $this->userGateway->selectBy([]);

        foreach ($users as $user) {
            $timestamp = !empty($user['lastTimestamp']) ? $user['lastTimestamp'] : date('Y-m-d H:i:s');

            // Citizenship 1
            $data = [
                'country'        => !empty($user['citizenship1']) ? $user['citizenship1'] : null,
                'documentNumber' => !empty($user['citizenship1Passport']) ? $user['citizenship1Passport'] : null,
                'dateExpiry'     => !empty($user['citizenship1PassportExpiry']) ? $user['citizenship1PassportExpiry'] : null,
                'filePath'       => !empty($user['citizenship1PassportScan']) ? $user['citizenship1PassportScan'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // Citizenship 2
            $data = [
                'country'        => !empty($user['citizenship2']) ? $user['citizenship2'] : null,
                'documentNumber' => !empty($user['citizenship2Passport']) ? $user['citizenship2Passport'] : null,
                'dateExpiry'     => !empty($user['citizenship2PassportExpiry']) ? $user['citizenship2PassportExpiry'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 002,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Passport',
                ]);
            }

            // National ID Card
            $data = [
                'documentNumber' => !empty($user['nationalIDCardNumber']) ? $user['nationalIDCardNumber'] : null,
                'filePath'       => !empty($user['nationalIDCardScan']) ? $user['nationalIDCardScan'] : null,
            ];

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'ID Card',
                ]);
            }

            // Residency/Visa
            $data = [
                'documentType' => !empty($user['residencyStatus']) ? $user['residencyStatus'] : null,
                'dateExpiry'   => !empty($user['visaExpiryDate']) ? $user['visaExpiryDate'] : null,
            ];

            if ($data['dateExpiry'] == '0000-00-00') $data['dateExpiry'] = null;

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Visa',
                ]);
            }

            // Birth Certificate
            $data = [
                'country'  => !empty($user['countryOfBirth']) ? $user['countryOfBirth'] : null,
                'filePath' => !empty($user['birthCertificateScan']) ? $user['birthCertificateScan'] : null,
            ];

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 005,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                    'timestamp'                    => $timestamp,
                    'document'                     => 'Document',
                ]);
            }
        }

        return !$partialFail;
    }
}
