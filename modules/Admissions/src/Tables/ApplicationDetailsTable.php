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

namespace Gibbon\Module\Admissions\Tables;

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;


/**
 * ApplicationDetailsTable
 *
 * @version v24
 * @since   v24
 */
class ApplicationDetailsTable extends DataTable
{
    protected $view;
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;

    }

    public function createTable()
    {
        $table = DataTable::createDetails('applicationDetails');

        $table->addColumn('student', __('Student'))
            ->format(function ($values) {
                return Format::name('', $values['studentSurname'], $values['studentPreferredName'], 'Student', true);
            });
        $table->addColumn('gibbonAdmissionsApplicationID', __('Application ID'))
            ->format(function ($values) {
                return intval($values['gibbonAdmissionsApplicationID']);
            });
        $table->addColumn('applicationName', __('Application Form'));

        $table->addColumn('status', __('Status'))->format(function ($values) {
            switch ($values['status']) {
                case 'Accepted':
                    $class = 'success'; break;
                case 'Incomplete':
                    $class = 'warning'; break;
                case 'Rejected':
                case 'Withdrawn':      
                    $class = 'error'; break;
                case 'Pending':
                case 'Waiting List':
                    $class = 'dull'; break;
                default: $class = '';
            }

            return Format::tag($values['status'], $class);
        });
        $table->addColumn('schoolYear', __('School Year'));
        $table->addColumn('timestampCreated', __('Created'))->format(Format::using('date', 'timestampCreated'));

        return $table;
    }
}
