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
 * Time: 13:03
 */

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

/**
 * Class FinanceExpenseGateway
 * @package Gibbon\Domain\Finance
 */
class FinanceExpenseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFinanceExpense';

    private static $searchableColumns = [];

    /**
     * queryByExpenseIDList
     * @param array $expenseIDList
     * @param QueryCriteria $criteria
     * @return DataSet
     * @throws \Exception
     */
    public function queryByExpenseIDList(array $expenseIDList, QueryCriteria $criteria): DataSet
    {   
        return $this->runQuery(
            $this->newQuery()
                ->from($this->getTableName())
                ->cols(['gibbonFinanceExpense.*', 'gibbonFinanceBudget.name AS budget', 'gibbonFinanceBudgetCycle.name AS budgetCycle', 'preferredName', 'surname'])
                ->leftJoin('gibbonPerson', 'gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID')
                ->leftJoin('gibbonFinanceBudget', 'gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID')
                ->leftJoin('gibbonFinanceBudgetCycle', 'gibbonFinanceExpense.gibbonFinanceBudgetCycleID=gibbonFinanceBudgetCycle.gibbonFinanceBudgetCycleID')
                ->where('gibbonFinanceExpense.gibbonFinanceExpenseID IN (:expenseIDList)')
                ->bindValue('expenseIDList', implode(',', $expenseIDList)),
            $criteria
        );
    }
}
