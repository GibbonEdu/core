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

namespace Gibbon\Domain\Finance;

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
    private static $primaryKey = 'gibbonFinanceInvoiceID';

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
                'gibbonFormGroup.name AS formGroup',
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
            ->leftJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonFinanceInvoice.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonFinanceInvoice.gibbonFinanceInvoiceID']);

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                switch ($status) {
                    case 'Issued':
                        $query->where('gibbonFinanceInvoice.invoiceDueDate >= :today')
                              ->bindValue('today', date('Y-m-d'));
                        break;

                    case 'Issued - Overdue':
                        $status = 'Issued';
                        $query->where('gibbonFinanceInvoice.invoiceDueDate < :today')
                              ->bindValue('today', date('Y-m-d'));
                        break;

                    case 'Paid':
                        $query->where('gibbonFinanceInvoice.invoiceDueDate >= gibbonFinanceInvoice.paidDate');
                        break;

                    case 'Paid - Late':
                        $status = 'Paid';
                        $query->where('gibbonFinanceInvoice.invoiceDueDate < gibbonFinanceInvoice.paidDate');
                        break;
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
                    ->where(function ($query) {
                        $query->where('gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID')
                              ->orWhere("(gibbonFinanceInvoiceFee.separated='N' AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID)");
                    })
                    ->bindValue('gibbonFinanceFeeCategoryID', $gibbonFinanceFeeCategoryID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryFeeCategories(QueryCriteria $criteria)
    {
        $query = $this
        ->newQuery()
        ->from('gibbonFinanceFeeCategory')
        ->cols([
          'gibbonFinanceFeeCategoryID',
          'name',
          'nameShort',
          'active',
          'description'
        ]);
        $criteria->addFilterRules([
        'active' => function ($query, $active) {
            return $query
            ->where('gibbonFinanceFeeCategory.active = :active')
            ->bindValue('active', $active);
        }
        ]);
        return $this->runQuery($query, $criteria);
    }
}
