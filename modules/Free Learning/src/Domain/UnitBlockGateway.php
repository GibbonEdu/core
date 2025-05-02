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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitBlockGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitBlock';
    private static $primaryKey = 'freeLearningUnitBlockID';
    private static $searchableColumns = ['freeLearningUnitBlock.title'];

    public function selectAllBlocks()
    {
        $sql = "
            SELECT freeLearningUnitBlock.*, freeLearningUnit.name as unitName
            FROM freeLearningUnitBlock
            LEFT JOIN freeLearningUnit ON (freeLearningUnitBlock.freeLearningUnitID = freeLearningUnit.freeLearningUnitID)
            ORDER BY unitName, sequenceNumber
        ";

        return $this->db()->select($sql);
    }

    public function selectBlocksByUnit($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
