<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Date: 28/01/2019
 * Time: 11:53
 */

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Class FinanceInvoiceFeeGateway
 * @package Gibbon\Domain\Finance
 */
class FinanceInvoiceFeeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFinanceInvoiceFee';

    private static $searchableColumns = [];

    /**
     * getFee
     * @param int $invoiceID
     * @param string $status
     * @return bool|float
     * @throws \Exception
     */
    public function getFee(int $invoiceID, string $status = '')
    {
        if ($status === 'Pending')
            $total = $this->queryPendingInvoiceFee($invoiceID);
        else
            $total = $this->queryInvoiceFee($invoiceID);

        if ($total->getResultCount() === 0)
            return false;

        $total = $total->getRow(0);
        $totalFee = 0;
        if (is_numeric($total["fee2"])) {
            $totalFee+=$total["fee2"] ;
        }
        else {
            $totalFee+=$total["fee"] ;
        }
        return (float) $totalFee;
    }

    /**
     * queryPendingInvoiceFee
     * @param $invoiceID
     * @return \Gibbon\Domain\DataSet
     * @throws \Exception
     */
    public function queryPendingInvoiceFee($invoiceID)
    {
        return $this->runQuery(
            $this->newQuery()
                ->from($this->getTableName())
                ->cols(['gibbonFinanceInvoiceFee.fee AS fee', 'NULL AS fee2'])
                ->where('gibbonFinanceInvoiceID = :invoiceID')
                ->bindValue('invoiceID', $invoiceID),
            $this->newQueryCriteria());
    }

    /**
     * queryInvoiceFee
     * @param $invoiceID
     * @return \Gibbon\Domain\DataSet
     * @throws \Exception
     */
    public function queryInvoiceFee($invoiceID)
    {
        return $this->runQuery(
            $this->newQuery()
                ->from($this->getTableName())
                ->cols(['gibbonFinanceInvoiceFee.fee AS fee', 'gibbonFinanceFee.fee AS fee2'])
                ->leftJoin('gibbonFinanceFee', 'gibbonFinanceInvoiceFee.gibbonFinanceFeeID = gibbonFinanceFee.gibbonFinanceFeeID')
                ->where('gibbonFinanceInvoiceID = :invoiceID')
                ->bindValue('invoiceID', $invoiceID),
            $this->newQueryCriteria());
    }
}
