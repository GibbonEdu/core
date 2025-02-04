<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

class PettyCashGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinancePettyCashID';
    private static $tableName = 'gibbonFinancePettyCash';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username'];

    public function queryPettyCashBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
        ->newQuery()
        ->cols([
            'gibbonFinancePettyCash.gibbonFinancePettyCashID',
            'gibbonFinancePettyCash.amount',
            'gibbonFinancePettyCash.reason',
            'gibbonFinancePettyCash.notes',
            'gibbonFinancePettyCash.actionRequired',
            'gibbonFinancePettyCash.status',
            'gibbonFinancePettyCash.timestampCreated',
            'gibbonFinancePettyCash.timestampStatus',
            'gibbonPerson.gibbonPersonID',
            'gibbonPerson.preferredName',
            'gibbonPerson.title',
            'gibbonPerson.surname',
            'gibbonRole.category as roleCategory',
            'gibbonFormGroup.nameShort as formGroup',
            '(CASE WHEN gibbonFinancePettyCash.status = "Pending" THEN 0 ELSE 1 END) as statusSort',
          ])
        ->from('gibbonFinancePettyCash')
        ->innerJoin('gibbonPerson', 'gibbonFinancePettyCash.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID = gibbonPerson.gibbonRoleIDPrimary')
        ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = gibbonFinancePettyCash.gibbonSchoolYearID')
        ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
        ->where('gibbonFinancePettyCash.gibbonSchoolYearID = :gibbonSchoolYearID')
        ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function queryPettyCashBalance(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
        ->newQuery()
        ->cols([
            'SUM(gibbonFinancePettyCash.amount) as total',
          ])
        ->from('gibbonFinancePettyCash')
        ->innerJoin('gibbonPerson', 'gibbonFinancePettyCash.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->where('gibbonFinancePettyCash.status = "Pending"')
        ->where('gibbonFinancePettyCash.actionRequired = "Repay"')
        ->where('gibbonFinancePettyCash.gibbonSchoolYearID = :gibbonSchoolYearID')
        ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectPettyCashBalanceByStudent($gibbonSchoolYearID)
    {
        $select = $this
        ->newSelect()
        ->cols([ 
            'DATE(gibbonFinancePettyCash.timestampCreated) as date', 
            'SUM(gibbonFinancePettyCash.amount) as amount', 
            'gibbonPerson.gibbonPersonID',
            'gibbonPerson.preferredName as studentPreferredName',
            'gibbonPerson.surname as studentSurname',
            'gibbonPerson.officialName as studentOfficialName',
          ])
        ->from('gibbonFinancePettyCash')
        ->innerJoin('gibbonPerson', 'gibbonFinancePettyCash.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID = gibbonPerson.gibbonRoleIDPrimary')
        ->where('gibbonFinancePettyCash.status = "Pending"')
        ->where('gibbonFinancePettyCash.actionRequired = "Repay"')
        ->where('gibbonRole.category = "Student"')
        ->where("gibbonPerson.status = 'Full'")
        ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
        ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
        ->where('gibbonFinancePettyCash.gibbonSchoolYearID = :gibbonSchoolYearID')
        ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->where('DATE(gibbonFinancePettyCash.timestampCreated) < :today')
        ->bindValue('today', date('Y-m-d'))
        ->groupBy(['gibbonPerson.gibbonPersonID'])
        ->having('amount > 0');

        return $this->runSelect($select);
    }

    public function selectPettyCashBalanceByStaff($gibbonSchoolYearID)
    {
        $select = $this
        ->newSelect()
        ->cols([ 
            'DATE(gibbonFinancePettyCash.timestampCreated) as date', 
            'SUM(gibbonFinancePettyCash.amount) as amount', 
            'gibbonPerson.gibbonPersonID',
            'gibbonPerson.title',
            'gibbonPerson.preferredName',
            'gibbonPerson.surname',
            'gibbonPerson.email',
          ])
        ->from('gibbonFinancePettyCash')
        ->innerJoin('gibbonPerson', 'gibbonFinancePettyCash.gibbonPersonID = gibbonPerson.gibbonPersonID')
        ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID = gibbonPerson.gibbonRoleIDPrimary')
        ->where('gibbonFinancePettyCash.status = "Pending"')
        ->where('gibbonFinancePettyCash.actionRequired = "Repay"')
        ->where('gibbonRole.category = "Staff"')
        ->where("gibbonPerson.status = 'Full'")
        ->where('gibbonFinancePettyCash.gibbonSchoolYearID = :gibbonSchoolYearID')
        ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->where('DATE(gibbonFinancePettyCash.timestampCreated) < :today')
        ->bindValue('today', date('Y-m-d'))
        ->groupBy(['gibbonPerson.gibbonPersonID'])
        ->having('amount > 0');

        return $this->runSelect($select);
    }
}
