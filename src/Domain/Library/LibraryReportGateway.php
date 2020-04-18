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

namespace Gibbon\Domain\Library;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * LibraryReportGateway
 *
 * @version v20
 * @since   v20
 */
class LibraryReportGateway extends QueryableGateway
{
    use TableAware;
    
    public function selectOverdueItems($ignoreStatus = null)
    {
        $data = ['today' => date('Y-m-d')];

        if ($ignoreStatus == 'on') {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email 
            FROM gibbonLibraryItem 
            JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) 
            WHERE gibbonLibraryItem.status='On Loan' 
            AND borrowable='Y' 
            AND returnExpected<:today 
            ORDER BY surname, preferredName";
        } else {
            $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email 
            FROM gibbonLibraryItem 
            JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) 
            WHERE gibbonLibraryItem.status='On Loan' 
            AND borrowable='Y' 
            AND returnExpected<:today AND gibbonPerson.status='Full' 
            ORDER BY surname, preferredName";
        }

        return $this->db()->select($sql, $data);
    }
}
