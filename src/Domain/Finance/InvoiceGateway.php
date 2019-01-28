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

namespace Gibbon\Domain\Finance;

use Aura\SqlQuery\Common\SelectInterface;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Invoice Gateway
 *
 * @version v16
 * @since   v16
 */
class InvoiceGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFinanceInvoice';

    private static $searchableColumns = [];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryInvoicesByYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFinanceInvoice.gibbonFinanceInvoiceID', 
                'gibbonFinanceInvoice.invoiceTo',  
                'gibbonFinanceInvoice.status', 
                'gibbonFinanceInvoice.invoiceIssueDate', 
                'gibbonFinanceInvoice.paidDate', 
                'gibbonFinanceInvoice.paidAmount', 
                'gibbonFinanceInvoice.notes', 
                'gibbonPerson.surname', 
                'gibbonPerson.preferredName', 
                'gibbonRollGroup.name AS rollGroup',
                "(CASE 
                    WHEN gibbonFinanceInvoice.status = 'Pending' AND billingScheduleType='Scheduled' THEN gibbonFinanceBillingSchedule.invoiceDueDate 
                    ELSE gibbonFinanceInvoice.invoiceDueDate END
                ) AS invoiceDueDate", 
                "(CASE 
                    WHEN gibbonFinanceInvoice.status = 'Pending' AND billingScheduleType='Scheduled' THEN gibbonFinanceBillingSchedule.name
                    WHEN billingScheduleType='Ad Hoc' THEN 'Ad Hoc'
                    ELSE gibbonFinanceBillingSchedule.name END
                ) AS billingSchedule", 
                "FIND_IN_SET(gibbonFinanceInvoice.status, 'Pending,Issued,Paid,Refunded,Cancelled') as defaultSortOrder"
            ])
            ->innerJoin('gibbonFinanceInvoicee', 'gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID')
            ->innerJoin('gibbonPerson', 'gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonFinanceBillingSchedule', 'gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonFinanceInvoice.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonFinanceInvoice.gibbonFinanceInvoiceID']);

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                switch ($status) {
                    case 'Issued':     
                        $query->where('gibbonFinanceInvoice.invoiceDueDate >= :today')
                              ->bindValue('today', date('Y-m-d')); break;

                    case 'Issued - Overdue': 
                        $status = 'Issued';
                        $query->where('gibbonFinanceInvoice.invoiceDueDate < :today')
                              ->bindValue('today', date('Y-m-d')); break;

                    case 'Paid': 
                        $query->where('gibbonFinanceInvoice.invoiceDueDate >= gibbonFinanceInvoice.paidDate'); break;

                    case 'Paid - Late': 
                        $status = 'Paid';
                        $query->where('gibbonFinanceInvoice.invoiceDueDate < gibbonFinanceInvoice.paidDate'); break;
                }

                return $query
                    ->where('gibbonFinanceInvoice.status LIKE :status')
                    ->bindValue('status', $status);
            },

            'invoicee' => function ($query, $gibbonFinanceInvoiceeID) {
                return $query
                    ->where('gibbonFinanceInvoice.gibbonFinanceInvoiceeID = :gibbonFinanceInvoiceeID')
                    ->bindValue('gibbonFinanceInvoiceeID', $gibbonFinanceInvoiceeID);
            },

            'month' => function ($query, $monthOfIssue) {
                return $query
                    ->where('MONTH(gibbonFinanceInvoice.invoiceIssueDate) = :monthOfIssue')
                    ->bindValue('monthOfIssue', $monthOfIssue);
            },

            'billingSchedule' => function ($query, $gibbonFinanceBillingScheduleID) {
                if ($gibbonFinanceBillingScheduleID == 'Ad Hoc') {
                    return $query->where('gibbonFinanceInvoice.billingScheduleType = "Ad Hoc"');
                } else {
                    return $query
                        ->where('gibbonFinanceInvoice.gibbonFinanceBillingScheduleID = :gibbonFinanceBillingScheduleID')
                        ->bindValue('gibbonFinanceBillingScheduleID', $gibbonFinanceBillingScheduleID);
                }
            },

            'feeCategory' => function ($query, $gibbonFinanceFeeCategoryID) {
                return $query
                    ->leftJoin('gibbonFinanceInvoiceFee', 'gibbonFinanceInvoiceFee.gibbonFinanceInvoiceID=gibbonFinanceInvoice.gibbonFinanceInvoiceID')
                    ->leftJoin('gibbonFinanceFee', 'gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID')
                    ->where(function($query) {
                        $query->where('gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID')
                              ->orWhere("(gibbonFinanceInvoiceFee.separated='N' AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID)");
                    })
                    ->bindValue('gibbonFinanceFeeCategoryID', $gibbonFinanceFeeCategoryID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * findExportContent
     * @param int $schoolYearID
     * @param array $invoiceExportIDList
     */
    public function findExportContent(int $schoolYearID, array $invoiceExportIDList)
    {
        $data = array('schoolYearID' => $schoolYearID, 'invoiceIDList' => implode(',', $invoiceExportIDList));

        $criteria = $this->newQueryCriteria()
            ->sortBy(["FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled')", 'invoiceIssueDate', 'surname', 'preferredName'])
            ->pageSize(0);

        $query = $this->queryScheduleAndPending($data, $criteria, null,false);
        $query = $query->union();
        $query = $this->queryAdHocAndPending($data, $criteria, $query, false);
        $query = $query->union();
        $query = $this->queryNotPending($data, $criteria, $query, false);

        return $this->runQuery($query, $criteria);
    }

    /**
     * queryScheduleAndPending
     * @param array $data
     * @param QueryCriteria $criteria
     * @param SelectInterface|null $query
     * @param bool $returnResult
     * @return SelectInterface|\Gibbon\Domain\DataSet
     * @throws \Exception
     */
    public function queryScheduleAndPending(array $data, QueryCriteria $criteria, SelectInterface $query = null, bool $returnResult = true, string $identifier = '0')
    {
        $query = empty($query) ? $this->newQuery() : $query;
        $parameters = [];
        foreach($data as $q=>$w)
            $parameters[$q.$identifier] = $w;
        $query->from($this->getTableName())
            ->cols(['gibbonFinanceInvoice.gibbonFinanceInvoiceID', 'surname', 'preferredName', 'gibbonPerson.gibbonPersonID', 'dob', 'gender,
				studentID', 'gibbonFinanceInvoice.invoiceTo', 'gibbonFinanceInvoice.status', 'gibbonFinanceInvoice.invoiceIssueDate,
				gibbonFinanceBillingSchedule.invoiceDueDate', 'paidDate', 'paidAmount', 'gibbonFinanceBillingSchedule.name AS billingSchedule,
				NULL AS billingScheduleExtra', 'notes', 'gibbonRollGroup.name AS rollGroup'])
            ->leftJoin('gibbonFinanceBillingSchedule', 'gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID')
            ->leftJoin('gibbonFinanceInvoicee', 'gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID')
            ->leftJoin('gibbonPerson', 'gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                            AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonFinanceInvoice.gibbonSchoolYearID=:schoolYearID0')
            ->where('billingScheduleType=:scheduleType0')
            ->where('gibbonFinanceInvoice.status=:status0')
            ->bindValues(['scheduleType0' => 'Scheduled', 'status0' => 'Pending'])
            ->where('gibbonFinanceInvoice.gibbonFinanceInvoiceID IN (:invoiceIDList0)')
            ->bindValues($parameters)
        ;

        return $returnResult ? $this->runQuery($query, $criteria) : $query ;
    }

    /**
     * queryAdHocAndPending
     * @param array $data
     * @param QueryCriteria $criteria
     * @param SelectInterface|null $query
     * @param bool $returnResult
     * @param string $identifier
     * @return SelectInterface|\Gibbon\Domain\DataSet
     * @throws \Exception
     */
    public function queryAdHocAndPending(array $data = [], QueryCriteria $criteria, SelectInterface $query = null, bool $returnResult = true, string $identifier = '1')
    {
        $query = empty($query) ? $this->newQuery() : $query;
        $parameters = [];
        foreach($data as $q=>$w)
            $parameters[$q.$identifier] = $w;
        $query->from($this->getTableName())
            ->cols(['gibbonFinanceInvoice.gibbonFinanceInvoiceID', 'surname', 'preferredName', 'gibbonPerson.gibbonPersonID', 'dob', 'gender,
				studentID', 'gibbonFinanceInvoice.invoiceTo', 'gibbonFinanceInvoice.status', 'gibbonFinanceInvoice.invoiceIssueDate,
				gibbonFinanceBillingSchedule.invoiceDueDate', 'paidDate', 'paidAmount', 'gibbonFinanceBillingSchedule.name AS billingSchedule,
				NULL AS billingScheduleExtra', 'notes', 'gibbonRollGroup.name AS rollGroup'])
            ->leftJoin('gibbonFinanceBillingSchedule', 'gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID')
            ->leftJoin('gibbonFinanceInvoicee', 'gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID')
            ->leftJoin('gibbonPerson', 'gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonFinanceInvoice.gibbonSchoolYearID=:schoolYearID1')
            ->where('billingScheduleType=:scheduleType1')
            ->where('gibbonFinanceInvoice.status=:status1')
            ->bindValues(['scheduleType1' => 'Ad Hoc', 'status1' => 'Pending'])
            ->where('gibbonFinanceInvoice.gibbonFinanceInvoiceID IN (:invoiceIDList1)')
        ;
        $query = ! empty($parameters) ? $query->bindValues($parameters) : $query;

        return $returnResult ? $this->runQuery($query, $criteria) : $query ;
    }

    /**
     * queryNotPending
     * @param array $data
     * @param QueryCriteria $criteria
     * @param SelectInterface|null $query
     * @param bool $returnResult
     * @param string $identifier
     * @return SelectInterface|\Gibbon\Domain\DataSet
     * @throws \Exception
     */
    public function queryNotPending(array $data = [], QueryCriteria $criteria, SelectInterface $query = null, bool $returnResult = true, string $identifier = '2')
    {
        $query = empty($query) ? $this->newQuery() : $query;
        $parameters = [];
        foreach($data as $q=>$w)
            $parameters[$q.$identifier] = $w;
        $query->from($this->getTableName())
            ->cols(['gibbonFinanceInvoice.gibbonFinanceInvoiceID', 'surname', 'preferredName', 'gibbonPerson.gibbonPersonID', 'dob', 'gender,
				studentID', 'gibbonFinanceInvoice.invoiceTo', 'gibbonFinanceInvoice.status', 'gibbonFinanceInvoice.invoiceIssueDate,
				gibbonFinanceBillingSchedule.invoiceDueDate', 'paidDate', 'paidAmount', 'gibbonFinanceBillingSchedule.name AS billingSchedule,
				NULL AS billingScheduleExtra', 'notes', 'gibbonRollGroup.name AS rollGroup'])
            ->leftJoin('gibbonFinanceBillingSchedule', 'gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID')
            ->leftJoin('gibbonFinanceInvoicee', 'gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID')
            ->leftJoin('gibbonPerson', 'gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonFinanceInvoice.gibbonSchoolYearID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonFinanceInvoice.gibbonSchoolYearID=:schoolYearID2')
            ->where('gibbonFinanceInvoice.status != :status2')
            ->bindValue('status2', 'Pending')
            ->where('gibbonFinanceInvoice.gibbonFinanceInvoiceID IN (:invoiceIDList2)')
        ;
        $query = ! empty($parameters) ? $query->bindValues($parameters) : $query;

        return $returnResult ? $this->runQuery($query, $criteria) : $query ;
    }
}
