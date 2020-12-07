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

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Finance\InvoiceeGateway;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Domain\Behaviour\BehaviourLetterGateway;
use Gibbon\Domain\DataUpdater\FinanceUpdateGateway;

/**
 * Log Gateway
 *
 * @version v17
 * @since   v17
 */
class DataRetentionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDataRetention';
    private static $primaryKey = 'gibbonDataRetentionID';

    // ['gibbonPersonID', 'gibbonFinanceInvoicee', 'gibbonFinanceInvoiceeID']

    /*
     * tableKey denotes the following:
     *  string - standard case
     *  array - more complex case requiring a join
    */
    protected $allTables = [
        // 'gibbonBehaviour' => [['tableKey', 'gibbonPersonID'],['descriptor',null], ['level',null], ['comment',''], ['followup','']],
        // 'gibbonBehaviourLetter' => [['tableKey', 'gibbonPersonID'],['body','']],
        'gibbonFamilyAdult' => [['tableKey', 'gibbonPersonID'],['comment','']],
        'gibbonFamilyChild' => [['tableKey', 'gibbonPersonID'],['comment','']],
        // 'gibbonFinanceInvoicee' => [['tableKey', 'gibbonPersonID'],['companyContact',null],['companyAddress',null],['companyEmail',null],['companyCCFamily',null],['companyPhone',null]],
        // 'gibbonFinanceInvoiceeUpdate' => [['tableKey', ['gibbonFinanceInvoicee.gibbonPersonID',' JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID)']],['companyName',null],['companyContact',null],['companyAddress',null],['companyEmail',null],['companyCCFamily',null],['companyPhone',null],['companyAll',null]],
        'gibbonFirstAid' => [['tableKey', 'gibbonPersonIDPatient'],['description',''],['actionTaken',''],['followUp','']],
        'gibbonFirstAidFollowup' => [['tableKey', ['gibbonFirstAid.gibbonPersonIDPatient',' JOIN gibbonFirstAid ON (gibbonFirstAidFollowUp.gibbonFirstAidID=gibbonFirstAid.gibbonFirstAidID)']],['followUp','']],
        'gibbonIN' => [['tableKey', 'gibbonPersonID'],['strategies',''],['targets',''],['notes','']],
        'gibbonINArchive' => [['tableKey', 'gibbonPersonID'],['strategies',''],['targets',''],['notes',''],['descriptors','']],
        'gibbonINInvestigation' => [['tableKey', 'gibbonPersonIDStudent'],['date',''],['reason',''],['strategiesTried',''],['parentsInformed',''],['parentsResponse',null],['resolutionDetails',null]],
        'gibbonINInvestigationContribution' => [['tableKey', ['gibbonINInvestigation.gibbonPersonIDStudent',' JOIN gibbonINInvestigation ON (gibbonINInvestigationContribution.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID)']],['cognition',null],['memory',null],['selfManagement',null],['attention',null],['socialInteraction',null],['communication',null],['comment',null]],
        'gibbonINPersonDescriptor' => [['tableKey', 'gibbonPersonID'],['gibbonINDescriptorID',null],['gibbonAlertLevelID',null]],
        // 'gibbonPerson' => [['tableKey', 'gibbonPersonID'],['password','randomString'],['passwordStrong','randomString'],['passwordStrongSalt','randomString'],['address1',''],['address1District',''],['address1Country',''],['address2',''],['address2District',''],['address2Country',''],['phone1Type',''],['phone1CountryCode',''],['phone1',''],['phone3Type',''],['phone3CountryCode',''],['phone3',''],['phone2Type',''],['phone2CountryCode',''],['phone2',''],['phone4Type',''],['phone4CountryCode',''],['phone4',''],['website',''],['languageFirst',''],['languageSecond',''],['languageThird',''],['countryOfBirth',''],['birthCertificateScan',''],['ethnicity',''],['citizenship1',''],['citizenship1Passport',''],['citizenship1PassportExpiry',null],['citizenship1PassportScan',''],['citizenship2',''],['citizenship2Passport',''],['citizenship2PassportExpiry',null],['religion',''],['nationalIDCardNumber',''],['nationalIDCardScan',''],['residencyStatus',''],['visaExpiryDate',null],['profession',''],['employer',''],['jobTitle',''],['emergency1Name',''],['emergency1Number1',''],['emergency1Number2',''],['emergency1Relationship',''],['emergency2Name',''],['emergency2Number1',''],['emergency2Number2',''],['emergency2Relationship',''],['transport',''],['transportNotes',''],['calendarFeedPersonal',''],['lockerNumber',''],['vehicleRegistration',''],['personalBackground',''],['studentAgreements',null],['fields','']],
        'gibbonPersonMedical' => [['tableKey', 'gibbonPersonID'],['bloodType',''],['longTermMedication',''],['longTermMedicationDetails',''],['tetanusWithin10Years',''],['comment','']],
        'gibbonPersonMedicalCondition' => [['tableKey', ['gibbonPersonMedical.gibbonPersonID',' JOIN gibbonPersonMedical ON (
            gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)']],['name',''],['gibbonAlertLevelID',null],['triggers',''],['reaction',''],['response',''],['medication',''],['lastEpisode',null],['lastEpisodeTreatment',''],['comment',''],['attachment',null]],
        'gibbonPersonMedicalConditionUpdate' => [['tableKey', ['gibbonPersonMedical.gibbonPersonID',' JOIN gibbonPersonMedical ON (gibbonPersonMedicalConditionUpdate.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)']],['name',''],['gibbonAlertLevelID',null],['triggers',''],['reaction',''],['response',''],['medication',''],['lastEpisode',null],['lastEpisodeTreatment',''],['comment',''],['attachment',null]],
        'gibbonPersonMedicalUpdate' => [['tableKey', 'gibbonPersonID'],['bloodType',''],['longTermMedication',''],['longTermMedicationDetails',''],['tetanusWithin10Years',''],['comment','']],
        'gibbonPersonUpdate' => [['tableKey', 'gibbonPersonID'],['address1',''],['address1District',''],['address1Country',''],['address2',''],['address2District',''],['address2Country',''],['phone1Type',''],['phone1CountryCode',''],['phone1',''],['phone3Type',''],['phone3CountryCode',''],['phone3',''],['phone2Type',''],['phone2CountryCode',''],['phone2',''],['phone4Type',''],['phone4CountryCode',''],['phone4',''],['languageFirst',''],['languageSecond',''],['languageThird',''],['countryOfBirth',''],['ethnicity',''],['citizenship1',''],['citizenship1Passport',''],['citizenship1PassportExpiry',null],['citizenship2',''],['citizenship2Passport',''],['citizenship2PassportExpiry',null],['religion',''],['nationalIDCardCountry',''],['nationalIDCardNumber',''],['residencyStatus',''],['visaExpiryDate',null],['profession',null],['employer',null],['jobTitle',null],['emergency1Name',null],['emergency1Number1',null],['emergency1Number2',null],['emergency1Relationship',null],['emergency2Name',null],['emergency2Number1',null],['emergency2Number2',null],['emergency2Relationship',null],['vehicleRegistration',''],['fields',''],],
        'gibbonStaffAbsence' => [['tableKey', 'gibbonPersonID'],['commentConfidential',null]],
        'gibbonStudentNote' => [['tableKey', 'gibbonPersonID'],['note','']],
    ];

    public function getDomains()
    {
        return [
            'Behaviour Records' => [
                'description' => __('Clear all behaviour data including positive and negative behaviour records and any behaviour letters sent to parents.'), 
                'context' => ['Student'],
                'gateways' => [
                    BehaviourGateway::class,
                    BehaviourLetterGateway::class
                ] 
            ],
            'Individual Needs'          => [
                'description' => __(''),
                'context' => ['Student'],
                'gateways' => [
                    InvoiceeGateway::class,
                    FinanceUpdateGateway::class,
                ],
            ],
            'Parent Data'               => [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Parent'],
                'gateways' => [
                    UserGateway::class,
                ],
            ],
            'Family Data'               => [
                'description' => __(''),
                'gateways' => [],
            ],
            'Student Data'              => [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Student'],
                'gateways' => [
                    UserGateway::class,
                ],
            ],
            'Medical Data'              => [
                'description' => __(''),
                'context' => ['Student'],
                'gateways' => [],
            ],
            'Finance Data'              => [
                'description' => __(''),
                'context' => ['Student'],
                'gateways' => [
                    InvoiceeGateway::class,
                    FinanceUpdateGateway::class,
                ],
            ],
            'Student Application Forms' => [
                'description' => __(''),
                'context' => ['Student'],
                'gateways' => [],
            ],
            'Staff Data'                => [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Staff'],
                'gateways' => [
                    UserGateway::class,
                ],
            ],
            'Staff Application Forms'   => [
                'description' => __(''),
                'gateways' => [],
            ],
        ];
    }

    public function getAllTables()
    {
        return $this->allTables;
    }

    public function runUserScrub($gibbon, $connection2, $gibbonPersonID, $tables)
    {
        $return = true ;

        // Cycle through tables
        foreach ($this->allTables AS $key => $fields) {
            $status = 'Success' ;

            // Check if in tables
            if (in_array($key, $tables)) {
                $data = [];
                $sql = "UPDATE $key ";
                if (is_array($fields[0][1])) {
                    $sql .= $fields[0][1][1];
                }
                $sql .= " SET ";
                $where = '';

                foreach ($fields as $field) {
                    if ($field[0] == 'tableKey') {
                        $data['gibbonPersonID'] = $gibbonPersonID;
                        if (is_array($field[1])) {
                            $where = " WHERE ".$field[1][0]."=:gibbonPersonID";
                        }
                        else {
                            $where = " WHERE ".$field[1]."=:gibbonPersonID";
                        }
                    }
                    else {
                        $data[$field[0]] = $field[1] ;
                        $sql .= $key.".".$field[0]."=:".$field[0].", ";
                    }
                }
                $sql = substr($sql, 0, -2).$where;

                // Data array replace
                $data = array_map(
                    function($value) {
                        $value = str_replace('', '', $value);
                        $value = str_replace('randomString', randomPassword(20), $value);
                        return $value;
                    },
                    $data
                );

                // Run queries, storing result
                 try {
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $status = 'Partial Fail' ;
                }

                // Write to gibbonDataRetention

                $data = [
                    'gibbonPersonID'            => $gibbonPersonID,
                    'tables'                    => json_encode($tables),
                    'status'                    => $status,
                    'gibbonPersonIDOperator'    => $gibbon->session->get('gibbonPersonID'),
                ];

                if (!$this->insert($data)) {
                    $return = false;
                }
            }
        }

        return $return;
    }
}
