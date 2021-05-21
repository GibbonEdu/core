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

use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;

/**
 * Personal Document Migration - move document data into normalized table entries.
 */
class PersonalDocuments extends Migration
{
    protected $userGateway;
    protected $personalDocumentGateway;

    public function __construct(UserGateway $userGateway, PersonalDocumentGateway $personalDocumentGateway)
    {
        $this->userGateway = $userGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        $users = $this->userGateway->selectBy([]); 

        foreach ($users as $user) {

            // Citizenship 1
            $data = [
                'country'        => $user['citizenship1'],
                'documentNumber' => $user['citizenship1Passport'],
                'dateExpiry'     => $user['citizenship1PassportExpiry'],
                'filePath'       => $user['citizenship1PassportScan'],
            ];

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 001,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                ]);
            }

            // Citizenship 2
            $data = [
                'country'        => $user['citizenship2'],
                'documentNumber' => $user['citizenship2Passport'],
                'dateExpiry'     => $user['citizenship2PassportExpiry'],
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 002,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                ]);
            }

            // National ID Card
            $data = [
                'documentNumber' => $user['nationalIDCardNumber'],
                'filePath'       => $user['nationalIDCardScan'],
            ];

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 003,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                ]);
            }

            // Residency/Visa
            $data = [
                'documentType' => $user['residencyStatus'],
                'dateExpiry'   => $user['visaExpiryDate'],
            ];

            if (count(array_filter($data)) > 0) {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 004,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                ]);
            }

            // Birth Certificate
            $data = [
                'country'  => $user['countryOfBirth'],
                'filePath' => $user['birthCertificateScan'],
            ];

            if (count(array_filter($data)) > 0 && $data['filePath'] != 'Deleted File') {
                $partialFail &= !$this->personalDocumentGateway->insert($data + [
                    'gibbonPersonalDocumentTypeID' => 005,
                    'foreignTable'                 => 'gibbonPerson',
                    'foreignTableID'               => $user['gibbonPersonID'],
                ]);
            }
        }

        return !$partialFail;
    }
}
