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
use Gibbon\Domain\Traits\TableAware;

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

    /*
     * tableKey denotes the following:
     *  string - standard case
     *  array - more complex case requiring a join
    */
    protected $allTables = [
        'gibbonBehaviour' => [['tableKey', 'gibbonPersonID'],['descriptor',null], ['level',null], ['comment','emptyString'], ['followup','emptyString']],
        'gibbonBehaviourLetter' => [['tableKey', 'gibbonPersonID'],['body','emptyString']],
        'gibbonFamilyAdult' => [['tableKey', 'gibbonPersonID'],['comment','emptyString']],
        'gibbonFamilyChild' => [['tableKey', 'gibbonPersonID'],['comment','emptyString']],
        'gibbonFinanceInvoicee' => [['tableKey', 'gibbonPersonID'],['companyContact',null],['companyAddress',null],['companyEmail',null],['companyCCFamily',null],['companyPhone',null]],
        'gibbonFinanceInvoiceeUpdate' => [['tableKey', ['gibbonFinanceInvoicee.gibbonPersonID',' JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID)']],['companyName',null],['companyContact',null],['companyAddress',null],['companyEmail',null],['companyCCFamily',null],['companyPhone',null],['companyAll',null]],
        'gibbonFirstAid' => [['tableKey', 'gibbonPersonIDPatient'],['description','emptyString'],['actionTaken','emptyString'],['followUp','emptyString']],
        'gibbonFirstAidFollowup' => [['tableKey', ['gibbonFirstAid.gibbonPersonIDPatient',' JOIN gibbonFirstAid ON (gibbonFirstAidFollowUp.gibbonFirstAidID=gibbonFirstAid.gibbonFirstAidID)']],['followUp','emptyString']],
        'gibbonIN' => [['tableKey', 'gibbonPersonID'],['strategies','emptyString'],['targets','emptyString'],['notes','emptyString']],
        'gibbonINArchive' => [['tableKey', 'gibbonPersonID'],['strategies','emptyString'],['targets','emptyString'],['notes','emptyString'],['descriptors','emptyString']],
        'gibbonINInvestigation' => [['tableKey', 'gibbonPersonIDStudent'],['date','emptyString'],['reason','emptyString'],['strategiesTried','emptyString'],['parentsInformed','emptyString'],['parentsResponse',null],['resolutionDetails',null]],
        'gibbonINInvestigationContribution' => [['tableKey', ['gibbonINInvestigation.gibbonPersonIDStudent',' JOIN gibbonINInvestigation ON (gibbonINInvestigationContribution.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID)']],['cognition',null],['memory',null],['selfManagement',null],['attention',null],['socialInteraction',null],['communication',null],['comment',null]],
        'gibbonINPersonDescriptor' => [['tableKey', 'gibbonPersonID'],['gibbonINDescriptorID',null],['gibbonAlertLevelID',null]],
        'gibbonPerson' => [['tableKey', 'gibbonPersonID'],['password','randomString'],['passwordStrong','randomString'],['passwordStrongSalt','randomString'],['address1','emptyString'],['address1District','emptyString'],['address1Country','emptyString'],['address2','emptyString'],['address2District','emptyString'],['address2Country','emptyString'],['phone1Type','emptyString'],['phone1CountryCode','emptyString'],['phone1','emptyString'],['phone3Type','emptyString'],['phone3CountryCode','emptyString'],['phone3','emptyString'],['phone2Type','emptyString'],['phone2CountryCode','emptyString'],['phone2','emptyString'],['phone4Type','emptyString'],['phone4CountryCode','emptyString'],['phone4','emptyString'],['website','emptyString'],['languageFirst','emptyString'],['languageSecond','emptyString'],['languageThird','emptyString'],['countryOfBirth','emptyString'],['birthCertificateScan','emptyString'],['ethnicity','emptyString'],['citizenship1','emptyString'],['citizenship1Passport','emptyString'],['citizenship1PassportExpiry',null],['citizenship1PassportScan','emptyString'],['citizenship2','emptyString'],['citizenship2Passport','emptyString'],['citizenship2PassportExpiry',null],['religion','emptyString'],['nationalIDCardNumber','emptyString'],['nationalIDCardScan','emptyString'],['residencyStatus','emptyString'],['visaExpiryDate',null],['profession','emptyString'],['employer','emptyString'],['jobTitle','emptyString'],['emergency1Name','emptyString'],['emergency1Number1','emptyString'],['emergency1Number2','emptyString'],['emergency1Relationship','emptyString'],['emergency2Name','emptyString'],['emergency2Number1','emptyString'],['emergency2Number2','emptyString'],['emergency2Relationship','emptyString'],['transport','emptyString'],['transportNotes','emptyString'],['calendarFeedPersonal','emptyString'],['lockerNumber','emptyString'],['vehicleRegistration','emptyString'],['personalBackground','emptyString'],['studentAgreements',null],['fields','emptyString']],
        'gibbonPersonMedical' => [['tableKey', 'gibbonPersonID'],['bloodType','emptyString'],['longTermMedication','emptyString'],['longTermMedicationDetails','emptyString'],['tetanusWithin10Years','emptyString'],['comment','emptyString']],
        'gibbonPersonMedicalCondition' => [['tableKey', ['gibbonPersonMedical.gibbonPersonID',' JOIN gibbonPersonMedical ON (
            gibbonPersonMedicalCondition.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)']],['name','emptyString'],['gibbonAlertLevelID',null],['triggers','emptyString'],['reaction','emptyString'],['response','emptyString'],['medication','emptyString'],['lastEpisode',null],['lastEpisodeTreatment','emptyString'],['comment','emptyString'],['attachment',null]],
        'gibbonPersonMedicalConditionUpdate' => [['tableKey', ['gibbonPersonMedical.gibbonPersonID',' JOIN gibbonPersonMedical ON (gibbonPersonMedicalConditionUpdate.gibbonPersonMedicalID=gibbonPersonMedical.gibbonPersonMedicalID)']],['name','emptyString'],['gibbonAlertLevelID',null],['triggers','emptyString'],['reaction','emptyString'],['response','emptyString'],['medication','emptyString'],['lastEpisode',null],['lastEpisodeTreatment','emptyString'],['comment','emptyString'],['attachment',null]],
        'gibbonPersonMedicalUpdate' => [['tableKey', 'gibbonPersonID'],['bloodType','emptyString'],['longTermMedication','emptyString'],['longTermMedicationDetails','emptyString'],['tetanusWithin10Years','emptyString'],['comment','emptyString']],
        'gibbonPersonUpdate' => [['tableKey', 'gibbonPersonID'],['address1','emptyString'],['address1District','emptyString'],['address1Country','emptyString'],['address2','emptyString'],['address2District','emptyString'],['address2Country','emptyString'],['phone1Type','emptyString'],['phone1CountryCode','emptyString'],['phone1','emptyString'],['phone3Type','emptyString'],['phone3CountryCode','emptyString'],['phone3','emptyString'],['phone2Type','emptyString'],['phone2CountryCode','emptyString'],['phone2','emptyString'],['phone4Type','emptyString'],['phone4CountryCode','emptyString'],['phone4','emptyString'],['languageFirst','emptyString'],['languageSecond','emptyString'],['languageThird','emptyString'],['countryOfBirth','emptyString'],['ethnicity','emptyString'],['citizenship1','emptyString'],['citizenship1Passport','emptyString'],['citizenship1PassportExpiry',null],['citizenship2','emptyString'],['citizenship2Passport','emptyString'],['citizenship2PassportExpiry',null],['religion','emptyString'],['nationalIDCardCountry','emptyString'],['nationalIDCardNumber','emptyString'],['residencyStatus','emptyString'],['visaExpiryDate',null],['profession',null],['employer',null],['jobTitle',null],['emergency1Name',null],['emergency1Number1',null],['emergency1Number2',null],['emergency1Relationship',null],['emergency2Name',null],['emergency2Number1',null],['emergency2Number2',null],['emergency2Relationship',null],['vehicleRegistration','emptyString'],['fields','emptyString'],],
        'gibbonStaffAbsence' => [['tableKey', 'gibbonPersonID'],['commentConfidential',null]],
        'gibbonStudentNote' => [['tableKey', 'gibbonPersonID'],['note','emptyString']],
    ];

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
                        $value = str_replace('emptyString', '', $value);
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
