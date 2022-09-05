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

namespace Gibbon\Module\Finance\Tables;

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Finance\ExpenseGateway;

/**
 * ExpenseLog
 *
 * @version v21
 * @since   v21
 */
class ExpenseLog
{
    protected $expenseGateway;

    public function __construct(ExpenseGateway $expenseGateway)
    {
        $this->expenseGateway = $expenseGateway;
    }

    public function create($gibbonFinanceExpenseID, $expanded = false)
    {
        $criteria = $this->expenseGateway->newQueryCriteria()
            ->sortBy('timestamp')
            ->fromPOST();

        $expenses = $this->expenseGateway->queryExpenseLogByID($criteria, $gibbonFinanceExpenseID);

        $table = DataTable::create('expenseLog')->withData($expenses);

        $table->addExpandableColumn('comment')->setExpanded($expanded);

        $table->addColumn('name', __('Person'))
            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));
            
        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', 'timestamp'));
            
        $table->addColumn('action', __('Event'));

        return $table;
    }
}
