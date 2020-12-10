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
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Finance\InvoiceeGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\User\FamilyAdultGateway;
use Gibbon\Domain\User\FamilyChildGateway;
use Gibbon\Domain\Students\FirstAidGateway;
use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\DataUpdater\FamilyUpdateGateway;
use Gibbon\Domain\DataUpdater\PersonUpdateGateway;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Domain\Behaviour\BehaviourLetterGateway;
use Gibbon\Domain\DataUpdater\FinanceUpdateGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;
use Gibbon\Domain\IndividualNeeds\INArchiveGateway;
use Gibbon\Domain\Students\FirstAidFollowupGateway;
use Gibbon\Domain\Students\MedicalConditionGateway;
use Gibbon\Domain\Staff\StaffApplicationFormGateway;
use Gibbon\Domain\Students\ApplicationFormFileGateway;
use Gibbon\Domain\Staff\StaffApplicationFormFileGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\DataUpdater\MedicalConditionUpdateGateway;
use Gibbon\Domain\IndividualNeeds\INPersonDescriptorGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;

/**
 * Data Retention Gateway
 *
 * @version v21
 * @since   v21
 */
class DataRetentionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDataRetention';
    private static $primaryKey = 'gibbonDataRetentionID';

    public function getDomains()
    {
        return [
            'Student Personal Data' => [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Student'],
                'gateways' => [
                    UserGateway::class,
                    PersonUpdateGateway::class,
                    StudentNoteGateway::class,
                ],
            ],
            'Medical Data' => [
                'description' => __('Clear student medical records including medical conditions and first aid records.'),
                'context' => ['Student'],
                'gateways' => [
                    MedicalGateway::class,
                    MedicalConditionGateway::class,
                    MedicalUpdateGateway::class,
                    MedicalConditionUpdateGateway::class,
                    FirstAidGateway::class,
                    FirstAidFollowupGateway::class,
                ],
            ],
            'Finance Data' => [
                'description' => __('Clear student finance data including billing information. Invoices will be retained.'),
                'context' => ['Student'],
                'gateways' => [
                    InvoiceeGateway::class,
                    FinanceUpdateGateway::class,
                ],
            ],
            'Behaviour Records' => [
                'description' => __('Clear all behaviour data including positive and negative behaviour records and any behaviour letters sent to parents.'), 
                'context' => ['Student'],
                'gateways' => [
                    BehaviourGateway::class,
                    BehaviourLetterGateway::class
                ] 
            ],
            'Individual Needs' => [
                'description' => __('Clear individual needs records including archived records and individual needs investigations.'),
                'context' => ['Student'],
                'gateways' => [
                    INGateway::class,
                    INArchiveGateway::class,
                    INPersonDescriptorGateway::class,
                    INInvestigationGateway::class,
                    INInvestigationContributionGateway::class,
                ],
            ],
            'Family Data'=> [
                'description' => __('Clear family data such as address, country, languages and marital status.'),
                'context' => ['Student', 'Parent', 'Staff', 'Other'],
                'gateways' => [
                    FamilyGateway::class,
                    FamilyUpdateGateway::class,
                    FamilyAdultGateway::class,
                    FamilyChildGateway::class,
                ],
            ],
            'Parent Personal Data'=> [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Parent'],
                'gateways' => [
                    UserGateway::class,
                    PersonUpdateGateway::class,
                    
                ],
            ],
            'Staff Personal Data' =>  [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Staff'],
                'gateways' => [
                    UserGateway::class,
                    PersonUpdateGateway::class,
                    StaffAbsenceGateway::class,
                ],
            ],
            'Other Users Personal Data'=>  [
                'description' => __('Clear personal data such as passwords, addresses, phone numbers, id numbers, etc.'),
                'context' => ['Other'],
                'gateways' => [
                    UserGateway::class,
                    PersonUpdateGateway::class,
                ],
            ],
            'Student Application Forms' => [
                'description' => __('Clear all personal data submitted through the student application form.'),
                'context' => ['Student'],
                'gateways' => [
                    ApplicationFormGateway::class,
                    ApplicationFormFileGateway::class,
                ],
            ],
            'Staff Application Forms' => [
                'description' => __('Clear all personal data submitted through the staff application form.'),
                'gateways' => [
                    StaffApplicationFormGateway::class,
                    StaffApplicationFormFileGateway::class,
                ],
            ],
        ];
    }

    public function getAllTables()
    {
        return $this->allTables;
    }
}
